<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();

// Check if user can create community
if ($user['role'] !== 'community_admin') {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];
    
    // Handle file uploads
    $profile_picture = uploadImage($_FILES['profile_picture']);
    $bg_picture = uploadImage($_FILES['bg_picture']);
    
    // Insert community
    try {
        $stmt = $pdo->prepare("INSERT INTO communities (name, description, category_id, profile_picture, bg_picture, admin_id) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $description, $category_id, $profile_picture, $bg_picture, $user['id']])) {
            $success = 'Komunitas berhasil dibuat!';
            // Redirect after 2 seconds
            header("refresh:2;url=communities.php");
        }
    } catch (PDOException $e) {
        $error = 'Terjadi kesalahan saat membuat komunitas';
    }
}

// Get categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Komunitas - ConnectVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-red: #dc3545;
            --dark-bg: #212529;
        }
        
        .navbar-brand {
            font-weight: bold;
            color: var(--primary-red) !important;
        }
        
        .btn-primary {
            background: var(--primary-red);
            border-color: var(--primary-red);
        }
        
        .btn-primary:hover {
            background: #c82333;
            border-color: #c82333;
        }
        
        .card {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .form-section {
            background: var(--dark-bg);
            color: white;
            padding: 2rem 0;
        }
        
        .image-preview {
            width: 100%;
            height: 200px;
            border: 2px dashed #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            margin-top: 10px;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-users"></i> ConnectVerse
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="communities.php">Komunitas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php">Event</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <section class="form-section">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <h1 class="display-6 fw-bold mb-0">Buat Komunitas Baru</h1>
                    <p class="lead">Bangun komunitas dan kumpulkan orang-orang dengan minat yang sama</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="communities.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left"></i> Kembali ke Komunitas
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nama Komunitas *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <div class="form-text">Berikan nama yang menarik dan mudah diingat</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Kategori *</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Pilih Kategori</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Deskripsi Komunitas *</label>
                                <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                                <div class="form-text">Jelaskan tujuan, aktivitas, dan kegiatan komunitas Anda</div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="profile_picture" class="form-label">Logo/Foto Profil Komunitas</label>
                                    <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                                    <div class="image-preview" id="profile_preview">
                                        <span class="text-muted">
                                            <i class="fas fa-image fa-2x"></i><br>
                                            Preview logo komunitas
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="bg_picture" class="form-label">Foto Latar Belakang</label>
                                    <input type="file" class="form-control" id="bg_picture" name="bg_picture" accept="image/*">
                                    <div class="image-preview" id="bg_preview">
                                        <span class="text-muted">
                                            <i class="fas fa-image fa-2x"></i><br>
                                            Preview latar belakang
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Tips:</strong> Upload gambar dengan kualitas baik untuk menarik lebih banyak anggota. 
                                Format yang didukung: JPG, PNG, GIF (maksimal 5MB).
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="communities.php" class="btn btn-secondary me-md-2">Batal</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Buat Komunitas
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview functionality
        function previewImage(input, previewId) {
            const file = input.files[0];
            const preview = document.getElementById(previewId);
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                };
                reader.readAsDataURL(file);
            }
        }
        
        document.getElementById('profile_picture').addEventListener('change', function() {
            previewImage(this, 'profile_preview');
        });
        
        document.getElementById('bg_picture').addEventListener('change', function() {
            previewImage(this, 'bg_preview');
        });
    </script>
</body>
</html>