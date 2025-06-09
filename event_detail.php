<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$event_id = $_GET['id'] ?? 0;

// Get event details
$stmt = $pdo->prepare("
    SELECT e.*, cat.name as category_name, u.full_name as provider_name,
           (SELECT COUNT(*) FROM event_bookings eb WHERE eb.event_id = e.id) as booking_count
    FROM events e 
    LEFT JOIN categories cat ON e.category_id = cat.id 
    LEFT JOIN users u ON e.provider_id = u.id 
    WHERE e.id = ?
");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header('Location: events.php');
    exit;
}

// Check if user has booked
$stmt = $pdo->prepare("SELECT * FROM event_bookings WHERE event_id = ? AND user_id = ?");
$stmt->execute([$event_id, $user['id']]);
$has_booked = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle booking/cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'book') {
                // Create booking
                $stmt = $pdo->prepare("INSERT INTO event_bookings (event_id, user_id, total_price, status) VALUES (?, ?, ?, 'pending')");
                $stmt->execute([$event_id, $user['id'], $event['price']]);
                $booking_id = $pdo->lastInsertId();
                
                // Redirect to payment page
                header('Location: payment.php?booking_id=' . $booking_id);
                exit;
            } elseif ($_POST['action'] === 'cancel') {
                $stmt = $pdo->prepare("UPDATE event_bookings SET status = 'cancelled' WHERE event_id = ? AND user_id = ?");
                $stmt->execute([$event_id, $user['id']]);
                $has_booked = false;
            }
            // Refresh booking count
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM event_bookings WHERE event_id = ?");
            $stmt->execute([$event_id]);
            $event['booking_count'] = $stmt->fetchColumn();
        } catch (Exception $e) {
            $error_message = "Terjadi kesalahan. Silakan coba lagi.";
        }
    }
}

// Get recent bookings
$stmt = $pdo->prepare("
    SELECT u.*, eb.booking_date 
    FROM event_bookings eb 
    JOIN users u ON eb.user_id = u.id 
    WHERE eb.event_id = ? 
    ORDER BY eb.booking_date DESC 
    LIMIT 5
");
$stmt->execute([$event_id]);
$recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format dates
$start_date = new DateTime($event['start_date']);
$end_date = new DateTime($event['end_date']);
$now = new DateTime();

// Determine event status
if ($now < $start_date) {
    $status = 'upcoming';
    $status_text = 'Akan Datang';
    $status_class = 'bg-primary';
} elseif ($now >= $start_date && $now <= $end_date) {
    $status = 'ongoing';
    $status_text = 'Sedang Berlangsung';
    $status_class = 'bg-success';
} else {
    $status = 'finished';
    $status_text = 'Selesai';
    $status_class = 'bg-secondary';
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['name']); ?> - ConnectVerse</title>
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

    .event-header {
        background: var(--dark-bg);
        color: white;
        padding: 3rem 0;
        position: relative;
        overflow: hidden;
    }

    .event-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('<?php echo htmlspecialchars($event['bg_picture'] ?? 'assets/img/default-event-bg.jpg'); ?>') center/cover;
        opacity: 0.3;
    }

    .event-info {
        position: relative;
        z-index: 1;
    }

    .event-logo {
        width: 120px;
        height: 120px;
        border-radius: 15px;
        object-fit: cover;
        border: 4px solid white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }

    .card {
        border: none;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-bottom: 1.5rem;
    }

    .booking-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }

    .badge-bookings {
        background: var(--primary-red);
    }

    .event-status {
        position: absolute;
        top: 20px;
        right: 20px;
        z-index: 2;
    }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-calendar-alt"></i> ConnectVerse
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

    <!-- Event Header -->
    <section class="event-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <img src="<?php echo htmlspecialchars($event['profile_picture'] ?? 'assets/img/default-event.png'); ?>"
                        alt="Event Logo" class="event-logo">
                </div>
                <div class="col-md-10 event-info">
                    <span class="badge <?php echo $status_class; ?> event-status">
                        <?php echo $status_text; ?>
                    </span>
                    <h1 class="display-6 fw-bold mb-2"><?php echo htmlspecialchars($event['name']); ?></h1>
                    <p class="lead mb-3"><?php echo htmlspecialchars($event['description']); ?></p>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge badge-bookings">
                            <i class="fas fa-ticket-alt me-1"></i> <?php echo $event['booking_count']; ?> Booking
                        </span>
                        <span class="badge bg-secondary">
                            <i class="fas fa-tag me-1"></i> <?php echo htmlspecialchars($event['category_name']); ?>
                        </span>
                        <span class="text-white">
                            <i class="fas fa-user-tie me-1"></i> Provider:
                            <?php echo htmlspecialchars($event['provider_name']); ?>
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

                <!-- Event Details -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Detail Event</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-2">
                                    <i class="fas fa-calendar-alt me-2 text-primary"></i>
                                    <strong>Tanggal Mulai:</strong><br>
                                    <?php echo $start_date->format('d M Y H:i'); ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2">
                                    <i class="fas fa-calendar-check me-2 text-primary"></i>
                                    <strong>Tanggal Selesai:</strong><br>
                                    <?php echo $end_date->format('d M Y H:i'); ?>
                                </p>
                            </div>
                        </div>
                        <p class="mb-2">
                            <i class="fas fa-tag me-2 text-primary"></i>
                            <strong>Kategori:</strong><br>
                            <?php echo htmlspecialchars($event['category_name']); ?>
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-money-bill-wave me-2 text-primary"></i>
                            <strong>Harga Tiket:</strong><br>
                            Rp <?php echo number_format($event['price'], 0, ',', '.'); ?>
                        </p>
                    </div>
                </div>

                <!-- Event Description -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Deskripsi Event</h5>
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Booking Button -->
                <div class="card">
                    <div class="card-body">
                        <?php if ($status === 'finished'): ?>
                        <button class="btn btn-secondary w-100" disabled>
                            <i class="fas fa-calendar-times me-2"></i>Event Selesai
                        </button>
                        <?php elseif ($has_booked): ?>
                        <form method="POST" class="d-grid">
                            <input type="hidden" name="action" value="cancel">
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="fas fa-times-circle me-2"></i>Batalkan Booking
                            </button>
                        </form>
                        <?php else: ?>
                        <form method="POST" class="d-grid">
                            <input type="hidden" name="action" value="book">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-ticket-alt me-2"></i>Booking Tiket
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Bookings -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Booking Terbaru</h5>
                        <?php if (empty($recent_bookings)): ?>
                        <p class="text-muted text-center">Belum ada booking</p>
                        <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_bookings as $booking): ?>
                            <div class="list-group-item px-0 d-flex align-items-center">
                                <img src="<?php echo $booking['profile_picture'] ?? 'assets/img/default-profile.png'; ?>"
                                    alt="Booking Avatar" class="booking-avatar me-3">
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($booking['full_name']); ?></h6>
                                    <small class="text-muted">
                                        Booking <?php echo date('d M Y', strtotime($booking['booking_date'])); ?>
                                    </small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Event Guidelines -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Panduan Event</h5>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Datang 15 menit sebelum event dimulai
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Bawa bukti booking
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Ikuti protokol kesehatan
                            </li>
                            <li>
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Patuhi aturan event
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