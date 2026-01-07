<?php
require_once 'config.php';
require_once 'functions.php';

// Pastikan session dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika sudah login, redirect ke dashboard sesuai role
if (isLoggedIn()) {
    $role = getUserRole();
    if ($role === 'staff_bop') {
        redirect("bop/dashboard.php");
    } elseif ($role === 'staff_bsp') {
        redirect("bsp/dashboard.php");
    } elseif ($role === 'mahasiswa') {
        redirect("mahasiswa/dashboard.php");
    }
}

$error = "";
$success = "";
if (isset($_SESSION['registration_success'])) {
    $success = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = input($_POST['username']);
    $password = $_POST['password'];
    
    $error = [];
    if (empty($username)) {
        $error[] = "Username harus diisi";
    }
    if (empty($password)) {
        $error[] = "Password harus diisi";
    }
    
    if (empty($error)) {
        $user = null;
        $role = null;
        
        $query = "SELECT * FROM staff_bop WHERE username = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $role = 'staff_bop';
        } else {
            $stmt->close();
            $query = "SELECT * FROM staff_bsp WHERE username = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $role = 'staff_bsp';
            } else {
                $stmt->close();
                $query = "SELECT * FROM users WHERE nim = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    $role = 'mahasiswa';
                }
            }
        }
        
        if ($user !== null) {
            if ($password === $user['password']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $role;
                
                if ($role === 'staff_bop') {
                    $_SESSION['staff_bop_id'] = $user['id'];
                    $_SESSION['staff_bop_username'] = $user['username'];
                    $_SESSION['staff_bop_nama'] = $user['nama'] ?? $user['name'] ?? $user['username'];
                } elseif ($role === 'staff_bsp') {
                    $_SESSION['staff_bsp_id'] = $user['id'];
                    $_SESSION['staff_bsp_username'] = $user['username'];
                    $_SESSION['staff_bsp_nama'] = $user['nama'] ?? $user['name'] ?? $user['username'];
                } elseif ($role === 'mahasiswa') {
                    if (isset($user['nama'])) {
                        $_SESSION['user_nama'] = $user['nama'];
                    }elseif (isset($user['name'])) {
                        $_SESSION['user_nama'] = $user['name'];
                    }elseif (isset($user['nama_lengkap'])) {
                        $_SESSION['user_nama'] = $user['nama_lengkap'];
                    } else {
                        $_SESSION['user_nama'] = $user['username'];
                    }
            
                    if (isset($user['nim'])) {
                        $_SESSION['user_nim'] = $user['nim'];
                    } elseif (isset($user['nomor_induk'])) {
                        $_SESSION['user_nim'] = $user['nomor_induk'];
                    } else {
                        $_SESSION['user_nim'] = '-';
                    }
                }
                
                if ($role === 'staff_bop') {
                    header("Location: bop/dashboard.php");
                } elseif ($role === 'staff_bsp') {
                    header("Location: bsp/dashboard.php");
                } else {
                    header("Location: mahasiswa/dashboard.php");
                }
                exit();
            } else {
                $error[] = "Password salah";
            }
        } else {
            $error[] = "Username tidak ditemukan";
        }
        
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Website Peminjaman Fasilitas & Ruangan UMB</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
        }
        /* Animasi untuk login container */
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes shakeError {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .login-container {
            background-image: url('https://edunitas.com//assets/image/news/imgcover_1621660021336.jpg');
            background-size: cover;  
            background-position: center;  
            background-repeat: no-repeat;  
        }

        /* Overlay tipis hanya untuk kontras */
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            pointer-events: none;
        }

        .login-box {
            animation: slideInDown 0.6s ease-out;
            backdrop-filter: blur(8px);
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            z-index: 1;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .login-logo {
            height: 80px;
            width: auto;
            margin: 0 auto 15px;
            display: block;
        }

        /* Animasi untuk input focus */
        .form-control {
            transition: all 0.3s ease;
        }

        .form-control:focus {
            transform: scale(1.02);
            box-shadow: 0 0 20px rgba(30, 64, 175, 0.3);
        }

        /* Animasi untuk tombol login */
        .btn-login {
            position: relative;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid;
            background-color: #1e40af;
            color: white;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-login:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(30, 64, 175, 0.4);
        }

        .btn-login:active {
            transform: translateY(-1px);
        }

        /* Toggle password button */
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6b7280;
            transition: color 0.3s;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .password-wrapper {
            position: relative;
        }

        /* Alert animation */
        .alert {
            animation: shakeError 0.5s ease;
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .form-group {
            position: relative;
        }

        .form-label-animated {
            position: absolute;
            left: 15px;
            top: 15px;
            color: #6b7280;
            transition: all 0.3s ease;
            pointer-events: none;
            background: white;
            padding: 0 5px;
        }

        .form-control:focus ~ .form-label-animated,
        .form-control:not(:placeholder-shown) ~ .form-label-animated {
            top: -10px;
            font-size: 0.85rem;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-box">
        <div class="login-header">
            <img src="assets/images/logo-umb.jpg" alt="UMB" class="login-logo" onerror="this.style.display='none'">
            <h2>UMB Booking System</h2>
            <p>Peminjaman Ruangan dan Fasilitas</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo implode('<br>', $error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <div class="form-group">
                <input type="text" name="username" class="form-control" placeholder=" " required>
                <label class="form-label-animated">Username / NIM</label>
            </div>
            
            <div class="form-group">
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" class="form-control" placeholder=" " required>
                    <label class="form-label-animated">Password</label>
                    <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-login">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <div class="login-footer">
            <p style="color: #6b7280; font-size: 0.9rem;">
                <i class="fas fa-info-circle"></i> Gunakan NIM untuk mahasiswa atau username untuk staff<br>
                <p style="color: #6b7280; font-size: 0.9rem; margin-top: 10px;">
                    Belum punya akun? <a href="./register.php" style="color: #3b82f6; font-weight: 600; text-decoration: none;">Daftar di sini</a>
                </p>
            </p>
        </div>
    </div>
</div>

<script>
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    togglePassword.addEventListener('click', function() {
        // Toggle tipe input
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        // Toggle icon
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });

    // Add focus effect to form inputs
    const formInputs = document.querySelectorAll('.form-control');
    formInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.01)';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    });

    // Prevent form resubmission on refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
</script>

</body>
</html>