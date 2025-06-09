<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top py-3 shadow-sm" id="adminNav">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold text-danger" href="admin/dashboard.php">
            <i class="bi bi-shield-check me-2"></i>ConnectVerse Admin
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdminResponsive" 
                aria-controls="navbarAdminResponsive" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarAdminResponsive">
            <!-- Main Navigation -->
            <ul class="navbar-nav me-auto my-2 my-lg-0">
                <li class="nav-item">
                    <a class="nav-link px-3 <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" 
                       href="admin/dashboard.php">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle px-3" href="#" id="userManagementDropdown" 
                       role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-people me-1"></i>User Management
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="userManagementDropdown">
                        <li>
                            <a class="dropdown-item" href="admin/users.php">
                                <i class="bi bi-person-lines-fill me-2"></i>All Users
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="admin/user_roles.php">
                                <i class="bi bi-person-badge me-2"></i>User Roles
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="admin/banned_users.php">
                                <i class="bi bi-person-x me-2"></i>Banned Users
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle px-3" href="#" id="contentManagementDropdown" 
                       role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-folder me-1"></i>Content Management
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="contentManagementDropdown">
                        <li>
                            <a class="dropdown-item" href="admin/communities.php">
                                <i class="bi bi-people me-2"></i>Communities
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="admin/events.php">
                                <i class="bi bi-calendar-event me-2"></i>Events
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="admin/reports.php">
                                <i class="bi bi-flag me-2"></i>Reports
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 <?php echo $current_page == 'analytics.php' ? 'active' : ''; ?>" 
                       href="admin/analytics.php">
                        <i class="bi bi-graph-up me-1"></i>Analytics
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>" 
                       href="admin/settings.php">
                        <i class="bi bi-gear me-1"></i>Settings
                    </a>
                </li>
            </ul>

            <!-- Right Side Navigation -->
            <ul class="navbar-nav ms-auto my-2 my-lg-0 align-items-center">
                <!-- Notifications -->
                <li class="nav-item dropdown me-3">
                    <a class="nav-link position-relative" href="#" id="notificationDropdown" 
                       role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            3
                            <span class="visually-hidden">unread notifications</span>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                        <li><h6 class="dropdown-header">Notifications</h6></li>
                        <li>
                            <a class="dropdown-item" href="#">
                                <div class="small text-muted">New user registration</div>
                                <div class="fw-bold">5 minutes ago</div>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#">
                                <div class="small text-muted">Community reported</div>
                                <div class="fw-bold">1 hour ago</div>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#">
                                <div class="small text-muted">Event approved</div>
                                <div class="fw-bold">2 hours ago</div>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-center" href="admin/notifications.php">
                                View all notifications
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Quick Actions -->
                <li class="nav-item dropdown me-3">
                    <a class="nav-link" href="#" id="quickActionsDropdown" 
                       role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-plus-circle"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="quickActionsDropdown">
                        <li><h6 class="dropdown-header">Quick Actions</h6></li>
                        <li>
                            <a class="dropdown-item" href="admin/create_user.php">
                                <i class="bi bi-person-plus me-2"></i>Add User
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="admin/create_announcement.php">
                                <i class="bi bi-megaphone me-2"></i>Create Announcement
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="admin/backup.php">
                                <i class="bi bi-download me-2"></i>Backup Data
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Admin Profile -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="adminDropdown" 
                       role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-1"></i>
                        <span class="d-none d-lg-inline"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                        <li>
                            <a class="dropdown-item" href="admin/profile.php">
                                <i class="bi bi-person me-2"></i>My Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="admin/account_settings.php">
                                <i class="bi bi-gear me-2"></i>Account Settings
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="../index.php">
                                <i class="bi bi-house me-2"></i>View Site
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<style>
/* Admin Navbar Custom Styles */
#adminNav .navbar-brand {
    font-size: 1.5rem;
}

#adminNav .nav-link.active {
    background-color: rgba(220, 53, 69, 0.1);
    border-radius: 5px;
    color: #dc3545 !important;
}

#adminNav .dropdown-menu {
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

#adminNav .badge {
    font-size: 0.6rem;
}

/* Notification badge animation */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

#adminNav .badge {
    animation: pulse 2s infinite;
}
</style>