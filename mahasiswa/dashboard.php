<?php
require_once '../config.php';
require_once '../functions.php';

// Jika session nama dan nim belum ada, ambil dari database
if (!isset($_SESSION['user_nama']) || !isset($_SESSION['user_nim'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        
        // Set nama
        if (isset($user_data['nama'])) {
            $_SESSION['user_nama'] = $user_data['nama'];
        } elseif (isset($user_data['name'])) {
            $_SESSION['user_nama'] = $user_data['name'];
        } elseif (isset($user_data['nama_lengkap'])) {
            $_SESSION['user_nama'] = $user_data['nama_lengkap'];
        } else {
            $_SESSION['user_nama'] = $_SESSION['username'] ?? 'Mahasiswa';
        }
        
        // Set NIM
        if (isset($user_data['nim'])) {
            $_SESSION['user_nim'] = $user_data['nim'];
        } elseif (isset($user_data['nomor_induk'])) {
            $_SESSION['user_nim'] = $user_data['nomor_induk'];
        } else {
            $_SESSION['user_nim'] = '-';
        }
    }
    $stmt->close();
}

// Ambil data sesi
$user_id   = $_SESSION['user_id'];
$user_nama = $_SESSION['user_nama'];
$user_nim  = $_SESSION['user_nim'];

// --- VALIDASI KONEKSI DATABASE ---
if (!$conn) {
    die("Koneksi database gagal.");
}

// =============================
//       QUERY STATISTIK
// =============================

// Total peminjaman ruangan
$stmt_ruangan = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM peminjaman_ruangan 
    WHERE user_id = ?
");
$stmt_ruangan->bind_param("i", $user_id);
$stmt_ruangan->execute();
$total_pinjam_ruangan = $stmt_ruangan->get_result()->fetch_assoc()['total'];
$stmt_ruangan->close();

// Total peminjaman fasilitas
$stmt_fasilitas = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM peminjaman_fasilitas 
    WHERE user_id = ?
");
$stmt_fasilitas->bind_param("i", $user_id);
$stmt_fasilitas->execute();
$total_pinjam_fasilitas = $stmt_fasilitas->get_result()->fetch_assoc()['total'];
$stmt_fasilitas->close();

// Total pending (gabungan ruangan + fasilitas)
$stmt_pending = $conn->prepare("
    SELECT COUNT(*) AS total FROM (
        SELECT id 
        FROM peminjaman_ruangan 
        WHERE user_id = ? AND status = 'pending'
        
        UNION ALL
        
        SELECT id 
        FROM peminjaman_fasilitas 
        WHERE user_id = ? AND status = 'pending'
    ) AS pending_items
");
$stmt_pending->bind_param("ii", $user_id, $user_id);
$stmt_pending->execute();
$total_pending = $stmt_pending->get_result()->fetch_assoc()['total'];
$stmt_pending->close();


// =============================
//    RIWAYAT PEMINJAMAN BARU
// =============================

// Ruangan terbaru
$recent_ruangan = $conn->prepare("
    SELECT pr.*, r.nama_ruangan, r.gedung 
    FROM peminjaman_ruangan pr
    JOIN ruangan r ON pr.ruangan_id = r.id
    WHERE pr.user_id = ?
    ORDER BY pr.created_at DESC
    LIMIT 5
");
$recent_ruangan->bind_param("i", $user_id);
$recent_ruangan->execute();
$result_ruangan = $recent_ruangan->get_result();

// Fasilitas terbaru
$recent_fasilitas = $conn->prepare("
    SELECT pf.*, f.nama_fasilitas, f.kategori 
    FROM peminjaman_fasilitas pf
    JOIN fasilitas f ON pf.fasilitas_id = f.id
    WHERE pf.user_id = ?
    ORDER BY pf.created_at DESC
    LIMIT 5
");
$recent_fasilitas->bind_param("i", $user_id);
$recent_fasilitas->execute();
$result_fasilitas = $recent_fasilitas->get_result();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mahasiswa - UMB Booking System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Enhanced Dashboard Styles */
        .dashboard-header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #0ea5e9 100%);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .dashboard-nav {
            padding: 10px 0;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 20px;
            border-radius: 50px;
        }

        .user-info span {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-info span::before {
            content: '\f007';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            margin-right: auto; 
        }

        .dashboard-nav {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-logo {
            height: 70px;
            width: 70px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }
        .dashboard-title h1 {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logout-btn {
            background: rgba(239, 68, 68, 0.9);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }

        .logout-btn::before {
            content: '\f2f5';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
        }

        .logout-btn:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
        }

        /* Dashboard Menu Enhancement */
        .dashboard-menu {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        .dashboard-menu a {
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.1);
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .dashboard-menu a::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 3px;
            background: white;
            transition: width 0.3s ease;
        }

        .dashboard-menu a:hover::before {
            width: 100%;
        }

        .dashboard-menu a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .dashboard-menu a.active {
            background: rgba(255, 255, 255, 0.25);
            font-weight: 600;
        }

        .dashboard-menu a.active::before {
            width: 100%;
        }

        /* Stats Card Enhancement */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(180deg, #3b82f6, #1e40af);
            transition: width 0.3s ease;
        }

        .stat-card:hover::before {
            width: 100%;
            opacity: 0.05;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            font-size: 2rem;
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            position: relative;
            z-index: 1;
        }

        .stat-info h3 {
            font-size: 2.5rem;
            color: #1e40af;
            margin-bottom: 5px;
            font-weight: 700;
        }

        .stat-info p {
            color: #6b7280;
            font-size: 0.95rem;
            font-weight: 500;
        }

        /* Card Enhancement */
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            padding: 30px;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f3f4f6;
        }

        .card-title {
            font-size: 1.5rem;
            color: #1e40af;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title::before {
            content: '';
            width: 4px;
            height: 30px;
            background: linear-gradient(180deg, #3b82f6, #1e40af);
            border-radius: 2px;
        }

        /* Button Enhancement */
        .btn {
            padding: 12px 28px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
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

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1e40af);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
        }

        /* Table Enhancement */
        .table-responsive {
            overflow-x: auto;
            border-radius: 12px;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        table thead {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
        }

        table th {
            padding: 16px;
            text-align: left;
            font-weight: 700;
            color: #1e40af;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table td {
            padding: 16px;
            border-bottom: 1px solid #f3f4f6;
        }

        table tbody tr {
            transition: all 0.3s ease;
        }

        table tbody tr:hover {
            background: #f9fafb;
            transform: scale(1.01);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        /* Badge Enhancement */
        .badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            text-transform: capitalize;
        }

        .badge-pending {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
        }

        .badge-disetujui {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
        }

        .badge-ditolak {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
        }

        /* Empty Message */
        .empty-message {
            text-align: center;
            padding: 40px;
            color: #9ca3af;
            font-size: 1.1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-nav {
                flex-direction: column;
                gap: 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .card-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="dashboard-header">
            <div class="container">
                <div class="dashboard-nav" style="justify-content: flex-start;">
                    <img src="../assets/images/logo-umb.png" alt="Logo UMB" class="nav-logo" onerror="this.style.display='none'">
                    <div class="dashboard-title">
                        <h1><i class="fas fa-home"></i> Dashboard Mahasiswa</h1>
                        <p>Selamat datang, <?= htmlspecialchars($user_nama) ?> (<?= htmlspecialchars($user_nim) ?>)</p>
                    </div>
                    <div class="user-info" style="margin-left: auto;">
                        <span><?= htmlspecialchars($user_nama) ?></span>
                        <a href="../logout.php" class="logout-btn">Logout</a>
                    </div>
                </div>

                <div class="dashboard-menu">
                    <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="ruangan.php"><i class="fas fa-door-open"></i> Pinjam Ruangan</a>
                    <a href="fasilitas.php"><i class="fas fa-tools"></i> Pinjam Fasilitas</a>
                    <a href="riwayat.php"><i class="fas fa-history"></i> Riwayat Peminjaman</a>
                </div>
            </div>
        </div>


        <div class="dashboard-content">
            <div class="container">

                <div class="stats-grid">

                    <div class="stat-card">
                        <div class="stat-icon" style="color: #3b82f6;">
                            <i class="fas fa-door-open"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $total_pinjam_ruangan ?></h3>
                            <p>Total Peminjaman Ruangan</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="color: #10b981;">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $total_pinjam_fasilitas ?></h3>
                            <p>Total Peminjaman Fasilitas</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="color: #f59e0b;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $total_pending ?></h3>
                            <p>Menunggu Persetujuan</p>
                        </div>
                    </div>

                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Peminjaman Ruangan Terbaru</h2>
                        <a href="ruangan.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Pinjam Ruangan
                        </a>
                    </div>

                    <?php if ($result_ruangan->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-door-open"></i> Ruangan</th>
                                        <th><i class="fas fa-calendar"></i> Tanggal</th>
                                        <th><i class="fas fa-clock"></i> Waktu</th>
                                        <th><i class="fas fa-info-circle"></i> Status</th>
                                        <th><i class="fas fa-user-check"></i> Disetujui Oleh</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <?php while ($row = $result_ruangan->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($row['nama_ruangan']) ?></strong><br>
                                                <small style="color: #6b7280;"><?= htmlspecialchars($row['gedung']) ?></small>
                                            </td>

                                            <td><?= formatTanggal($row['tanggal_mulai']) ?></td>

                                            <td>
                                                <?= date('H:i', strtotime($row['jam_mulai'])) ?> -
                                                <?= date('H:i', strtotime($row['jam_selesai'])) ?>
                                            </td>

                                            <td>
                                                <span class="badge badge-<?= htmlspecialchars($row['status']) ?>">
                                                    <?= ucfirst($row['status']) ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?php if ($row['status'] === 'disetujui'): ?>
                                                    <strong><?= htmlspecialchars($row['nama_penyetuju'] ?? '-') ?></strong><br>
                                                    <small style="color: #6b7280;">Admin BOP</small>
                                                <?php else: ?>
                                                    <span style="color: #9ca3af;">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>

                                </tbody>
                            </table>
                        </div>

                    <?php else: ?>
                        <p class="empty-message">
                            <i class="fas fa-inbox" style="font-size: 3rem; display: block; margin-bottom: 10px; opacity: 0.3;"></i>
                            Belum ada peminjaman ruangan
                        </p>
                    <?php endif; ?>

                </div>


                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Peminjaman Fasilitas Terbaru</h2>
                        <a href="fasilitas.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Pinjam Fasilitas
                        </a>
                    </div>

                    <?php if ($result_fasilitas->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-tools"></i> Fasilitas</th>
                                        <th><i class="fas fa-calendar"></i> Tanggal</th>
                                        <th><i class="fas fa-clock"></i> Waktu</th>
                                        <th><i class="fas fa-boxes"></i> Jumlah</th>
                                        <th><i class="fas fa-info-circle"></i> Status</th>
                                        <th><i class="fas fa-user-check"></i> Disetujui Oleh</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <?php while ($row = $result_fasilitas->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($row['nama_fasilitas']) ?></strong><br>
                                                <small style="color: #6b7280;"><?= htmlspecialchars($row['kategori']) ?></small>
                                            </td>

                                            <td><?= formatTanggal($row['tanggal_mulai']) ?></td>

                                            <td>
                                                <?= date('H:i', strtotime($row['jam_mulai'])) ?> -
                                                <?= date('H:i', strtotime($row['jam_selesai'])) ?>
                                            </td>

                                            <td><strong><?= intval($row['jumlah_pinjam']) ?></strong> unit</td>

                                            <td>
                                                <span class="badge badge-<?= htmlspecialchars($row['status']) ?>">
                                                    <?= ucfirst($row['status']) ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?php if ($row['status'] === 'disetujui'): ?>
                                                    <strong><?= htmlspecialchars($row['nama_penyetuju'] ?? '-') ?></strong><br>
                                                    <small style="color: #6b7280;">Admin BSP</small>
                                                <?php else: ?>
                                                    <span style="color: #9ca3af;">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>

                                </tbody>
                            </table>
                        </div>

                    <?php else: ?>
                        <p class="empty-message">
                            <i class="fas fa-inbox" style="font-size: 3rem; display: block; margin-bottom: 10px; opacity: 0.3;"></i>
                            Belum ada peminjaman fasilitas
                        </p>
                    <?php endif; ?>

                </div>

            </div>
        </div>

    </div>
</body>
</html>