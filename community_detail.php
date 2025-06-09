<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$community_id = $_GET['id'] ?? 0;

// Get community details
$stmt = $pdo->prepare("
    SELECT c.*, cat.name as category_name, u.full_name as admin_name,
           (SELECT COUNT(*) FROM community_members cm WHERE cm.community_id = c.id) as member_count
    FROM communities c 
    LEFT JOIN categories cat ON c.category_id = cat.id 
    LEFT JOIN users u ON c.admin_id = u.id 
    WHERE c.id = ?
");
$stmt->execute([$community_id]);
$community = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$community) {
    header('Location: communities.php');
    exit;
}

// Check if user is a member
$stmt = $pdo->prepare("SELECT * FROM community_members WHERE community_id = ? AND user_id = ?");
$stmt->execute([$community_id, $user['id']]);
$is_member = $stmt->rowCount() > 0;

// Handle join/leave community
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'join') {
                $stmt = $pdo->prepare("INSERT INTO community_members (community_id, user_id) VALUES (?, ?)");
                $stmt->execute([$community_id, $user['id']]);
                $is_member = true;
            } elseif ($_POST['action'] === 'leave') {
                $stmt = $pdo->prepare("DELETE FROM community_members WHERE community_id = ? AND user_id = ?");
                $stmt->execute([$community_id, $user['id']]);
                $is_member = false;
            }
            // Refresh member count
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM community_members WHERE community_id = ?");
            $stmt->execute([$community_id]);
            $community['member_count'] = $stmt->fetchColumn();
        } catch (Exception $e) {
            $error_message = "Terjadi kesalahan. Silakan coba lagi.";
        }
    }
}

// Get recent members
$stmt = $pdo->prepare("
    SELECT u.*, cm.joined_at 
    FROM community_members cm 
    JOIN users u ON cm.user_id = u.id 
    WHERE cm.community_id = ? 
    ORDER BY cm.joined_at DESC 
    LIMIT 5
");
$stmt->execute([$community_id]);
$recent_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($community['name']); ?> - ConnectVerse</title>
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
        
        .community-header {
            background: var(--dark-bg);
            color: white;
            padding: 3rem 0;
            position: relative;
            overflow: hidden;
        }
        
        .community-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('<?php echo htmlspecialchars($community['bg_picture'] ?? 'assets/img/default-community-bg.jpg'); ?>') center/cover;
            opacity: 0.3;
        }
        
        .community-info {
            position: relative;
            z-index: 1;
        }
        
        .community-logo {
            width: 120px;
            height: 120px;
            border-radius: 15px;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .card {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        
        .member-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .badge-members {
            background: var(--primary-red);
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

    <!-- Community Header -->
    <section class="community-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <img src="<?php echo htmlspecialchars($community['profile_picture'] ?? 'assets/img/default-community.png'); ?>" 
                         alt="Community Logo" class="community-logo">
                </div>
                <div class="col-md-10 community-info">
                    <h1 class="display-6 fw-bold mb-2"><?php echo htmlspecialchars($community['name']); ?></h1>
                    <p class="lead mb-3"><?php echo htmlspecialchars($community['description']); ?></p>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge badge-members">
                            <i class="fas fa-users me-1"></i> <?php echo $community['member_count']; ?> Anggota
                        </span>
                        <span class="badge bg-secondary">
                            <i class="fas fa-tag me-1"></i> <?php echo htmlspecialchars($community['category_name']); ?>
                        </span>
                        <span class="text-white">
                            <i class="fas fa-user-shield me-1"></i> Admin: <?php echo htmlspecialchars($community['admin_name']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container my-5">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Community Description -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Tentang Komunitas</h5>
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($community['description'])); ?></p>
                    </div>
                </div>

                <!-- Community Posts (Placeholder) -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Diskusi Terbaru</h5>
                        <div class="text-center py-5">
                            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Belum ada diskusi dalam komunitas ini</p>
                            <?php if ($is_member): ?>
                                <button class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Buat Diskusi
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Join/Leave Button -->
                <div class="card">
                    <div class="card-body">
                        <?php if ($is_member): ?>
                            <form method="POST" class="d-grid">
                                <input type="hidden" name="action" value="leave">
                                <button type="submit" class="btn btn-outline-danger">
                                    <i class="fas fa-sign-out-alt me-2"></i>Keluar Komunitas
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" class="d-grid">
                                <input type="hidden" name="action" value="join">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>Gabung Komunitas
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Members -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Anggota Terbaru</h5>
                        <?php if (empty($recent_members)): ?>
                            <p class="text-muted text-center">Belum ada anggota</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recent_members as $member): ?>
                                    <div class="list-group-item px-0 d-flex align-items-center">
                                        <img src="<?php echo $member['profile_picture'] ?? 'assets/img/default-profile.png'; ?>" 
                                             alt="Member Avatar" class="member-avatar me-3">
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($member['full_name']); ?></h6>
                                            <small class="text-muted">
                                                Bergabung <?php echo date('d M Y', strtotime($member['joined_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Community Rules -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Aturan Komunitas</h5>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Hormati sesama anggota
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Jangan spam atau promosi berlebihan
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Gunakan bahasa yang sopan
                            </li>
                            <li>
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Tetap fokus pada topik komunitas
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 