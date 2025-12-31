<?php
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff_bop') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_SESSION['staff_bop_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM staff_bop WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    $_SESSION['staff_bop_id'] = $user['id'];
    $_SESSION['staff_bop_username'] = $user['username'];
    $_SESSION['staff_bop_nama'] = $user['nama'] ?? $user['name'] ?? $user['username'];
}

$staff_id = $_SESSION['staff_bop_id'];
$staff_username = $_SESSION['staff_bop_username'];
$staff_nama = $_SESSION['staff_bop_nama'];

function formatTanggal($tanggal) {
    $bulan = array(
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    
    $pecahkan = explode('-', $tanggal);
    
    if (count($pecahkan) == 3) {
        return $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];
    }
    
    return $tanggal;
}

// Filter
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : '';

// Build query
$query = "
    SELECT pr.*, r.nama_ruangan, r.gedung, u.nama as nama_peminjam, u.nim 
    FROM peminjaman_ruangan pr
    JOIN ruangan r ON pr.ruangan_id = r.id
    JOIN users u ON pr.user_id = u.id
    WHERE 1=1
";

if ($filter_status !== 'all') {
    $query .= " AND pr.status = '" . $conn->real_escape_string($filter_status) . "'";
}

if (!empty($filter_bulan)) {
    $query .= " AND DATE_FORMAT(pr.tanggal_mulai, '%Y-%m') = '" . $conn->real_escape_string($filter_bulan) . "'";
}

$query .= " ORDER BY pr.created_at DESC";

$result = $conn->query($query);

