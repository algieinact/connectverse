<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$booking_id = $_GET['booking_id'] ?? 0;

// Get booking details
$stmt = $pdo->prepare("
    SELECT eb.*, e.name as event_name, e.price, e.profile_picture
    FROM event_bookings eb
    JOIN events e ON eb.event_id = e.id
    WHERE eb.id = ? AND eb.user_id = ?
");
$stmt->execute([$booking_id, $user['id']]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: events.php');
    exit;
}

$error = '';
$success = '';

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Handle file upload
        if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Bukti pembayaran harus diupload!');
        }

        $payment_proof = uploadImage($_FILES['payment_proof'], 'uploads/payments/');

        // Create transaction record
        $stmt = $pdo->prepare("
            INSERT INTO transactions (user_id, event_id, amount, payment_method, payment_proof)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user['id'],
            $booking['event_id'],
            $booking['price'],
            $_POST['payment_method'],
            $payment_proof
        ]);
        $transaction_id = $pdo->lastInsertId();

        // Update booking with transaction ID
        $stmt = $pdo->prepare("
            UPDATE event_bookings 
            SET transaction_id = ?, status = 'pending'
            WHERE id = ?
        ");
        $stmt->execute([$transaction_id, $booking_id]);

        $success = 'Bukti pembayaran berhasil diupload! Silakan tunggu verifikasi dari penyedia event.';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - ConnectVerse</title>
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
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .event-image {
        width: 100px;
        height: 100px;
        border-radius: 10px;
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
                            <li><a class="dropdown-item" href="my_activities.php">Aktivitas Saya</a></li>
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="my_activities.php">Aktivitas Saya</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
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
            <h1 class="display-6 fw-bold mb-0">Pembayaran Event</h1>
            <p class="lead">Lengkapi pembayaran untuk menyelesaikan booking</p>
        </div>
    </section>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-4">
                            <?php if ($booking['profile_picture']): ?>
                            <img src="<?php echo htmlspecialchars($booking['profile_picture']); ?>" alt="Event Image"
                                class="event-image me-3">
                            <?php else: ?>
                            <div class="event-image me-3 bg-light d-flex align-items-center justify-content-center">
                                <i class="fas fa-calendar fa-2x text-muted"></i>
                            </div>
                            <?php endif; ?>
                            <div>
                                <h5 class="mb-1"><?php echo htmlspecialchars($booking['event_name']); ?></h5>
                                <p class="text-muted mb-0">
                                    Total Pembayaran: Rp <?php echo number_format($booking['price'], 0, ',', '.'); ?>
                                </p>
                            </div>
                        </div>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Metode Pembayaran</label>
                                <select class="form-select" name="payment_method" required>
                                    <option value="">Pilih Metode Pembayaran</option>
                                    <option value="transfer">Transfer Bank</option>
                                    <option value="ewallet">E-Wallet</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Bukti Pembayaran</label>
                                <input type="file" class="form-control" name="payment_proof" accept="image/*" required>
                                <small class="text-muted">
                                    Upload bukti transfer atau screenshot pembayaran
                                </small>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-upload me-2"></i>Upload Bukti Pembayaran
                                </button>
                                <a href="my_activities.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Kembali ke Aktivitas
                                </a>
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