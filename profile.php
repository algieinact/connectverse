<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$success_message = '';
$error_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate input
    if (empty($full_name) || empty($email)) {
        $error_message = "Nama lengkap dan email harus diisi";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Format email tidak valid";
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();

            // Check if email is already taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user['id']]);
            if ($stmt->rowCount() > 0) {
                throw new Exception("Email sudah digunakan oleh pengguna lain");
            }

            // Update basic info
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $user['id']]);

            // Update password if provided
            if (!empty($current_password)) {
                if (empty($new_password) || empty($confirm_password)) {
                    throw new Exception("Password baru dan konfirmasi password harus diisi");
                }
                if ($new_password !== $confirm_password) {
                    throw new Exception("Password baru dan konfirmasi password tidak cocok");
                }
                if (!password_verify($current_password, $user['password'])) {
                    throw new Exception("Password saat ini tidak valid");
                }

                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user['id']]);
            }

            $pdo->commit();
            $success_message = "Profil berhasil diperbarui";
            
            // Refresh user data
            $user = getCurrentUser();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - ConnectVerse</title>
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
        
        .profile-header {
            background: var(--dark-bg);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .card {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
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

    <!-- Profile Header -->
    <section class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <img src="<?php echo $user['profile_picture'] ?? 'assets/img/default-profile.png'; ?>" 
                         alt="Profile Picture" class="profile-picture">
                </div>
                <div class="col-md-10">
                    <h1 class="display-6 fw-bold mb-2"><?php echo htmlspecialchars($user['full_name']); ?></h1>
                    <p class="lead mb-0">
                        <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($user['email']); ?>
                    </p>
                    <p class="mb-0">
                        <span class="badge bg-primary">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <div class="container mb-5">
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Edit Profil</h4>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>

                            <hr class="my-4">
                            <h5 class="mb-3">Ubah Password</h5>
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Password Saat Ini</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Password Baru</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 