// Get statistics
$total_peminjaman = $conn->query("SELECT COUNT(*) as total FROM peminjaman_ruangan")->fetch_assoc()['total'];
$total_pending = $conn->query("SELECT COUNT(*) as total FROM peminjaman_ruangan WHERE status = 'pending'")->fetch_assoc()['total'];
$total_disetujui = $conn->query("SELECT COUNT(*) as total FROM peminjaman_ruangan WHERE status = 'disetujui'")->fetch_assoc()['total'];
$total_ditolak = $conn->query("SELECT COUNT(*) as total FROM peminjaman_ruangan WHERE status = 'ditolak'")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman Ruangan - BOP</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Enhanced Dashboard Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f3f4f6;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #0ea5e9 100%);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .dashboard-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-right: auto;
        }

        .nav-logo {
            height: 50px;
            width: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }

        .dashboard-title h1 {
            color: white;
            font-size: 1.8rem;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dashboard-title p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.95rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255, 255, 255, 0.15);
            padding: 10px 20px;
            border-radius: 50px;
            backdrop-filter: blur(10px);
        }

        .user-info span {
            color: white;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-info span::before {
            content: '\f007';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
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
            display: flex;
            align-items: center;
            gap: 8px;
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
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
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 2rem;
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
        }

        .stat-info h3 {
            font-size: 2rem;
            color: #1e40af;
            margin-bottom: 5px;
            font-weight: 700;
        }

        .stat-info p {
            color: #6b7280;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .filter-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1e40af;
            font-size: 0.9rem;
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* Card */
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

        /* Button */
        .btn {
            padding: 12px 24px;
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
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1e40af);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }

        /* Table */
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
            font-size: 0.9rem;
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

        /* Badge */
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

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 15px;
            opacity: 0.3;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-nav {
                flex-direction: column;
                gap: 15px;
            }

            .nav-brand {
                width: 100%;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .filter-row {
                flex-direction: column;
            }

            .filter-group {
                width: 100%;
            }

            table {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="dashboard-header">
            <div class="container">
                <div class="dashboard-nav">
                    <div class="nav-brand">
                        <img src="../assets/images/logo-umb.png" alt="Logo UMB" class="nav-logo" onerror="this.style.display='none'">
                        <div class="dashboard-title">
                            <h1><i class="fas fa-history"></i> Riwayat Peminjaman</h1>
                            <p>Biro Operasional Perkuliahan - Pengelola Ruangan</p>
                        </div>
                    </div>
                    <div class="user-info">
                        <span><?= htmlspecialchars($staff_nama) ?></span>
                        <a href="../logout.php" class="logout-btn">Logout</a>
                    </div>
                </div>
                <div class="dashboard-menu">
                    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="ruangan.php"><i class="fas fa-door-open"></i> Kelola Ruangan</a>
                    <a href="jadwalkuliah.php"><i class="fas fa-calendar-alt"></i> Jadwal Perkuliahan</a>
                    <a href="riwayatpeminjaman.php" class="active"><i class="fas fa-list"></i> Riwayat Peminjaman Ruangan</a>
                </div>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container">
                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="color: #3b82f6;">
                            <i class="fas fa-list-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $total_peminjaman ?></h3>
                            <p>Total Peminjaman</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="color: #f59e0b;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $total_pending ?></h3>
                            <p>Pending</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="color: #10b981;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $total_disetujui ?></h3>
                            <p>Disetujui</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="color: #ef4444;">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $total_ditolak ?></h3>
                            <p>Ditolak</p>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="filter-section">
                    <form method="GET" action="">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label><i class="fas fa-filter"></i> Status</label>
                                <select name="status" id="filterStatus">
                                    <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>Semua Status</option>
                                    <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="disetujui" <?= $filter_status === 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                                    <option value="ditolak" <?= $filter_status === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label><i class="fas fa-calendar"></i> Bulan</label>
                                <input type="month" name="bulan" value="<?= htmlspecialchars($filter_bulan) ?>">
                            </div>
                            <div class="filter-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Terapkan Filter
                                </button>
                                <a href="peminjaman.php" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Data Table -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Data Peminjaman Ruangan</h2>
                    </div>

                    <?php if ($result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-hashtag"></i> No</th>
                                        <th><i class="fas fa-user"></i> Mahasiswa</th>
                                        <th><i class="fas fa-door-open"></i> Ruangan</th>
                                        <th><i class="fas fa-calendar"></i> Tanggal</th>
                                        <th><i class="fas fa-clock"></i> Waktu</th>
                                        <th><i class="fas fa-align-left"></i> Keperluan</th>
                                        <th><i class="fas fa-info-circle"></i> Status</th>
                                        <th><i class="fas fa-user-check"></i> Penyetuju</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    while ($row = $result->fetch_assoc()): 
                                    ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($row['nama_peminjam']) ?></strong><br>
                                                <small style="color: #6b7280;"><?= htmlspecialchars($row['nim']) ?></small>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($row['nama_ruangan']) ?></strong><br>
                                                <small style="color: #6b7280;"><?= htmlspecialchars($row['gedung']) ?></small>
                                            </td>
                                            <td>
                                                <?= formatTanggal($row['tanggal_mulai']) ?><br>
                                                <small style="color: #6b7280;">s/d <?= formatTanggal($row['tanggal_selesai']) ?></small>
                                            </td>
                                            <td>
                                                <?= date('H:i', strtotime($row['jam_mulai'])) ?> -<br>
                                                <?= date('H:i', strtotime($row['jam_selesai'])) ?>
                                            </td>
                                            <td style="max-width: 200px;">
                                                <small><?= htmlspecialchars($row['keperluan']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= htmlspecialchars($row['status']) ?>">
                                                    <?= ucfirst($row['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($row['status'] === 'disetujui' || $row['status'] === 'ditolak'): ?>
                                                    <strong><?= htmlspecialchars($row['nama_penyetuju'] ?? '-') ?></strong><br>
                                                    <small style="color: #6b7280;">
                                                        <?= $row['status'] === 'disetujui' ? 'Disetujui' : 'Ditolak' ?>
                                                    </small>
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
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>Tidak ada data peminjaman ditemukan</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>