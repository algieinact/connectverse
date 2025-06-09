<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();

// Check if user has the correct role
if ($user['role'] !== 'user') {
    header('Location: index.php');
    exit();
}

// Get recent communities
$stmt = $pdo->prepare("
    SELECT c.*, cat.name as category_name, u.full_name as admin_name
    FROM communities c 
    LEFT JOIN categories cat ON c.category_id = cat.id 
    LEFT JOIN users u ON c.admin_id = u.id 
    ORDER BY c.created_at DESC LIMIT 6
");
$stmt->execute();
$recent_communities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get upcoming events
$stmt = $pdo->prepare("
    SELECT e.*, cat.name as category_name, u.full_name as provider_name
    FROM events e 
    LEFT JOIN categories cat ON e.category_id = cat.id 
    LEFT JOIN users u ON e.provider_id = u.id 
    WHERE e.start_date >= NOW()
    ORDER BY e.start_date ASC LIMIT 6
");
$stmt->execute();
$upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ConnectVerse</title>
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
            transition: transform 0.2s;
        }
        
        .card:hover {
            transform: translateY(-2px);
        }
        
        .community-card, .event-card {
            height: 300px;
            position: relative;
            overflow: hidden;
        }
        
        .card-img-top {
            height: 150px;
            object-fit: cover;
        }
        
        .stats-card {
            background: linear-gradient(135deg, var(--primary-red), #c82333);
            color: white;
        }
        
        .welcome-section {
            background: linear-gradient(135deg, var(--dark-bg), var(--primary-red));
            color: white;
            padding: 3rem 0;
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

    <!-- Welcome Section -->
    <section class="welcome-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 fw-bold">Selamat Datang, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
                    <p class="lead">Temukan komunitas dan event yang sesuai dengan minat Anda</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-grid gap-2 d-md-block">
                        <a href="communities.php" class="btn btn-light btn-lg">
                            <i class="fas fa-search"></i> Cari Komunitas
                        </a>
                        <a href="events.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-calendar"></i> Cari Event
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container my-5">
        <!-- Quick Actions -->
        <div class="row mb-5">
            <div class="col-md-4">
                <div class="card stats-card text-center p-4">
                    <i class="fas fa-users fa-3x mb-3"></i>
                    <h4>Komunitas Terbaru</h4>
                    <a href="communities.php" class="btn btn-light mt-2">Lihat Semua</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card text-center p-4">
                    <i class="fas fa-calendar-alt fa-3x mb-3"></i>
                    <h4>Event Mendatang</h4>
                    <a href="events.php" class="btn btn-light mt-2">Lihat Semua</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card text-center p-4">
                    <i class="fas fa-plus fa-3x mb-3"></i>
                    <h4>Buat Konten</h4>
                    <?php if ($user['role'] === 'community_admin'): ?>
                        <a href="create_community.php" class="btn btn-light mt-2">Buat Komunitas</a>
                    <?php elseif ($user['role'] === 'event_provider'): ?>
                        <a href="create_event.php" class="btn btn-light mt-2">Buat Event</a>
                    <?php else: ?>
                        <small class="text-light">Upgrade akun untuk membuat konten</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Communities -->
        <section class="mb-5">
            <h2 class="mb-4">Komunitas Terbaru</h2>
            <div class="row">
                <?php foreach ($recent_communities as $community): ?>
                <div class="col-md-4 mb-4">
                    <div class="card community-card">
                        <?php if ($community['profile_picture']): ?>
                            <img src="<?php echo htmlspecialchars($community['profile_picture']); ?>" class="card-img-top" alt="Community Image">
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center">
                                <i class="fas fa-users fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($community['name']); ?></h5>
                            <p class="card-text">
                                <small class="text-muted">
                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($community['category_name']); ?>
                                </small>
                            </p>
                            <p class="card-text"><?php echo htmlspecialchars(substr($community['description'], 0, 100)); ?>...</p>
                            <a href="community_detail.php?id=<?php echo $community['id']; ?>" class="btn btn-primary btn-sm">Lihat Detail</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Upcoming Events -->
        <section>
            <h2 class="mb-4">Event Mendatang</h2>
            <div class="row">
                <?php foreach ($upcoming_events as $event): ?>
                <div class="col-md-4 mb-4">
                    <div class="card event-card">
                        <?php if ($event['profile_picture']): ?>
                            <img src="<?php echo htmlspecialchars($event['profile_picture']); ?>" class="card-img-top" alt="Event Image">
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center">
                                <i class="fas fa-calendar fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($event['name']); ?></h5>
                            <p class="card-text">
                                <small class="text-muted">
                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($event['category_name']); ?>
                                </small>
                            </p>
                            <p class="card-text">
                                <i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($event['start_date'])); ?>
                            </p>
                            <p class="card-text">
                                <i class="fas fa-money-bill"></i> 
                                <?php echo $event['price'] > 0 ? 'Rp ' . number_format($event['price'], 0, ',', '.') : 'Gratis'; ?>
                            </p>
                            <a href="event_detail.php?id=<?php echo $event['id']; ?>" class="btn btn-primary btn-sm">Lihat Detail</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>