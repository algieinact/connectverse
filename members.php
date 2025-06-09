<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
if (!$user || $user['role'] !== 'community_admin') {
    header('Location: dashboard_admin.php');
    exit();
}

$action = $_GET['action'] ?? 'list';
$community_id = $_GET['community_id'] ?? null;
$member_id = $_GET['member_id'] ?? null;
$message = '';
$error = '';

// Get all communities owned by the admin
$stmt = $pdo->prepare("SELECT * FROM communities WHERE admin_id = ? ORDER BY name");
$stmt->execute([$user['id']]);
$communities = $stmt->fetchAll();

// Handle actions
if ($_POST) {
    if ($action === 'add') {
        $community_id = $_POST['community_id'];
        $email = trim($_POST['email']);
        
        if (empty($email)) {
            $error = 'Email harus diisi!';
        } else {
            try {
                // Check if user exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user_to_add = $stmt->fetch();
                
                if (!$user_to_add) {
                    $error = 'User dengan email tersebut tidak ditemukan!';
                } else {
                    // Check if already a member
                    $stmt = $pdo->prepare("SELECT id FROM community_members WHERE community_id = ? AND user_id = ?");
                    $stmt->execute([$community_id, $user_to_add['id']]);
                    if ($stmt->fetch()) {
                        $error = 'User sudah menjadi member komunitas ini!';
                    } else {
                        // Add member
                        $stmt = $pdo->prepare("INSERT INTO community_members (community_id, user_id) VALUES (?, ?)");
                        $stmt->execute([$community_id, $user_to_add['id']]);
                        $message = 'Member berhasil ditambahkan!';
                    }
                }
            } catch (PDOException $e) {
                $error = 'Terjadi kesalahan: ' . $e->getMessage();
            }
        }
    }
}

// Handle remove member
if ($action === 'remove' && $member_id && $community_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM community_members WHERE id = ? AND community_id = ?");
        $stmt->execute([$member_id, $community_id]);
        $message = 'Member berhasil dihapus!';
    } catch (PDOException $e) {
        $error = 'Gagal menghapus member: ' . $e->getMessage();
    }
}

// Get members list
if ($action === 'list' && $community_id) {
    $stmt = $pdo->prepare("
        SELECT cm.*, u.full_name, u.email
        FROM community_members cm
        JOIN users u ON cm.user_id = u.id
        WHERE cm.community_id = ?
        ORDER BY u.full_name
    ");
    $stmt->execute([$community_id]);
    $members = $stmt->fetchAll();
    
    // Get community info
    $stmt = $pdo->prepare("SELECT * FROM communities WHERE id = ? AND admin_id = ?");
    $stmt->execute([$community_id, $user['id']]);
    $community = $stmt->fetch();
    
    if (!$community) {
        $error = 'Komunitas tidak ditemukan!';
        $action = 'select';
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Member - ConnectVerse</title>
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

    .member-card {
        transition: transform 0.2s;
    }

    .member-card:hover {
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
                    <a class="nav-link" href="dashboard_admin.php">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a class="nav-link" href="dashboard_community.php">
                        <i class="fas fa-users me-2"></i> Kelola Komunitas
                    </a>
                    <a class="nav-link active" href="members.php">
                        <i class="fas fa-user-friends me-2"></i> Kelola Member
                    </a>
                    <!-- <a class="nav-link" href="profile.php">
                        <i class="fas fa-user me-2"></i> Profil
                    </a> -->
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

                    <?php if ($action === 'select' || !$community_id): ?>
                    <!-- Select Community -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Pilih Komunitas</h2>
                    </div>

                    <div class="row">
                        <?php foreach ($communities as $comm): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card member-card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <?php if ($comm['profile_picture']): ?>
                                        <img src="<?php echo htmlspecialchars($comm['profile_picture']); ?>"
                                            class="rounded-circle me-3" width="50" height="50"
                                            style="object-fit: cover;">
                                        <?php else: ?>
                                        <div class="bg-primary rounded-circle me-3 d-flex align-items-center justify-content-center"
                                            style="width: 50px; height: 50px;">
                                            <i class="fas fa-users text-white"></i>
                                        </div>
                                        <?php endif; ?>

                                        <div>
                                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($comm['name']); ?>
                                            </h5>
                                        </div>
                                    </div>

                                    <a href="?action=list&community_id=<?php echo $comm['id']; ?>"
                                        class="btn btn-primary w-100">
                                        <i class="fas fa-users me-2"></i>Kelola Member
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php elseif ($action === 'list'): ?>
                    <!-- Members List -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2>Kelola Member</h2>
                            <p class="text-muted mb-0">
                                Komunitas: <?php echo htmlspecialchars($community['name']); ?>
                            </p>
                        </div>
                        <div>
                            <a href="?action=select" class="btn btn-secondary me-2">
                                <i class="fas fa-arrow-left me-2"></i>Kembali
                            </a>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#addMemberModal">
                                <i class="fas fa-plus me-2"></i>Tambah Member
                            </button>
                        </div>
                    </div>

                    <div class="row">
                        <?php foreach ($members as $member): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card member-card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-primary rounded-circle me-3 d-flex align-items-center justify-content-center"
                                            style="width: 50px; height: 50px;">
                                            <i class="fas fa-user text-white"></i>
                                        </div>

                                        <div>
                                            <h5 class="card-title mb-0">
                                                <?php echo htmlspecialchars($member['full_name']); ?></h5>
                                            <small
                                                class="text-muted"><?php echo htmlspecialchars($member['email']); ?></small>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <a href="?action=remove&community_id=<?php echo $community_id; ?>&member_id=<?php echo $member['id']; ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Yakin ingin menghapus member ini?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Add Member Modal -->
                    <div class="modal fade" id="addMemberModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Tambah Member</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="?action=add">
                                    <div class="modal-body">
                                        <input type="hidden" name="community_id" value="<?php echo $community_id; ?>">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email User</label>
                                            <input type="email" class="form-control" id="email" name="email" required>
                                            <small class="text-muted">Masukkan email user yang ingin ditambahkan sebagai
                                                member</small>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary">Tambah Member</button>
                                    </div>
                                </form>
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