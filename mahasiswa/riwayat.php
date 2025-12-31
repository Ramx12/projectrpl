<?php
require_once '../config.php';
require_once '../functions.php';

// Pastikan session aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan fungsi redirect ada
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit;
    }
}

// Pastikan formatTanggal ada
if (!function_exists('formatTanggal')) {
    function formatTanggal($date) {
        return date('d-m-Y', strtotime($date));
    }
}

if (!isset($_SESSION['user_id'])) {
    redirect('../login.php');
}

// Ambil data sesi
$user_id   = $_SESSION['user_id'];
$user_nama = $_SESSION['user_nama'] ?? 'Mahasiswa';
$user_nim  = $_SESSION['user_nim'] ?? '-';

// Filter
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$filter_jenis = isset($_GET['jenis']) ? $_GET['jenis'] : 'all';

// =============================
//    RIWAYAT PEMINJAMAN RUANGAN
// =============================

$query_ruangan = "
    SELECT pr.*, r.nama_ruangan, r.gedung 
    FROM peminjaman_ruangan pr
    JOIN ruangan r ON pr.ruangan_id = r.id
    WHERE pr.user_id = ?
";

if ($filter_status !== 'all') {
    $query_ruangan .= " AND pr.status = ?";
}

$query_ruangan .= " ORDER BY pr.created_at DESC";

if ($filter_status !== 'all') {
    $stmt_ruangan = $conn->prepare($query_ruangan);
    $stmt_ruangan->bind_param("is", $user_id, $filter_status);
} else {
    $stmt_ruangan = $conn->prepare($query_ruangan);
    $stmt_ruangan->bind_param("i", $user_id);
}

$stmt_ruangan->execute();
$result_ruangan = $stmt_ruangan->get_result();

// =============================
//    RIWAYAT PEMINJAMAN FASILITAS
// =============================

$query_fasilitas = "
    SELECT pf.*, f.nama_fasilitas, f.kategori 
    FROM peminjaman_fasilitas pf
    JOIN fasilitas f ON pf.fasilitas_id = f.id
    WHERE pf.user_id = ?
";

if ($filter_status !== 'all') {
    $query_fasilitas .= " AND pf.status = ?";
}

$query_fasilitas .= " ORDER BY pf.created_at DESC";

if ($filter_status !== 'all') {
    $stmt_fasilitas = $conn->prepare($query_fasilitas);
    $stmt_fasilitas->bind_param("is", $user_id, $filter_status);
} else {
    $stmt_fasilitas = $conn->prepare($query_fasilitas);
    $stmt_fasilitas->bind_param("i", $user_id);
}

