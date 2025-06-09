<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
if (!$user || $user['role'] !== 'community_admin') {
    header('Location: dashboard_admin.php');
    exit();
}

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$message = '';
$error = '';

// Get categories for forms
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Handle actions
if ($_POST) {
    if ($action === 'create' || $action === 'edit') {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $category_id = $_POST['category_id'];
        
        if (empty($name)) {
            $error = 'Nama komunitas harus diisi!';
        } else {
            try {
                // Handle image uploads
                $profile_picture = null;
                $bg_picture = null;
                
                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                    $profile_picture = uploadImage($_FILES['profile_picture'], 'uploads/communities/');
                }
                
                if (isset($_FILES['bg_picture']) && $_FILES['bg_picture']['error'] === UPLOAD_ERR_OK) {
                    $bg_picture = uploadImage($_FILES['bg_picture'], 'uploads/communities/');
                }
                
                if ($action === 'create') {
                    $sql = "INSERT INTO communities (name, description, category_id, profile_picture, bg_picture, admin_id) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$name, $description, $category_id, $profile_picture, $bg_picture, $user['id']]);
                    $message = 'Komunitas berhasil dibuat!';
                } else {
                    // Get current community data
                    $stmt = $pdo->prepare("SELECT * FROM communities WHERE id = ? AND admin_id = ?");
                    $stmt->execute([$id, $user['id']]);
                    $community = $stmt->fetch();
                    
                    if ($community) {
                        $sql = "UPDATE communities SET name = ?, description = ?, category_id = ?";
                        $params = [$name, $description, $category_id];
                        
                        if ($profile_picture) {
                            $sql .= ", profile_picture = ?";
                            $params[] = $profile_picture;
                        }
                        
                        if ($bg_picture) {
                            $sql .= ", bg_picture = ?";
                            $params[] = $bg_picture;
                        }
                        
                        $sql .= " WHERE id = ? AND admin_id = ?";
                        $params[] = $id;
                        $params[] = $user['id'];
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);
                        $message = 'Komunitas berhasil diupdate!';
                    }
                }
                
                $action = 'list';
            } catch (PDOException $e) {
                $error = 'Terjadi kesalahan: ' . $e->getMessage();
            }
        }
    }
}

// Handle delete
if ($action === 'delete' && $id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM communities WHERE id = ? AND admin_id = ?");
        $stmt->execute([$id, $user['id']]);
        $message = 'Komunitas berhasil dihapus!';
        $action = 'list';
    } catch (PDOException $e) {
        $error = 'Gagal menghapus komunitas: ' . $e->getMessage();
    }
}

