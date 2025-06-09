<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
if (!$user || !in_array($user['role'], ['event_provider', 'community_admin'])) {
    header('Location: index.php');
    exit();
}

$message = '';
$error = '';

// Handle profile update
if ($_POST) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $bio = trim($_POST['bio']);
    
    if (empty($full_name) || empty($email)) {
        $error = 'Nama lengkap dan email harus diisi!';
    } else {
        try {
            // Check if email is already used by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user['id']]);
            if ($stmt->fetch()) {
                $error = 'Email sudah digunakan oleh user lain!';
            } else {
                // Handle profile picture upload
                $profile_picture = null;
                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                    $profile_picture = uploadImage($_FILES['profile_picture'], 'uploads/profiles/');
                }
                
                // Update profile
                $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, bio = ?";
                $params = [$full_name, $email, $phone, $address, $bio];
                
                if ($profile_picture) {
                    $sql .= ", profile_picture = ?";
                    $params[] = $profile_picture;
                }
                
                $sql .= " WHERE id = ?";
                $params[] = $user['id'];
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                // Update session
                $_SESSION['user']['full_name'] = $full_name;
                $_SESSION['user']['email'] = $email;
                if ($profile_picture) {
                    $_SESSION['user']['profile_picture'] = $profile_picture;
                }
                
                $message = 'Profil berhasil diupdate!';
                
                // Refresh user data
                $user = getCurrentUser();
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Semua field password harus diisi!';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Password baru dan konfirmasi password tidak cocok!';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password baru minimal 6 karakter!';
    } else {
        try {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $current_hash = $stmt->fetchColumn();
            
            if (!password_verify($current_password, $current_hash)) {
                $error = 'Password saat ini tidak valid!';
            } else {
                // Update password
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$new_hash, $user['id']]);
                
                $message = 'Password berhasil diubah!';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    .sidebar {
        min-height: 100vh;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .sidebar .nav-link {
        color: rgba(255, 255, 255, 0.8);
        padding: 15px 20px;
        transition: all 0.3s;
    }

    .sidebar .nav-link:hover,
    .sidebar .nav-link.active {
        color: white;
        background-color: rgba(255, 255, 255, 0.1);
        transform: translateX(5px);
    }

    .main-content {
        background-color: #f8f9fa;
        min-height: 100vh;
    }

    .profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem 0;
        margin-bottom: 2rem;
    }

    .profile-picture {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        border: 5px solid white;
        object-fit: cover;
    }

    .profile-picture-placeholder {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        border: 5px solid white;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 4rem;
        color: white;
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
                    <small>Dashboard <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></small>
                </div>

                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard_admin.php">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <?php if ($user['role'] === 'community_admin'): ?>
                    <a class="nav-link" href="dashboard_community.php">
                        <i class="fas fa-users me-2"></i> Kelola Komunitas
                    </a>
                    <a class="nav-link" href="members.php">
                        <i class="fas fa-user-friends me-2"></i> Kelola Member
                    </a>
                    <?php endif; ?>
                    <?php if ($user['role'] === 'event_provider'): ?>
                    <a class="nav-link" href="dashboard_event.php">
                        <i class="fas fa-calendar-alt me-2"></i> Kelola Event
                    </a>
                    <a class="nav-link" href="bookings.php">
                        <i class="fas fa-ticket-alt me-2"></i> Verifikasi Booking
                    </a>
                    <?php endif; ?>
                    <a class="nav-link active" href="admin_profile.php">
                        <i class="fas fa-user me-2"></i> Profil
                    </a>
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show m-3">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show m-3">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="container">
                        <div class="row align-items-center">
                            <div class="col-md-3 text-center">
                                <?php if ($user['profile_picture']): ?>
                                <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture"
                                    class="profile-picture">
                                <?php else: ?>
                                <div class="profile-picture-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-9">
                                <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                                <p class="mb-0">
                                    <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($user['email']); ?>
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-user-tag me-2"></i><?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container">
                    <div class="row">
                        <!-- Profile Information -->
                        <div class="col-md-8">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Informasi Profil</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="mb-3">
                                            <label for="full_name" class="form-label">Nama Lengkap *</label>
                                            <input type="text" class="form-control" id="full_name" name="full_name"
                                                value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email *</label>
                                            <input type="email" class="form-control" id="email" name="email"
                                                value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Nomor Telepon</label>
                                            <input type="tel" class="form-control" id="phone" name="phone"
                                                value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label for="address" class="form-label">Alamat</label>
                                            <textarea class="form-control" id="address" name="address"
                                                rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label for="bio" class="form-label">Bio</label>
                                            <textarea class="form-control" id="bio" name="bio"
                                                rows="4"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label for="profile_picture" class="form-label">Foto Profil</label>
                                            <input type="file" class="form-control" id="profile_picture"
                                                name="profile_picture" accept="image/*">
                                        </div>

                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Simpan Perubahan
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Change Password -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Ubah Password</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">Password Saat Ini</label>
                                            <input type="password" class="form-control" id="current_password"
                                                name="current_password" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">Password Baru</label>
                                            <input type="password" class="form-control" id="new_password"
                                                name="new_password" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Konfirmasi Password
                                                Baru</label>
                                            <input type="password" class="form-control" id="confirm_password"
                                                name="confirm_password" required>
                                        </div>

                                        <button type="submit" name="change_password" class="btn btn-warning">
                                            <i class="fas fa-key me-2"></i>Ubah Password
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html> 