<?php
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['staff_bop_id'])) {
    header("Location: ../login.php");
    exit;
}

$success = '';
$error = '';

$staff_id = $_SESSION['staff_bop_id'];
$staff_username = $_SESSION['staff_bop_username'];
$staff_nama = $_SESSION['staff_bop_nama'];

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $nama_ruangan = input($_POST['nama_ruangan']);
        $gedung = input($_POST['gedung']);
        $kapasitas = (int)$_POST['kapasitas'];
        $fasilitas = input($_POST['fasilitas']);
        $gambar = 'default-room.jpg';
        
        // Handle file upload
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['gambar']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_name = 'room_' . time() . '.' . $ext;
                $upload_path = '../assets/images/' . $new_name;
                
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                    $gambar = $new_name;
                }
            }
        }
        
        $stmt = $conn->prepare("INSERT INTO ruangan (nama_ruangan, gedung, kapasitas, fasilitas, gambar) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiss", $nama_ruangan, $gedung, $kapasitas, $fasilitas, $gambar);
        
        if ($stmt->execute()) {
            $success = "Ruangan berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan ruangan.";
        }
    } elseif (isset($_POST['edit'])) {
        $id = (int)$_POST['id'];
        $nama_ruangan = input($_POST['nama_ruangan']);
        $gedung = input($_POST['gedung']);
        $kapasitas = (int)$_POST['kapasitas'];
        $fasilitas = input($_POST['fasilitas']);
        $status = input($_POST['status']);
        
        // Get current image
        $current = $conn->query("SELECT gambar FROM ruangan WHERE id = $id")->fetch_assoc();
        $gambar = $current['gambar'];
        
        // Handle file upload
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['gambar']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_name = 'room_' . time() . '.' . $ext;
                $upload_path = '../assets/images/' . $new_name;
                
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                    $gambar = $new_name;
                    // Delete old image if not default
                    if ($current['gambar'] !== 'default-room.jpg' && file_exists('../assets/images/' . $current['gambar'])) {
                        unlink('../assets/images/' . $current['gambar']);
                    }
                }
            }
        }
        
        $stmt = $conn->prepare("UPDATE ruangan SET nama_ruangan = ?, gedung = ?, kapasitas = ?, fasilitas = ?, gambar = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssisssi", $nama_ruangan, $gedung, $kapasitas, $fasilitas, $gambar, $status, $id);
        
        if ($stmt->execute()) {
            $success = "Ruangan berhasil diupdate!";
        } else {
            $error = "Gagal mengupdate ruangan.";
        }
    } elseif (isset($_POST['delete'])) {
        $id = (int)$_POST['id'];
        
        // Check if room has bookings
        $check = $conn->query("SELECT COUNT(*) as total FROM peminjaman_ruangan WHERE ruangan_id = $id")->fetch_assoc();
        
        if ($check['total'] > 0) {
            $error = "Tidak dapat menghapus ruangan yang memiliki riwayat peminjaman.";
        } else {
            $ruangan = $conn->query("SELECT gambar FROM ruangan WHERE id = $id")->fetch_assoc();
            
            if ($conn->query("DELETE FROM ruangan WHERE id = $id")) {
                // Delete image if not default
                if ($ruangan['gambar'] !== 'default-room.jpg' && file_exists('../assets/images/' . $ruangan['gambar'])) {
                    unlink('../assets/images/' . $ruangan['gambar']);
                }
                $success = "Ruangan berhasil dihapus!";
            } else {
                $error = "Gagal menghapus ruangan.";
            }
        }
    }
}

