<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
if (!$user || $user['role'] !== 'event_provider') {
    header('Location: dashboard.php');
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
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $price = floatval($_POST['price']);
        
        if (empty($name) || empty($start_date) || empty($end_date)) {
            $error = 'Nama event, tanggal mulai, dan tanggal selesai harus diisi!';
        } elseif (strtotime($start_date) >= strtotime($end_date)) {
            $error = 'Tanggal mulai harus lebih awal dari tanggal selesai!';
        } else {
            try {
                // Handle image uploads
                $profile_picture = null;
                $bg_picture = null;
                
                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                    $profile_picture = uploadImage($_FILES['profile_picture'], 'uploads/events/');
                }
                
                if (isset($_FILES['bg_picture']) && $_FILES['bg_picture']['error'] === UPLOAD_ERR_OK) {
                    $bg_picture = uploadImage($_FILES['bg_picture'], 'uploads/events/');
                }
                
                if ($action === 'create') {
                    $sql = "INSERT INTO events (name, description, category_id, profile_picture, bg_picture, start_date, end_date, price, provider_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$name, $description, $category_id, $profile_picture, $bg_picture, $start_date, $end_date, $price, $user['id']]);
                    $message = 'Event berhasil dibuat!';
                } else {
                    // Get current event data
                    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND provider_id = ?");
                    $stmt->execute([$id, $user['id']]);
                    $event = $stmt->fetch();
                    
                    if ($event) {
                        $sql = "UPDATE events SET name = ?, description = ?, category_id = ?, start_date = ?, end_date = ?, price = ?";
                        $params = [$name, $description, $category_id, $start_date, $end_date, $price];
                        
                        if ($profile_picture) {
                            $sql .= ", profile_picture = ?";
                            $params[] = $profile_picture;
                        }
                        
                        if ($bg_picture) {
                            $sql .= ", bg_picture = ?";
                            $params[] = $bg_picture;
                        }
                        
                        $sql .= " WHERE id = ? AND provider_id = ?";
                        $params[] = $id;
                        $params[] = $user['id'];
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);
                        $message = 'Event berhasil diupdate!';
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
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ? AND provider_id = ?");
        $stmt->execute([$id, $user['id']]);
        $message = 'Event berhasil dihapus!';
        $action = 'list';
    } catch (PDOException $e) {
        $error = 'Gagal menghapus event: ' . $e->getMessage();
    }
}

// Get events list
if ($action === 'list') {
    $stmt = $pdo->prepare("
        SELECT e.*, cat.name as category_name, 
               COUNT(eb.id) as booking_count
        FROM events e
        LEFT JOIN categories cat ON e.category_id = cat.id
        LEFT JOIN event_bookings eb ON e.id = eb.event_id
        WHERE e.provider_id = ?
        GROUP BY e.id
        ORDER BY e.start_date ASC
    ");
    $stmt->execute([$user['id']]);
    $events = $stmt->fetchAll();
}

// Get event data for edit
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND provider_id = ?");
    $stmt->execute([$id, $user['id']]);
    $event = $stmt->fetch();
    
    if (!$event) {
        $error = 'Event tidak ditemukan!';
        $action = 'list';
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Event - ConnectVerse</title>
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

    .event-card {
        transition: transform 0.2s;
        border-radius: 15px;
        overflow: hidden;
    }

    .event-card:hover {
        transform: translateY(-5px);
    }

    .event-status {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 1;
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
                    <a class="nav-link active" href="events.php">
                        <i class="fas fa-calendar-alt me-2"></i> Kelola Event
                    </a>
                    <a class="nav-link" href="bookings.php">
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

                    <?php if ($action === 'list'): ?>
                    <!-- Events List -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Kelola Event</h2>
                        <a href="?action=create" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>Buat Event Baru
                        </a>
                    </div>

                    <div class="row">
                        <?php foreach ($events as $event): ?>
                        <?php
                                $now = new DateTime();
                                $start_date = new DateTime($event['start_date']);
                                $end_date = new DateTime($event['end_date']);
                                
                                if ($now < $start_date) {
                                    $status = 'upcoming';
                                    $status_text = 'Akan Datang';
                                    $status_class = 'bg-primary';
                                } elseif ($now >= $start_date && $now <= $end_date) {
                                    $status = 'ongoing';
                                    $status_text = 'Berlangsung';
                                    $status_class = 'bg-success';
                                } else {
                                    $status = 'finished';
                                    $status_text = 'Selesai';
                                    $status_class = 'bg-secondary';
                                }
                                ?>

                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card event-card h-100 position-relative">
                                <!-- Status Badge -->
                                <span class="badge <?php echo $status_class; ?> event-status">
                                    <?php echo $status_text; ?>
                                </span>

                                <?php if ($event['bg_picture']): ?>
                                <img src="<?php echo htmlspecialchars($event['bg_picture']); ?>" class="card-img-top"
                                    style="height: 200px; object-fit: cover;">
                                <?php else: ?>
                                <div class="bg-gradient d-flex align-items-center justify-content-center"
                                    style="height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                    <i class="fas fa-calendar-alt fa-3x text-white"></i>
                                </div>
                                <?php endif; ?>

                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <?php if ($event['profile_picture']): ?>
                                        <img src="<?php echo htmlspecialchars($event['profile_picture']); ?>"
                                            class="rounded-circle me-3" width="40" height="40"
                                            style="object-fit: cover;">
                                        <?php else: ?>
                                        <div class="bg-primary rounded-circle me-3 d-flex align-items-center justify-content-center"
                                            style="width: 40px; height: 40px;">
                                            <i class="fas fa-calendar text-white"></i>
                                        </div>
                                        <?php endif; ?>

                                        <div>
                                            <h6 class="card-title mb-0"><?php echo htmlspecialchars($event['name']); ?>
                                            </h6>
                                            <small
                                                class="text-muted"><?php echo htmlspecialchars($event['category_name']); ?></small>
                                        </div>
                                    </div>

                                    <p class="card-text">
                                        <?php echo htmlspecialchars(substr($event['description'], 0, 100)); ?>...
                                    </p>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted d-block">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?php echo date('d M Y', strtotime($event['start_date'])); ?>
                                            </small>
                                            <small class="text-muted d-block">
                                                <i class="fas fa-ticket-alt me-1"></i>
                                                <?php echo $event['booking_count']; ?> booking
                                            </small>
                                        </div>

                                        <div class="btn-group">
                                            <a href="?action=edit&id=<?php echo $event['id']; ?>"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?action=delete&id=<?php echo $event['id']; ?>"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Yakin ingin menghapus event ini?')">
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
                        <h2><?php echo $action === 'create' ? 'Buat Event Baru' : 'Edit Event'; ?></h2>
                        <a href="dashboard_event.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
                    </div>

                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-body">
                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Nama Event *</label>
                                            <input type="text" class="form-control" id="name" name="name"
                                                value="<?php echo $action === 'edit' ? htmlspecialchars($event['name']) : ''; ?>"
                                                required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="category_id" class="form-label">Kategori *</label>
                                            <select class="form-select" id="category_id" name="category_id" required>
                                                <option value="">Pilih Kategori</option>
                                                <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>"
                                                    <?php echo ($action === 'edit' && $event['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="description" class="form-label">Deskripsi</label>
                                            <textarea class="form-control" id="description" name="description"
                                                rows="4"><?php echo $action === 'edit' ? htmlspecialchars($event['description']) : ''; ?></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="start_date" class="form-label">Tanggal Mulai *</label>
                                                    <input type="datetime-local" class="form-control" id="start_date"
                                                        name="start_date"
                                                        value="<?php echo $action === 'edit' ? date('Y-m-d\TH:i', strtotime($event['start_date'])) : ''; ?>"
                                                        required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="end_date" class="form-label">Tanggal Selesai *</label>
                                                    <input type="datetime-local" class="form-control" id="end_date"
                                                        name="end_date"
                                                        value="<?php echo $action === 'edit' ? date('Y-m-d\TH:i', strtotime($event['end_date'])) : ''; ?>"
                                                        required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="price" class="form-label">Harga Tiket (Rp)</label>
                                            <input type="number" class="form-control" id="price" name="price" min="0"
                                                step="0.01"
                                                value="<?php echo $action === 'edit' ? htmlspecialchars($event['price']) : '0'; ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label for="profile_picture" class="form-label">Foto Profil</label>
                                            <input type="file" class="form-control" id="profile_picture"
                                                name="profile_picture" accept="image/*">
                                            <?php if ($action === 'edit' && $event['profile_picture']): ?>
                                            <div class="mt-2">
                                                <small class="text-muted">Foto saat ini:</small><br>
                                                <img src="<?php echo htmlspecialchars($event['profile_picture']); ?>"
                                                    class="img-thumbnail" width="100">
                                            </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="mb-3">
                                            <label for="bg_picture" class="form-label">Foto Background</label>
                                            <input type="file" class="form-control" id="bg_picture" name="bg_picture"
                                                accept="image/*">
                                            <?php if ($action === 'edit' && $event['bg_picture']): ?>
                                            <div class="mt-2">
                                                <small class="text-muted">Background saat ini:</small><br>
                                                <img src="<?php echo htmlspecialchars($event['bg_picture']); ?>"
                                                    class="img-thumbnail" width="200">
                                            </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i
                                                    class="fas fa-save me-2"></i><?php echo $action === 'create' ? 'Buat Event' : 'Update Event'; ?>
                                            </button>
                                            <a href="dashboard_event.php" class="btn btn-secondary">Batal</a>
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