$stmt_fasilitas->execute();
$result_fasilitas = $stmt_fasilitas->get_result();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman - UMB Booking System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
            font-family: 'FontAwesome 6 Free';
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

        /* Filter Section */
        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-weight: 600;
            color: #1e40af;
            font-size: 0.9rem;
        }

        .filter-group select {
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            min-width: 200px;
        }

        .filter-group select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            margin-left: auto;
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
            padding: 60px 20px;
            color: #9ca3af;
            font-size: 1.1rem;
        }

        /* Tab Navigation */
        .tab-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 2px solid #e5e7eb;
        }

        .tab-nav button {
            padding: 12px 24px;
            border: none;
            background: transparent;
            color: #6b7280;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .tab-nav button::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: #3b82f6;
            transition: width 0.3s ease;
        }

        .tab-nav button:hover {
            color: #3b82f6;
        }

        .tab-nav button.active {
            color: #1e40af;
        }

        .tab-nav button.active::after {
            width: 100%;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-nav {
                flex-direction: column;
                gap: 15px;
            }

            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-actions {
                margin-left: 0;
                width: 100%;
            }

            .filter-actions .btn {
                width: 100%;
                justify-content: center;
            }

            .card-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .tab-nav {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">

        <!-- ================= HEADER ================= -->
        <div class="dashboard-header">
            <div class="container">
                <div class="dashboard-nav">

                    <div class="dashboard-title">
                        <h1><i class="fas fa-history"></i> Riwayat Peminjaman</h1>
                        <p>Selamat datang, <?= htmlspecialchars($user_nama) ?> (<?= htmlspecialchars($user_nim) ?>)</p>
                    </div>

                    <div class="user-info">
                        <span><?= htmlspecialchars($user_nama) ?></span>
                        <a href="../logout.php" class="logout-btn">Logout</a>
                    </div>

                </div>

                <div class="dashboard-menu">
                    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="ruangan.php"><i class="fas fa-door-open"></i> Pinjam Ruangan</a>
                    <a href="fasilitas.php"><i class="fas fa-tools"></i> Pinjam Fasilitas</a>
                    <a href="riwayat.php" class="active"><i class="fas fa-history"></i> Riwayat Peminjaman</a>
                </div>
            </div>
        </div>

        <!-- ================= CONTENT ================= -->
        <div class="dashboard-content">
            <div class="container">

                <!-- Filter Section -->
                <div class="filter-section">
                    <div class="filter-group">
                        <label><i class="fas fa-filter"></i> Filter Status</label>
                        <select id="filterStatus" onchange="applyFilter()">
                            <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>Semua Status</option>
                            <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="disetujui" <?= $filter_status === 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                            <option value="ditolak" <?= $filter_status === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                        </select>
                    </div>

                    <div class="filter-actions">
                        <button class="btn btn-secondary" onclick="resetFilter()">
                            <i class="fas fa-redo"></i> Reset Filter
                        </button>
                    </div>
                </div>

                <!-- Tab Navigation -->
                <div class="tab-nav">
                    <button class="active" onclick="showTab('ruangan')">
                        <i class="fas fa-door-open"></i> Peminjaman Ruangan
                    </button>
                    <button onclick="showTab('fasilitas')">
                        <i class="fas fa-tools"></i> Peminjaman Fasilitas
                    </button>
                </div>

                <!-- ================= TAB RUANGAN ================= -->
                <div id="tab-ruangan" class="tab-content active">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Riwayat Peminjaman Ruangan</h2>
                        </div>

                        <?php if ($result_ruangan->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-hashtag"></i> No</th>
                                            <th><i class="fas fa-door-open"></i> Ruangan</th>
                                            <th><i class="fas fa-calendar"></i> Tanggal</th>
                                            <th><i class="fas fa-clock"></i> Waktu</th>
                                            <th><i class="fas fa-align-left"></i> Keperluan</th>
                                            <th><i class="fas fa-info-circle"></i> Status</th>
                                            <th><i class="fas fa-user-check"></i> Disetujui Oleh</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        <?php 
                                        $no = 1;
                                        $result_ruangan->data_seek(0); // Reset pointer
                                        while ($row = $result_ruangan->fetch_assoc()): 
                                        ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($row['nama_ruangan']) ?></strong><br>
                                                    <small style="color: #6b7280;"><?= htmlspecialchars($row['gedung']) ?></small>
                                                </td>

                                                <td><?= formatTanggal($row['tanggal_mulai']) ?></td>

                                                <td>
                                                    <?= date('H:i', strtotime($row['jam_mulai'])) ?> -
                                                    <?= date('H:i', strtotime($row['jam_selesai'])) ?>
                                                </td>

                                                <td><?= htmlspecialchars($row['keperluan']) ?></td>

                                                <td>
                                                    <span class="badge badge-<?= htmlspecialchars($row['status']) ?>">
                                                        <?= ucfirst($row['status']) ?>
                                                    </span>
                                                </td>

                                                <td>
                                                    <?php if ($row['status'] === 'disetujui'): ?>
                                                        <strong><?= htmlspecialchars($row['nama_penyetuju'] ?? '-') ?></strong><br>
                                                        <small style="color: #6b7280;">Admin BOP</small>
                                                    <?php elseif ($row['status'] === 'ditolak'): ?>
                                                        <strong><?= htmlspecialchars($row['nama_penyetuju'] ?? '-') ?></strong><br>
                                                        <small style="color: #ef4444;">Ditolak</small>
                                                    <?php else: ?>
                                                        <span style="color: #9ca3af;">Belum diproses</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>

                                    </tbody>
                                </table>
                            </div>

                        <?php else: ?>
                            <p class="empty-message">
                                <i class="fas fa-inbox" style="font-size: 4rem; display: block; margin-bottom: 15px; opacity: 0.3;"></i>
                                Tidak ada riwayat peminjaman ruangan
                            </p>
                        <?php endif; ?>

                    </div>
                </div>

                <!-- ================= TAB FASILITAS ================= -->
                <div id="tab-fasilitas" class="tab-content">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Riwayat Peminjaman Fasilitas</h2>
                        </div>

                        <?php if ($result_fasilitas->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-hashtag"></i> No</th>
                                            <th><i class="fas fa-tools"></i> Fasilitas</th>
                                            <th><i class="fas fa-calendar"></i> Tanggal</th>
                                            <th><i class="fas fa-clock"></i> Waktu</th>
                                            <th><i class="fas fa-boxes"></i> Jumlah</th>
                                            <th><i class="fas fa-align-left"></i> Keperluan</th>
                                            <th><i class="fas fa-info-circle"></i> Status</th>
                                            <th><i class="fas fa-user-check"></i> Disetujui Oleh</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        <?php 
                                        $no = 1;
                                        $result_fasilitas->data_seek(0); // Reset pointer
                                        while ($row = $result_fasilitas->fetch_assoc()): 
                                        ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
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

                                                <td><?= htmlspecialchars($row['keperluan']) ?></td>

                                                <td>
                                                    <span class="badge badge-<?= htmlspecialchars($row['status']) ?>">
                                                        <?= ucfirst($row['status']) ?>
                                                    </span>
                                                </td>

                                                <td>
                                                    <?php if ($row['status'] === 'disetujui'): ?>
                                                        <strong><?= htmlspecialchars($row['nama_penyetuju'] ?? '-') ?></strong><br>
                                                        <small style="color: #6b7280;">Admin BSP</small>
                                                    <?php elseif ($row['status'] === 'ditolak'): ?>
                                                        <strong><?= htmlspecialchars($row['nama_penyetuju'] ?? '-') ?></strong><br>
                                                        <small style="color: #ef4444;">Ditolak</small>
                                                    <?php else: ?>
                                                        <span style="color: #9ca3af;">Belum diproses</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>

                                    </tbody>
                                </table>
                            </div>

                        <?php else: ?>
                            <p class="empty-message">
                                <i class="fas fa-inbox" style="font-size: 4rem; display: block; margin-bottom: 15px; opacity: 0.3;"></i>
                                Tidak ada riwayat peminjaman fasilitas
                            </p>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </div>

    </div>

    <script>
        // Tab Switching
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-nav button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById('tab-' + tabName).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }

        // Apply Filter
        function applyFilter() {
            const status = document.getElementById('filterStatus').value;
            window.location.href = `riwayat.php?status=${status}`;
        }

        // Reset Filter
        function resetFilter() {
            window.location.href = 'riwayat.php';
        }
    </script>
</body>
</html>

<?php
$stmt_ruangan->close();
$stmt_fasilitas->close();
$conn->close();
?>