// Get all rooms
$ruangan_result = $conn->query("SELECT * FROM ruangan ORDER BY gedung, nama_ruangan");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Ruangan - BOP</title>
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

        /* Dashboard Menu */
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
            font-size: 2.5rem;
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
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1e40af);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid #e5e7eb;
            color: #6b7280;
        }

        .btn-outline:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
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

        table img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
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

        .badge-pending, .badge-warning {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
        }

        .badge-disetujui, .badge-tersedia {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
        }

        .badge-ditolak {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
        }

        /* Alert Enhancement */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .alert::before {
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: 1.2rem;
        }

        .alert-success::before {
            content: '\f058';
        }

        .alert-error::before {
            content: '\f06a';
        }

        /* Modal Enhancement */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            margin: 50px auto;
            padding: 0;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.3s;
        }

        .modal-header {
            padding: 25px;
            border-bottom: 2px solid #f3f4f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            color: #1e40af;
            font-size: 1.5rem;
            margin: 0;
        }

        .close-modal {
            font-size: 2rem;
            color: #6b7280;
            cursor: pointer;
            transition: color 0.3s;
            line-height: 1;
        }

        .close-modal:hover {
            color: #ef4444;
        }

        .modal form {
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1e40af;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* Action Buttons in Table */
        table .btn {
            padding: 8px 16px;
            font-size: 0.9rem;
            margin: 2px;
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
        }

        .btn-info {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }

        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.4);
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

            .card-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            table {
                font-size: 0.85rem;
            }

            table .btn {
                padding: 6px 12px;
                font-size: 0.8rem;
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
                            <h1><i class="fas fa-home"></i> Dashboard Staff BOP </h1>
                            <p>Biro Operasional Perkuliahan - Pengelola Ruangan UMB</p>
                        </div>
                    </div>
                    <div class="user-info">
                        <span><?= htmlspecialchars($staff_nama) ?></span>
                        <a href="../logout.php" class="logout-btn">Logout</a>
                    </div>
                </div>
                <div class="dashboard-menu">
                    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="ruangan.php" class="active"><i class="fas fa-door-open"></i> Kelola Ruangan</a>
                    <a href="jadwalkuliah.php"><i class="fas fa-calendar-alt"></i> Jadwal Perkuliahan</a>
                    <a href="riwayatpeminjaman.php"><i class="fas fa-list"></i> Riwayat Peminjaman Ruangan</a>
                </div>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= $error ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Data Ruangan</h2>
                        <button onclick="showAddModal()" class="btn btn-primary">+ Tambah Ruangan</button>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Gambar</th>
                                    <th>Nama Ruangan</th>
                                    <th>Gedung</th>
                                    <th>Kapasitas</th>
                                    <th>Fasilitas</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $ruangan_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <img src="../assets/images/<?= htmlspecialchars($row['gambar']) ?>" 
                                                 alt="<?= htmlspecialchars($row['nama_ruangan']) ?>">
                                        </td>
                                        <td><strong><?= htmlspecialchars($row['nama_ruangan']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['gedung']) ?></td>
                                        <td><?= $row['kapasitas'] ?> orang</td>
                                        <td style="max-width: 200px;">
                                            <small><?= htmlspecialchars($row['fasilitas']) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= $row['status'] === 'tersedia' ? 'tersedia' : 'warning' ?>">
                                                <?= ucfirst($row['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button onclick='showEditModal(<?= json_encode($row) ?>)' class="btn btn-info">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button onclick="showDeleteModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nama_ruangan']) ?>')" class="btn btn-danger">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Tambah Ruangan</h2>
                <span class="close-modal" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Nama Ruangan *</label>
                    <input type="text" name="nama_ruangan" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Gedung *</label>
                    <input type="text" name="gedung" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Kapasitas *</label>
                    <input type="number" name="kapasitas" class="form-control" min="1" required>
                </div>
                <div class="form-group">
                    <label>Fasilitas *</label>
                    <textarea name="fasilitas" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label>Gambar</label>
                    <input type="file" name="gambar" class="form-control" accept="image/*">
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="button" onclick="closeModal('addModal')" class="btn btn-outline" style="flex: 1; background: #1177deff; color: white">Batal</button>
                    <button type="submit" name="add" class="btn btn-primary" style="flex: 1;">Tambah</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Ruangan</h2>
                <span class="close-modal" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Nama Ruangan *</label>
                    <input type="text" name="nama_ruangan" id="edit_nama" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Gedung *</label>
                    <input type="text" name="gedung" id="edit_gedung" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Kapasitas *</label>
                    <input type="number" name="kapasitas" id="edit_kapasitas" class="form-control" min="1" required>
                </div>
                <div class="form-group">
                    <label>Fasilitas *</label>
                    <textarea name="fasilitas" id="edit_fasilitas" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" id="edit_status" class="form-control" required>
                        <option value="tersedia">Tersedia</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Gambar (kosongkan jika tidak ingin mengubah)</label>
                    <input type="file" name="gambar" class="form-control" accept="image/*">
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="button" onclick="closeModal('editModal')" class="btn btn-outline" style="flex: 1;  background: #1390cfff; color: white">Batal</button>
                    <button type="submit" name="edit" class="btn btn-primary" style="flex: 1;">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Hapus Ruangan</h2>
                <span class="close-modal" onclick="closeModal('deleteModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="delete_id">
                <p>Apakah Anda yakin ingin menghapus ruangan <strong id="delete_nama"></strong>?</p>
                <p style="color: #ef4444; font-size: 0.9rem;">Perhatian: Tindakan ini tidak dapat dibatalkan!</p>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" onclick="closeModal('deleteModal')" class="btn btn-outline" style="flex: 1;  background: #11abe8ff; color: white">Batal</button>
                    <button type="submit" name="delete" class="btn" style="flex: 1; background: #ef4444; color: white;">Hapus</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function showEditModal(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_nama').value = data.nama_ruangan;
            document.getElementById('edit_gedung').value = data.gedung;
            document.getElementById('edit_kapasitas').value = data.kapasitas;
            document.getElementById('edit_fasilitas').value = data.fasilitas;
            document.getElementById('edit_status').value = data.status;
            document.getElementById('editModal').style.display = 'block';
        }

        function showDeleteModal(id, nama) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_nama').textContent = nama;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>