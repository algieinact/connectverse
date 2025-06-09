<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();

// Get user's joined communities
$stmt = $pdo->prepare("
    SELECT c.*, cat.name as category_name, 
           (SELECT COUNT(*) FROM community_members cm WHERE cm.community_id = c.id) as member_count
    FROM communities c
    LEFT JOIN categories cat ON c.category_id = cat.id
    JOIN community_members cm ON c.id = cm.community_id
    WHERE cm.user_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$user['id']]);
$communities = $stmt->fetchAll();

// Get user's event bookings
$stmt = $pdo->prepare("
    SELECT eb.*, e.name as event_name, e.start_date, e.end_date, e.profile_picture,
           t.status as transaction_status, t.payment_method, t.payment_proof
    FROM event_bookings eb
    JOIN events e ON eb.event_id = e.id
    LEFT JOIN transactions t ON eb.transaction_id = t.id
    WHERE eb.user_id = ?
    ORDER BY eb.booking_date DESC
");
$stmt->execute([$user['id']]);
$bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivitas Saya - ConnectVerse</title>
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
        
        .page-header {
            background: var(--dark-bg);
            color: white;
            padding: 2rem 0;
        }
        
        .card {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        
        .community-logo {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            object-fit: cover;
        }
        
        .event-image {
            width: 100px;
            height: 100px;
            border-radius: 10px;
            object-fit: cover;
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 0.5rem 1rem;
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
                            <li><a class="dropdown-item" href="my_activities.php">Aktivitas Saya</a></li>
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="display-6 fw-bold mb-0">Aktivitas Saya</h1>
            <p class="lead">Lihat komunitas yang Anda ikuti dan status booking event</p>
        </div>
    </section>

    <div class="container my-5">
        <div class="row">
            <!-- Communities -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Komunitas Saya</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($communities)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Anda belum bergabung dengan komunitas apapun</p>
                                <a href="communities.php" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Jelajahi Komunitas
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($communities as $community): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center mb-3">
                                                    <?php if ($community['profile_picture']): ?>
                                                        <img src="<?php echo htmlspecialchars($community['profile_picture']); ?>" 
                                                             alt="Community Logo" class="community-logo me-3">
                                                    <?php else: ?>
                                                        <div class="bg-primary community-logo me-3 d-flex align-items-center justify-content-center">
                                                            <i class="fas fa-users text-white"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <h6 class="card-title mb-1"><?php echo htmlspecialchars($community['name']); ?></h6>
                                                        <small class="text-muted"><?php echo htmlspecialchars($community['category_name']); ?></small>
                                                    </div>
                                                </div>
                                                <p class="card-text small"><?php echo htmlspecialchars(substr($community['description'], 0, 100)); ?>...</p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="fas fa-users me-1"></i><?php echo $community['member_count']; ?> member
                                                    </small>
                                                    <a href="community_detail.php?id=<?php echo $community['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                        Lihat Detail
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Event Bookings -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Booking Event</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($bookings)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Anda belum melakukan booking event apapun</p>
                                <a href="events.php" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Jelajahi Event
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($bookings as $booking): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex">
                                            <?php if ($booking['profile_picture']): ?>
                                                <img src="<?php echo htmlspecialchars($booking['profile_picture']); ?>" 
                                                     alt="Event Image" class="event-image me-3">
                                            <?php else: ?>
                                                <div class="event-image me-3 bg-light d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-calendar fa-2x text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($booking['event_name']); ?></h6>
                                                <small class="text-muted d-block mb-2">
                                                    <?php echo date('d M Y H:i', strtotime($booking['start_date'])); ?>
                                                </small>
                                                
                                                <?php if ($booking['status'] === 'pending'): ?>
                                                    <?php if ($booking['transaction_status'] === 'pending'): ?>
                                                        <div class="alert alert-warning py-2 mb-2">
                                                            <small>
                                                                <i class="fas fa-info-circle me-1"></i>
                                                                Upload bukti pembayaran untuk menyelesaikan booking
                                                            </small>
                                                        </div>
                                                        <button type="button" class="btn btn-sm btn-primary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#uploadPaymentModal<?php echo $booking['id']; ?>">
                                                            <i class="fas fa-upload me-1"></i>Upload Bukti
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning status-badge">Menunggu Verifikasi</span>
                                                    <?php endif; ?>
                                                <?php elseif ($booking['status'] === 'paid'): ?>
                                                    <span class="badge bg-success status-badge">Booking Diterima</span>
                                                <?php elseif ($booking['status'] === 'cancelled'): ?>
                                                    <span class="badge bg-danger status-badge">Dibatalkan</span>
                                                <?php elseif ($booking['status'] === 'rejected'): ?>
                                                    <span class="badge bg-danger status-badge">Ditolak</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Upload Payment Modal -->
                                    <div class="modal fade" id="uploadPaymentModal<?php echo $booking['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Upload Bukti Pembayaran</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="upload_payment.php" method="POST" enctype="multipart/form-data">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Metode Pembayaran</label>
                                                            <select class="form-select" name="payment_method" required>
                                                                <option value="transfer">Transfer Bank</option>
                                                                <option value="ewallet">E-Wallet</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Bukti Pembayaran</label>
                                                            <input type="file" class="form-control" name="payment_proof" 
                                                                   accept="image/*" required>
                                                            <small class="text-muted">
                                                                Upload bukti transfer atau screenshot pembayaran
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" 
                                                                data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-primary">Upload</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>