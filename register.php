<?php
require_once 'config.php';
session_start();

// Jika sudah login, redirect
if (isset($_SESSION['user_id'])) {
    header("Location: mahasiswa/dashboard.php");
    exit();
}

$error = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $nim = trim($_POST['nim']);
    $jurusan = trim($_POST['jurusan']);
    $fakultas = trim($_POST['fakultas']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi input
    if (empty($nama_lengkap)) {
        $error[] = "Nama lengkap harus diisi";
    }
    
    if (empty($nim)) {
        $error[] = "NIM harus diisi";
    } elseif (!preg_match('/^[0-9]+$/', $nim)) {
        $error[] = "NIM hanya boleh berisi angka";
    }
    
    if (empty($jurusan)) {
        $error[] = "Jurusan harus diisi";
    }
    
    if (empty($fakultas)) {
        $error[] = "Fakultas harus dipilih";
    }
    
    if (empty($email)) {
        $error[] = "Email harus diisi";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error[] = "Format email tidak valid";
    } elseif (!preg_match('/@student\.mercubuana\.ac\.id$/', $email)) {
        $error[] = "Email harus menggunakan domain @student.mercubuana.ac.id";
    }
    
    if (empty($password)) {
        $error[] = "Password harus diisi";
    } elseif (strlen($password) < 8) {
        $error[] = "Password minimal 8 karakter";
    } elseif (strlen($password) > 20) {
        $error[] = "Password maksimal 20 karakter";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error[] = "Password harus mengandung minimal 1 huruf besar";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error[] = "Password harus mengandung minimal 1 huruf kecil";
    } elseif (!preg_match('/[_#@$!%*?&]/', $password)) {
        $error[] = "Password harus mengandung minimal 1 karakter khusus (_ # @ $ ! % * ? &)";
    }
    
    if ($password !== $confirm_password) {
        $error[] = "Konfirmasi password tidak cocok";
    }
    
    // Check apakah NIM sudah terdaftar
    if (empty($error)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE nim = ?");
        $stmt->bind_param("s", $nim);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $error[] = "NIM sudah terdaftar";
        }
        $stmt->close();
    }
    
    // Check apakah email sudah terdaftar
    if (empty($error)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $error[] = "Email sudah terdaftar";
        }
        $stmt->close();
    }
    
    if (empty($error)) {
    try {
        // Simpan data langsung ke database
        // Kolom: id (auto), nim, nama, email, password, prodi, fakultas, created_at (auto)
        $stmt = $conn->prepare("INSERT INTO users (nim, nama, email, password, prodi, fakultas) VALUES (?, ?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("ssssss", 
            $nim,           // nim
            $nama_lengkap,  // nama
            $email,         // email
            $password,      // password
            $jurusan,       // prodi
            $fakultas       // fakultas
        );
        
        if ($stmt->execute()) {
            $_SESSION['registration_success'] = "Registrasi berhasil! Silakan login dengan NIM dan password Anda.";
            $stmt->close();
            header("Location: login.php");
            exit();
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        } catch (Exception $e) {
            $error[] = "Terjadi kesalahan: " . $e->getMessage();
            if (isset($stmt)) {
                $stmt->close();
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
    <title>Registrasi Mahasiswa - UMB Booking System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .register-container {
            width: 100%;
            max-width: 600px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideInDown 0.6s ease-out;
        }

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

        .register-header {
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .register-header img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 15px;
            border: 3px solid white;
        }

        .register-header h2 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .register-header p {
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .register-body {
            padding: 40px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            animation: shake 0.5s;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .alert i {
            font-size: 1.2rem;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1e40af;
            font-size: 0.95rem;
        }

        .form-group label i {
            margin-right: 5px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
            background: #f9fafb;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .password-strength {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s;
            border-radius: 2px;
        }

        .strength-weak { width: 33%; background: #ef4444; }
        .strength-medium { width: 66%; background: #f59e0b; }
        .strength-strong { width: 100%; background: #10b981; }

        .password-requirements {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 8px;
        }

        .password-requirements ul {
            list-style: none;
            padding: 0;
            margin: 5px 0 0 0;
        }

        .password-requirements li {
            padding: 3px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .password-requirements li i {
            width: 16px;
            font-size: 0.9rem;
        }

        .requirement-met {
            color: #10b981;
        }

        .requirement-unmet {
            color: #ef4444;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 43px;
            cursor: pointer;
            color: #6b7280;
            transition: color 0.3s;
        }

        .password-toggle:hover {
            color: #3b82f6;
        }

        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1e40af);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4);
        }

        .btn-primary:active {
            transform: translateY(-1px);
        }

        .register-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 2px solid #f3f4f6;
        }

        .register-footer p {
            color: #6b7280;
            margin-bottom: 10px;
        }

        .register-footer a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .register-footer a:hover {
            color: #1e40af;
        }

        select.form-control {
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .register-body {
                padding: 25px;
            }

            .register-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <img src="assets/images/logo-umb.jpg" alt="UMB" onerror="this.style.display='none'">
            <h2><i class="fas fa-user-plus"></i> Registrasi Mahasiswa</h2>
            <p>Daftar untuk mengakses UMB Booking System</p>
        </div>

        <div class="register-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <?php foreach ($error as $err): ?>
                            <div><?= htmlspecialchars($err) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" id="registerForm">
                <div class="form-group">
                    <label for="nama_lengkap">
                        <i class="fas fa-user"></i> Nama Lengkap *
                    </label>
                    <input type="text" name="nama" id="nama" class="form-control" 
                           placeholder="Masukkan nama lengkap" value="<?= isset($_POST['nama_lengkap']) ? htmlspecialchars($_POST['nama_lengkap']) : '' ?>" required>
                </div>

                <div class="form-group">
                    <label for="nim">
                        <i class="fas fa-id-card"></i> NIM *
                    </label>
                    <input type="text" name="nim" id="nim" class="form-control" 
                           placeholder="Masukkan NIM" value="<?= isset($_POST['nim']) ? htmlspecialchars($_POST['nim']) : '' ?>" required>
                </div>

                <div class="form-group">
                    <label for="fakultas">
                        <i class="fas fa-building"></i> Fakultas *
                    </label>
                    <select name="fakultas" id="fakultas" class="form-control" required>
                        <option value="">-- Pilih Fakultas --</option>
                        <option value="Fakultas Ekonomi dan Bisnis" <?= (isset($_POST['fakultas']) && $_POST['fakultas'] == 'Fakultas Ekonomi dan Bisnis') ? 'selected' : '' ?>>Fakultas Ekonomi dan Bisnis</option>
                        <option value="Fakultas Teknik" <?= (isset($_POST['fakultas']) && $_POST['fakultas'] == 'Fakultas Teknik') ? 'selected' : '' ?>>Fakultas Teknik</option>
                        <option value="Fakultas Ilmu Komputer" <?= (isset($_POST['fakultas']) && $_POST['fakultas'] == 'Fakultas Ilmu Komputer') ? 'selected' : '' ?>>Fakultas Ilmu Komputer</option>
                        <option value="Fakultas Ilmu Komunikasi" <?= (isset($_POST['fakultas']) && $_POST['fakultas'] == 'Fakultas Ilmu Komunikasi') ? 'selected' : '' ?>>Fakultas Ilmu Komunikasi</option>
                        <option value="Fakultas Desain dan Seni Kreatif" <?= (isset($_POST['fakultas']) && $_POST['fakultas'] == 'Fakultas Desain dan Seni Kreatif') ? 'selected' : '' ?>>Fakultas Desain dan Seni Kreatif</option>
                        <option value="Fakultas Psikologi" <?= (isset($_POST['fakultas']) && $_POST['fakultas'] == 'Fakultas Psikologi') ? 'selected' : '' ?>>Fakultas Psikologi</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="jurusan">
                        <i class="fas fa-graduation-cap"></i> Jurusan / Program Studi *
                    </label>
                    <input type="text" name="prodi" id="prodi" class="form-control" 
                           placeholder="Contoh: Teknik Informatika" value="<?= isset($_POST['jurusan']) ? htmlspecialchars($_POST['jurusan']) : '' ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Universitas *
                    </label>
                    <input type="email" name="email" id="email" class="form-control" 
                           placeholder="nama@student.mercubuana.ac.id" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                    <small style="color: #6b7280; font-size: 0.85rem; display: block; margin-top: 5px;">
                        <i class="fas fa-info-circle"></i> Gunakan email resmi universitas
                    </small>
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password *
                    </label>
                    <div style="position: relative;">
                        <input type="password" name="password" id="password" class="form-control" 
                               placeholder="Buat password" required>
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="password-requirements">
                        <strong>Ketentuan Password:</strong>
                        <ul id="passwordChecks">
                            <li id="check-length" class="requirement-unmet">
                                <i class="fas fa-times"></i>
                                <span>8-20 karakter</span>
                            </li>
                            <li id="check-uppercase" class="requirement-unmet">
                                <i class="fas fa-times"></i>
                                <span>Minimal 1 huruf besar</span>
                            </li>
                            <li id="check-lowercase" class="requirement-unmet">
                                <i class="fas fa-times"></i>
                                <span>Minimal 1 huruf kecil</span>
                            </li>
                            <li id="check-special" class="requirement-unmet">
                                <i class="fas fa-times"></i>
                                <span>Minimal 1 karakter khusus (_ # @ $ ! % * ? &)</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> Konfirmasi Password *
                    </label>
                    <div style="position: relative;">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" 
                               placeholder="Ulangi password" required>
                        <i class="fas fa-eye password-toggle" id="toggleConfirmPassword"></i>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Daftar Sekarang
                </button>
            </form>

            <div class="register-footer">
                <p>Sudah punya akun?</p>
                <a href="login.php">
                    <i class="fas fa-sign-in-alt"></i> Login di sini
                </a>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const password = document.getElementById('confirm_password');
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strengthBar');
            
            let strength = 0;
            const checks = {
                length: password.length >= 8 && password.length <= 20,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                special: /[_#@$!%*?&]/.test(password)
            };
            
            // Update visual checks
            Object.keys(checks).forEach(key => {
                const element = document.getElementById(`check-${key}`);
                if (checks[key]) {
                    element.classList.remove('requirement-unmet');
                    element.classList.add('requirement-met');
                    element.querySelector('i').className = 'fas fa-check';
                    strength++;
                } else {
                    element.classList.remove('requirement-met');
                    element.classList.add('requirement-unmet');
                    element.querySelector('i').className = 'fas fa-times';
                }
            });
            
            // Update strength bar
            strengthBar.className = 'password-strength-bar';
            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
            } else if (strength === 3) {
                strengthBar.classList.add('strength-medium');
            } else if (strength === 4) {
                strengthBar.classList.add('strength-strong');
            }
        });

        // Email validation
        document.getElementById('email').addEventListener('blur', function() {
            const email = this.value;
            if (email && !email.endsWith('@student.mercubuana.ac.id')) {
                this.setCustomValidity('Email harus menggunakan domain @student.mercubuana.ac.id');
            } else {
                this.setCustomValidity('');
            }
        });

        // NIM validation (only numbers)
        document.getElementById('nim').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Prevent form resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>