<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
if (!$user || $user['role'] !== 'event_provider') {
    header('Location: dashboard.php');
    exit();
}

$message = '';
$error = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['booking_id'])) {
    $booking_id = $_POST['booking_id'];
    $action = $_POST['action'];
    
    try {
        // Verify the booking belongs to one of the provider's events
        $stmt = $pdo->prepare("
            SELECT eb.* 
            FROM event_bookings eb
            JOIN events e ON eb.event_id = e.id
            WHERE eb.id = ? AND e.provider_id = ?
        ");
        $stmt->execute([$booking_id, $user['id']]);
        $booking = $stmt->fetch();
        
        if ($booking) {
            if ($action === 'verify') {
                $stmt = $pdo->prepare("UPDATE event_bookings SET status = 'paid' WHERE id = ?");
                $stmt->execute([$booking_id]);
                $message = 'Booking berhasil diverifikasi!';
            } elseif ($action === 'reject') {
                $stmt = $pdo->prepare("UPDATE event_bookings SET status = 'rejected' WHERE id = ?");
                $stmt->execute([$booking_id]);
                $message = 'Booking berhasil ditolak!';
            }
        } else {
            $error = 'Booking tidak ditemukan!';
        }
    } catch (PDOException $e) {
        $error = 'Terjadi kesalahan: ' . $e->getMessage();
    }
}

// Get all bookings for provider's events
$stmt = $pdo->prepare("
    SELECT eb.*, e.name as event_name, e.start_date, e.end_date,
           u.full_name as user_name, u.email as user_email,
           t.payment_proof
    FROM event_bookings eb
    JOIN events e ON eb.event_id = e.id
    JOIN users u ON eb.user_id = u.id
    LEFT JOIN transactions t ON eb.transaction_id = t.id
    WHERE e.provider_id = ?
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
    <title>Verifikasi Booking - ConnectVerse</title>
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
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .booking-card {
            transition: transform 0.2s;
        }
        .booking-card:hover {
            transform: translateY(-5px);
        }
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .payment-proof {
            max-width: 200px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3 text-white">
                    <h4><i class="fas fa-calendar-alt"></i> ConnectVerse</h4>
                    <small>Dashboard Event Provider</small>
                </div>
                
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a class="nav-link" href="dashboard_event.php">
                        <i class="fas fa-calendar-alt me-2"></i> Kelola Event
                    </a>
                    <a class="nav-link active" href="bookings.php">
                        <i class="fas fa-ticket-alt me-2"></i> Verifikasi Booking
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

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Verifikasi Booking</h2>
                    </div>

                    <div class="row">
                        <?php if (empty($bookings)): ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    Belum ada booking yang perlu diverifikasi.
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card booking-card h-100">
                                        <div class="card-body">
                                            <span class="badge <?php 
                                                echo match($booking['status']) {
                                                    'pending' => 'bg-warning',
                                                    'paid' => 'bg-success',
                                                    'cancelled' => 'bg-danger',
                                                    'rejected' => 'bg-secondary',
                                                    default => 'bg-primary'
                                                };
                                            ?> status-badge">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>

                                            <h5 class="card-title mb-3"><?php echo htmlspecialchars($booking['event_name']); ?></h5>
                                            
                                            <div class="mb-3">
                                                <p class="mb-1">
                                                    <i class="fas fa-user me-2"></i>
                                                    <?php echo htmlspecialchars($booking['user_name']); ?>
                                                </p>
                                                <p class="mb-1">
                                                    <i class="fas fa-envelope me-2"></i>
                                                    <?php echo htmlspecialchars($booking['user_email']); ?>
                                                </p>
                                                <p class="mb-1">
                                                    <i class="fas fa-calendar me-2"></i>
                                                    <?php echo date('d M Y H:i', strtotime($booking['booking_date'])); ?>
                                                </p>
                                                <p class="mb-1">
                                                    <i class="fas fa-money-bill-wave me-2"></i>
                                                    Rp <?php echo number_format($booking['total_price'], 0, ',', '.'); ?>
                                                </p>
                                            </div>

                                            <?php if ($booking['payment_proof']): ?>
                                                <div class="mb-3">
                                                    <label class="form-label">Bukti Pembayaran:</label>
                                                    <img src="<?php echo htmlspecialchars($booking['payment_proof']); ?>" 
                                                         class="img-thumbnail payment-proof" 
                                                         data-bs-toggle="modal" 
                                                         data-bs-target="#paymentModal<?php echo $booking['id']; ?>">
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($booking['status'] === 'pending'): ?>
                                                <div class="d-flex gap-2">
                                                    <form method="POST" class="flex-grow-1">
                                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                        <input type="hidden" name="action" value="verify">
                                                        <button type="submit" class="btn btn-success w-100">
                                                            <i class="fas fa-check me-2"></i>Verifikasi
                                                        </button>
                                                    </form>
                                                    <form method="POST" class="flex-grow-1">
                                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                        <input type="hidden" name="action" value="reject">
                                                        <button type="submit" class="btn btn-danger w-100">
                                                            <i class="fas fa-times me-2"></i>Tolak
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Proof Modal -->
                                <?php if ($booking['payment_proof']): ?>
                                    <div class="modal fade" id="paymentModal<?php echo $booking['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Bukti Pembayaran</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body text-center">
                                                    <img src="<?php echo htmlspecialchars($booking['payment_proof']); ?>" 
                                                         class="img-fluid">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 