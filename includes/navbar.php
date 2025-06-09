<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-light fixed-top py-3 shadow-sm" id="mainNav">
    <div class="container px-4 px-lg-5">
        <a class="navbar-brand fw-bold" href="index.php">
            <i class="bi bi-people-fill me-2"></i>ConnectVerse
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive" 
                aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
            <!-- Main Navigation -->
            <ul class="navbar-nav me-auto my-2 my-lg-0">
                <li class="nav-item">
                    <a class="nav-link px-3 <?php echo $current_page == 'communities.php' ? 'active' : ''; ?>" 
                       href="communities.php">
                        <i class="bi bi-people me-1"></i>Communities
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 <?php echo $current_page == 'events.php' ? 'active' : ''; ?>" 
                       href="events.php">
                        <i class="bi bi-calendar-event me-1"></i>Events
                    </a>
                </li>
            </ul>

            <!-- Right Side Navigation -->
            <ul class="navbar-nav ms-auto my-2 my-lg-0 align-items-center">
                <!-- Info Pages -->
                <li class="nav-item">
                    <a class="nav-link px-3 <?php echo $current_page == 'about.php' ? 'active' : ''; ?>" 
                       href="about.php">
                        <i class="bi bi-info-circle me-1"></i>About
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 <?php echo $current_page == 'contact.php' ? 'active' : ''; ?>" 
                       href="contact.php">
                        <i class="bi bi-envelope me-1"></i>Contact
                    </a>
                </li>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- User Menu -->
                    <li class="nav-item dropdown ms-2">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" 
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i>
                            <span class="d-none d-lg-inline"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <!-- Common user menu items -->
                            <li>
                                <a class="dropdown-item" href="profile.php">
                                    <i class="bi bi-person me-2"></i>Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="my_bookings.php">
                                    <i class="bi bi-ticket-perforated me-2"></i>My Bookings
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="my_communities.php">
                                    <i class="bi bi-people me-2"></i>My Communities
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="settings.php">
                                    <i class="bi bi-gear me-2"></i>Settings
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
                <?php else: ?>
                    <!-- Guest Menu -->
                    <li class="nav-item ms-2">
                        <a class="nav-link btn btn-primary text-white px-4" href="login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="nav-link btn btn-outline-primary px-4" href="register.php">
                            <i class="bi bi-person-plus me-1"></i>Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>