// Get communities list
if ($action === 'list') {
    $stmt = $pdo->prepare("
        SELECT c.*, cat.name as category_name, 
               COUNT(cm.id) as member_count
        FROM communities c
        LEFT JOIN categories cat ON c.category_id = cat.id
        LEFT JOIN community_members cm ON c.id = cm.community_id
        WHERE c.admin_id = ?
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $communities = $stmt->fetchAll();
}

// Get community data for edit
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM communities WHERE id = ? AND admin_id = ?");
    $stmt->execute([$id, $user['id']]);
    $community = $stmt->fetch();
    
    if (!$community) {
        $error = 'Komunitas tidak ditemukan!';
        $action = 'list';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Komunitas - ConnectVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 15px 20px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .community-card {
            transition: transform 0.2s;
        }
        .community-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3 text-white">
                    <h4><i class="fas fa-users"></i> ConnectVerse</h4>
                    <small>Dashboard Community Admin</small>
                </div>
                
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a class="nav-link active" href="dashboard_community.php">
                        <i class="fas fa-users me-2"></i> Kelola Komunitas
                    </a>
                    <a class="nav-link" href="members.php">
                        <i class="fas fa-user-friends me-2"></i> Kelola Member
                    </a>
                    <a class="nav-link" href="profile.php">
                        <i class="fas fa-user me-2"></i> Profil
                    </a>
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="p-4">
                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($action === 'list'): ?>
                        <!-- Communities List -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>Kelola Komunitas</h2>
                            <a href="?action=create" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Buat Komunitas Baru
                            </a>
                        </div>
                        
                        <div class="row">
                            <?php foreach ($communities as $community): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card community-card h-100">
                                        <?php if ($community['bg_picture']): ?>
                                            <img src="<?php echo htmlspecialchars($community['bg_picture']); ?>" 
                                                 class="card-img-top" style="height: 200px; object-fit: cover;">
                                        <?php endif; ?>
                                        
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <?php if ($community['profile_picture']): ?>
                                                    <img src="<?php echo htmlspecialchars($community['profile_picture']); ?>" 
                                                         class="rounded-circle me-3" width="50" height="50" style="object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-primary rounded-circle me-3 d-flex align-items-center justify-content-center" 
                                                         style="width: 50px; height: 50px;">
                                                        <i class="fas fa-users text-white"></i>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div>
                                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($community['name']); ?></h5>
                                                    <small class="text-muted"><?php echo htmlspecialchars($community['category_name']); ?></small>
                                                </div>
                                            </div>
                                            
                                            <p class="card-text"><?php echo htmlspecialchars(substr($community['description'], 0, 100)); ?>...</p>
                                            
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="fas fa-users me-1"></i><?php echo $community['member_count']; ?> member
                                                </small>
                                                
                                                <div class="btn-group">
                                                    <a href="?action=edit&id=<?php echo $community['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?action=delete&id=<?php echo $community['id']; ?>" 
                                                       class="btn btn-sm btn-outline-danger"
                                                       onclick="return confirm('Yakin ingin menghapus komunitas ini?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                    <?php elseif ($action === 'create' || $action === 'edit'): ?>
                        <!-- Create/Edit Form -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2><?php echo $action === 'create' ? 'Buat Komunitas Baru' : 'Edit Komunitas'; ?></h2>
                            <a href="communities.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Kembali
                            </a>
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="card">
                                    <div class="card-body">
                                        <form method="POST" enctype="multipart/form-data">
                                            <div class="mb-3">
                                                <label for="name" class="form-label">Nama Komunitas *</label>
                                                <input type="text" class="form-control" id="name" name="name" 
                                                       value="<?php echo $action === 'edit' ? htmlspecialchars($community['name']) : ''; ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="category_id" class="form-label">Kategori *</label>
                                                <select class="form-select" id="category_id" name="category_id" required>
                                                    <option value="">Pilih Kategori</option>
                                                    <?php foreach ($categories as $category): ?>
                                                        <option value="<?php echo $category['id']; ?>" 
                                                                <?php echo ($action === 'edit' && $community['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($category['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="description" class="form-label">Deskripsi</label>
                                                <textarea class="form-control" id="description" name="description" rows="4"><?php echo $action === 'edit' ? htmlspecialchars($community['description']) : ''; ?></textarea>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="profile_picture" class="form-label">Foto Profil</label>
                                                <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                                                <?php if ($action === 'edit' && $community['profile_picture']): ?>
                                                    <div class="mt-2">
                                                        <small class="text-muted">Foto saat ini:</small><br>
                                                        <img src="<?php echo htmlspecialchars($community['profile_picture']); ?>" 
                                                             class="img-thumbnail" width="100">
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="bg_picture" class="form-label">Foto Background</label>
                                                <input type="file" class="form-control" id="bg_picture" name="bg_picture" accept="image/*">
                                                <?php if ($action === 'edit' && $community['bg_picture']): ?>
                                                    <div class="mt-2">
                                                        <small class="text-muted">Background saat ini:</small><br>
                                                        <img src="<?php echo htmlspecialchars($community['bg_picture']); ?>" 
                                                             class="img-thumbnail" width="200">
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save me-2"></i><?php echo $action === 'create' ? 'Buat Komunitas' : 'Update Komunitas'; ?>
                                                </button>
                                                <a href="communities.php" class="btn btn-secondary">Batal</a>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>