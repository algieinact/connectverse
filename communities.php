<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

// Get all categories for filter
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build query with filters
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(c.name LIKE ? OR c.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category_filter)) {
    $where_conditions[] = "c.category_id = ?";
    $params[] = $category_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$stmt = $pdo->prepare("
    SELECT c.*, cat.name as category_name, u.full_name as admin_name,
           (SELECT COUNT(*) FROM community_members cm WHERE cm.community_id = c.id) as member_count
    FROM communities c 
    LEFT JOIN categories cat ON c.category_id = cat.id 
    LEFT JOIN users u ON c.admin_id = u.id 
    $where_clause
    ORDER BY c.created_at DESC
");
$stmt->execute($params);
$communities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Komunitas - ConnectVerse</title>
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
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s;
        height: 100%;
    }

    .card:hover {
        transform: translateY(-2px);
    }

    .community-card {
        position: relative;
        overflow: hidden;
    }

    .card-img-top {
        height: 200px;
        object-fit: cover;
    }

    .search-section {
        background: var(--dark-bg);
        color: white;
        padding: 2rem 0;
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
                        <a class="nav-link active" href="communities.php">Komunitas</a>
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

    <!-- Search Section -->
    <section class="search-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-6 fw-bold mb-0">Temukan Komunitas</h1>
                    <p class="lead">Bergabunglah dengan komunitas yang sesuai minat Anda</p>
                </div>
                <div class="col-md-4 text-end">
                    <?php if ($user['role'] === 'community_admin'): ?>
                    <a href="create_community.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus"></i> Buat Komunitas
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <div class="container my-4">
        <!-- Filter Form -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-6">
                                <label for="search" class="form-label">Cari Komunitas</label>
                                <input type="text" class="form-control" id="search" name="search"
                                    placeholder="Nama komunitas atau deskripsi..."
                                    value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="category" class="form-label">Kategori</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">Semua Kategori</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"
                                        <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Cari
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Communities List -->
        <div class="row">
            <?php if (empty($communities)): ?>
            <div class="col-12 text-center">
                <div class="card">
                    <div class="card-body py-5">
                        <i class="fas fa-search fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">Tidak ada komunitas ditemukan</h4>
                        <p class="text-muted">Coba ubah kata kunci pencarian atau filter kategori</p>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($communities as $community): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card community-card">
                    <?php if ($community['profile_picture']): ?>
                    <img src="<?php echo htmlspecialchars($community['profile_picture']); ?>" class="card-img-top"
                        alt="Community Image">
                    <?php else: ?>
                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center">
                        <i class="fas fa-users fa-4x text-muted"></i>
                    </div>
                    <?php endif; ?>
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($community['name']); ?></h5>
                            <span class="badge badge-members">
                                <?php echo $community['member_count']; ?> anggota
                            </span>
                        </div>

                        <p class="text-muted small mb-2">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($community['category_name']); ?>
                        </p>

                        <p class="card-text flex-grow-1">
                            <?php echo htmlspecialchars(substr($community['description'], 0, 120)); ?>
                            <?php echo strlen($community['description']) > 120 ? '...' : ''; ?>
                        </p>

                        <div class="mt-auto">
                            <small class="text-muted d-block mb-2">
                                <i class="fas fa-user"></i> Admin:
                                <?php echo htmlspecialchars($community['admin_name']); ?>
                            </small>
                            <a href="community_detail.php?id=<?php echo $community['id']; ?>"
                                class="btn btn-primary w-100">
                                <i class="fas fa-eye"></i> Lihat Detail
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>