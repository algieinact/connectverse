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
                    <a class="nav-link px-3 <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" 
                       href="index.php">
                        <i class="bi bi-house me-1"></i>Home
                    </a>
                </li>
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
            </ul>

            <!-- Right Side Navigation -->
            <ul class="navbar-nav ms-auto my-2 my-lg-0 align-items-center">
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
            </ul>
        </div>
    </div>
</nav>