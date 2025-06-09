<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = $_POST['full_name'];
    $role = $_POST['role'];
    
    // Validation
    if ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak sama';
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetchColumn() > 0) {
            $error = 'Username atau email sudah digunakan';
        } else {
            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$username, $email, $hashed_password, $full_name, $role])) {
                // Get the newly created user ID
                $user_id = $pdo->lastInsertId();
                
                // Auto login - set session variables
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;
                $_SESSION['logged_in'] = true;
                
                // Redirect to dashboard
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Terjadi kesalahan saat registrasi';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ConnectVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-red: #dc3545;
            --dark-bg: #212529;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-red) 0%, var(--dark-bg) 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .register-header {
            background: var(--primary-red);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .btn-primary {
            background: var(--primary-red);
            border-color: var(--primary-red);
        }
        
        .btn-primary:hover {
            background: #c82333;
            border-color: #c82333;
        }
        
        .text-red {
            color: var(--primary-red);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="register-card">
                    <div class="register-header">
                        <h2 class="mb-0">ConnectVerse</h2>
                        <p class="mb-0">Daftar akun baru</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="full_name" class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="role" class="form-label">Daftar Sebagai</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="user">User Biasa</option>
                                    <option value="community_admin">Pengurus Komunitas</option>
                                    <option value="event_provider">Penyedia Event</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">Daftar</button>
                        </form>
                        
                        <div class="text-center">
                            <p>Sudah punya akun? <a href="login.php" class="text-red">Login disini</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>