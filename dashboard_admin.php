<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
if (!$user || !in_array($user['role'], ['community_admin', 'event_provider'])) {
    header('Location: index.php');
    exit();
}

// Get statistics
$stats = [];

if ($user['role'] === 'community_admin') {
    // Community statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_communities FROM communities WHERE admin_id = ?");
    $stmt->execute([$user['id']]);
    $stats['communities'] = $stmt->fetch()['total_communities'];
    
    $stmt = $pdo->prepare("
        SELECT COUNT(cm.id) as total_members 
        FROM community_members cm 
        JOIN communities c ON cm.community_id = c.id 
        WHERE c.admin_id = ?
    ");
    $stmt->execute([$user['id']]);
    $stats['total_members'] = $stmt->fetch()['total_members'];
}

if ($user['role'] === 'event_provider') {
    // Event statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_events FROM events WHERE provider_id = ?");
    $stmt->execute([$user['id']]);
    $stats['events'] = $stmt->fetch()['total_events'];
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_bookings 
        FROM event_bookings eb 
        JOIN events e ON eb.event_id = e.id 
        WHERE e.provider_id = ?
    ");
    $stmt->execute([$user['id']]);
    $stats['total_bookings'] = $stmt->fetch()['total_bookings'];
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as pending_bookings 
        FROM event_bookings eb 
        JOIN events e ON eb.event_id = e.id 
        WHERE e.provider_id = ? AND eb.status = 'pending'
    ");
    $stmt->execute([$user['id']]);
    $stats['pending_bookings'] = $stmt->fetch()['pending_bookings'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ConnectVerse</title>
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
            border-radius: 0;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        .card-stats {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border: none;
            border-radius: 15px;
        }
        .card-stats-2 {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            border: none;
            border-radius: 15px;
        }
        .card-stats-3 {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
            border: none;
            border-radius: 15px;
        }
        .card-stats-4 {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
            border: none;
            border-radius: 15px;
        }
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
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
                    <a class="nav-link active" href="dashboard_admin.php">
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
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2>Dashboard</h2>
                            <p class="text-muted">Selamat datang, <?php echo htmlspecialchars($user['full_name']); ?>!</p>
                        </div>
                        <div class="text-end">
                            <small class="text-muted"><?php echo date('d M Y, H:i'); ?></small>
                        </div>
                    </div>
                    
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <?php if ($user['role'] === 'community_admin'): ?>
                            <div class="col-md-6 col-lg-3 mb-3">
                                <div class="card card-stats">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h3 class="mb-0"><?php echo $stats['communities']; ?></h3>
                                                <p class="mb-0">Total Komunitas</p>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-users fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 col-lg-3 mb-3">
                                <div class="card card-stats-2">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h3 class="mb-0"><?php echo $stats['total_members']; ?></h3>
                                                <p class="mb-0">Total Member</p>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-user-friends fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($user['role'] === 'event_provider'): ?>
                            <div class="col-md-6 col-lg-3 mb-3">
                                <div class="card card-stats">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h3 class="mb-0"><?php echo $stats['events']; ?></h3>
                                                <p class="mb-0">Total Event</p>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-calendar-alt fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 col-lg-3 mb-3">
                                <div class="card card-stats-2">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h3 class="mb-0"><?php echo $stats['total_bookings']; ?></h3>
                                                <p class="mb-0">Total Booking</p>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-ticket-alt fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 col-lg-3 mb-3">
                                <div class="card card-stats-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h3 class="mb-0"><?php echo $stats['pending_bookings']; ?></h3>
                                                <p class="mb-0">Booking Pending</p>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-clock fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Aksi Cepat</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php if ($user['role'] === 'community_admin'): ?>
                                            <div class="col-md-6 col-lg-3 mb-3">
                                                <a href="communities.php?action=create" class="btn btn-primary w-100">
                                                    <i class="fas fa-plus me-2"></i>Buat Komunitas Baru
                                                </a>
                                            </div>
                                            <div class="col-md-6 col-lg-3 mb-3">
                                                <a href="members.php" class="btn btn-info w-100">
                                                    <i class="fas fa-users me-2"></i>Kelola Member
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($user['role'] === 'event_provider'): ?>
                                            <div class="col-md-6 col-lg-3 mb-3">
                                                <a href="events.php?action=create" class="btn btn-success w-100">
                                                    <i class="fas fa-plus me-2"></i>Buat Event Baru
                                                </a>
                                            </div>
                                            <div class="col-md-6 col-lg-3 mb-3">
                                                <a href="bookings.php" class="btn btn-warning w-100">
                                                    <i class="fas fa-check me-2"></i>Verifikasi Booking
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
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