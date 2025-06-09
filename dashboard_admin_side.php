<?php
<nav class="nav flex-column">
                    <a class="nav-link active" href="dashboard_admin.php">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    
                    <?php if ($user['role'] === 'community_admin'): ?>
                        <a class="nav-link" href="dashboard_community.php">
                            <i class="fas fa-users me-2"></i> Kelola Komunitas
                        </a>
                        <a class="nav-link" href="members.php">
                            <i class="fas fa-user-friends me-2"></i> Kelola Member
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($user['role'] === 'event_provider'): ?>
                        <a class="nav-link" href="dashboard_event.php">
                            <i class="fas fa-calendar-alt me-2"></i> Kelola Event
                        </a>
                        <a class="nav-link" href="bookings.php">
                            <i class="fas fa-ticket-alt me-2"></i> Verifikasi Booking
                        </a>
                    <?php endif; ?>
                    
                    <a class="nav-link" href="profile.php">
                        <i class="fas fa-user me-2"></i> Profil
                    </a>
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </nav>