<?php
require_once '../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Ambil data sesi
$user_id   = $_SESSION['user_id'];
$user_nama = $_SESSION['user_nama'];
$user_nim  = $_SESSION['user_nim'];

// Handle booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {
    
    $fasilitas_id     = (int)$_POST['fasilitas_id'];
    $tanggal_mulai    = input($_POST['tanggal_mulai']);
    $tanggal_selesai  = input($_POST['tanggal_selesai']);
    $jam_mulai        = input($_POST['jam_mulai']);
    $jam_selesai      = input($_POST['jam_selesai']);
    $jumlah_pinjam    = (int)$_POST['jumlah_pinjam'];
    $keperluan        = input($_POST['keperluan']);

    // Info fasilitas
    $fac_stmt = $conn->prepare("SELECT jumlah, memerlukan_proposal, nama_fasilitas FROM fasilitas WHERE id = ?");
    $fac_stmt->bind_param("i", $fasilitas_id);
    $fac_stmt->execute();
    $fac_res = $fac_stmt->get_result()->fetch_assoc();

    if (!$fac_res) {
        $error = "Fasilitas tidak ditemukan!";
    } else {
        $total_available = $fac_res['jumlah'];
        $memerlukan_proposal = $fac_res['memerlukan_proposal'];

        // Hitung durasi peminjaman
        $date1 = new DateTime($tanggal_mulai);
        $date2 = new DateTime($tanggal_selesai);
        $durasi_hari = $date2->diff($date1)->days;
        
        $dokumen_pendukung = null;
        
        // Validasi dokumen: wajib jika durasi > 1 hari ATAU fasilitas memerlukan proposal
        if ($durasi_hari > 0 || $memerlukan_proposal == 1) {
            if (!isset($_FILES['dokumen_pendukung']) || $_FILES['dokumen_pendukung']['error'] !== 0) {
                if ($memerlukan_proposal == 1) {
                    $error = "Fasilitas ini wajib melampirkan proposal kegiatan!";
                } else {
                    $error = "Peminjaman lebih dari 1 hari wajib melampirkan dokumen pendukung!";
                }
            } else {
                $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
                $filename = $_FILES['dokumen_pendukung']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (!in_array($ext, $allowed)) {
                    $error = "Format file tidak didukung. Gunakan: PDF, DOC, DOCX, JPG, JPEG, PNG";
                } elseif ($_FILES['dokumen_pendukung']['size'] > 5000000) { // 5MB
                    $error = "Ukuran file maksimal 5MB";
                } else {
                    $new_name = 'doc_fasilitas_' . time() . '_' . $user_id . '.' . $ext;
                    $upload_path = '../assets/documents/' . $new_name;
                    
                    // Buat folder jika belum ada
                    if (!file_exists('../assets/documents/')) {
                        mkdir('../assets/documents/', 0777, true);
                    }
                    
                    if (move_uploaded_file($_FILES['dokumen_pendukung']['tmp_name'], $upload_path)) {
                        $dokumen_pendukung = $new_name;
                    } else {
                        $error = "Gagal mengupload dokumen";
                    }
                }
            }
        }

        if (!$error) {
            // Validasi
            if (strtotime($tanggal_mulai) > strtotime($tanggal_selesai)) {
                $error = "Tanggal selesai harus lebih besar dari tanggal mulai!";
            } elseif (strtotime($jam_mulai) >= strtotime($jam_selesai)) {
                $error = "Jam selesai harus lebih besar dari jam mulai!";
            } elseif ($jumlah_pinjam < 1) {
                $error = "Jumlah minimal 1 unit!";
            } elseif ($jumlah_pinjam > $total_available) {
                $error = "Jumlah yang diminta melebihi stok tersedia!";
            } else {

                $check_stmt = $conn->prepare("
                    SELECT SUM(jumlah_pinjam) AS total_dipinjam
                    FROM peminjaman_fasilitas
                    WHERE fasilitas_id = ?
                    AND status = 'disetujui'
                    AND NOT (
                        tanggal_selesai < ? OR tanggal_mulai > ?
                    )
                    AND NOT (
                        jam_selesai <= ? OR jam_mulai >= ?
                    )
                ");

                $check_stmt->bind_param(
                    "issss",
                    $fasilitas_id,
                    $tanggal_mulai,
                    $tanggal_selesai,
                    $jam_mulai,
                    $jam_selesai
                );

                $check_stmt->execute();
                $check_res = $check_stmt->get_result()->fetch_assoc();

                $already_borrowed = $check_res['total_dipinjam'] ?? 0;
                $available = $total_available - $already_borrowed;

                if ($jumlah_pinjam > $available) {
                    $error = "Hanya tersedia {$available} unit pada waktu tersebut.";
                } else {

                    $insert_stmt = $conn->prepare("
                        INSERT INTO peminjaman_fasilitas
                        (user_id, fasilitas_id, tanggal_mulai, tanggal_selesai, jam_mulai, jam_selesai, jumlah_pinjam, keperluan, dokumen_pendukung, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                    ");

                    $insert_stmt->bind_param(
                        "iissssiss",
                        $user_id, 
                        $fasilitas_id, 
                        $tanggal_mulai, 
                        $tanggal_selesai, 
                        $jam_mulai, 
                        $jam_selesai, 
                        $jumlah_pinjam, 
                        $keperluan,
                        $dokumen_pendukung
                    );

                    if ($insert_stmt->execute()) {
                        $success = "Peminjaman fasilitas berhasil diajukan. Menunggu persetujuan BSP.";
                    } else {
                        $error = "Gagal mengajukan peminjaman.";
                    }
                }
            }
        }
    }
}

// Ambil semua fasilitas (termasuk yang rusak)
$fasilitas_result = $conn->query("SELECT * FROM fasilitas ORDER BY kondisi DESC, kategori, nama_fasilitas");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peminjaman Fasilitas - UMB Booking System</title>
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

        /* Facility Card Enhancement */
        .facility-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .facility-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .facility-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }

        .facility-card.rusak {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .facility-card.rusak::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            z-index: 1;
        }

        .facility-card.rusak:hover {
            transform: none;
        }

        .facility-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .facility-card-content {
            padding: 20px;
        }

        .facility-category {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e40af;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 12px;
        }

        .facility-card h3 {
            color: #1e40af;
            font-size: 1.3rem;
            margin-bottom: 10px;
        }

        .facility-stock {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .facility-stock.baik {
            color: #10b981;
        }

        .facility-stock.rusak {
            color: #ef4444;
        }

        .facility-stock i {
            font-size: 1.2rem;
        }

        .facility-description {
            color: #6b7280;
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-clamp: 2;
            overflow: hidden;
        }

        .kondisi-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .kondisi-badge.baik {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
        }

        .kondisi-badge.rusak {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
        }

        .proposal-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-left: 5px;
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
        }

        /* Modal Enhancement */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 600px;
            margin: 50px auto;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.3s;
            max-height: 90vh;
            overflow-y: auto;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-hint {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 5px;
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-input-wrapper input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: #f9fafb;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-input-label:hover {
            background: #f3f4f6;
            border-color: #3b82f6;
        }

        .file-name {
            font-size: 0.9rem;
            color: #6b7280;
        }

        #dokumen_required_notice_fasilitas {
            display: none;
            background: #fef3c7;
            color: #92400e;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-top: 10px;
        }

        #proposal_required_notice {
            display: none;
            background: #fef3c7;
            color: #92400e;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-top: 10px;
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
            justify-content: center;
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

        .btn-primary:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
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

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-nav {
                flex-direction: column;
                gap: 15px;
            }

            .facility-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 95%;
                margin: 20px auto;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="dashboard-header">
            <div class="container">
                <div class="dashboard-nav">
                    <div class="dashboard-title">
                        <h1><i class="fas fa-tools"></i> Peminjaman Fasilitas</h1>
                        <p>Pilih fasilitas yang ingin Anda pinjam</p>
                    </div>
                    <div class="user-info">
                        <span><?= htmlspecialchars($user_nama) ?></span>
                        <a href="../logout.php" class="logout-btn">Logout</a>
                    </div>
                </div>
                <div class="dashboard-menu">
                    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="ruangan.php"><i class="fas fa-door-open"></i> Pinjam Ruangan</a>
                    <a href="fasilitas.php" class="active"><i class="fas fa-tools"></i> Pinjam Fasilitas</a>
                    <a href="riwayat.php"><i class="fas fa-history"></i> Riwayat Peminjaman</a>
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

                <div class="facility-grid">
                    <?php while ($fasilitas = $fasilitas_result->fetch_assoc()): ?>
                        <div class="facility-card <?= $fasilitas['kondisi'] === 'rusak' ? 'rusak' : '' ?>" 
                             onclick="<?= $fasilitas['kondisi'] === 'baik' ? "openBookingModal(" . $fasilitas['id'] . ", '" . htmlspecialchars($fasilitas['nama_fasilitas']) . "', " . $fasilitas['jumlah'] . ", " . $fasilitas['memerlukan_proposal'] . ")" : '' ?>">
                            <img src="../assets/images/<?= htmlspecialchars($fasilitas['gambar']) ?>" 
                                 alt="<?= htmlspecialchars($fasilitas['nama_fasilitas']) ?>">
                            <div class="facility-card-content">
                                <span class="facility-category">
                                    <i class="fas fa-tag"></i> <?= htmlspecialchars($fasilitas['kategori']) ?>
                                </span>
                                <?php if ($fasilitas['memerlukan_proposal'] == 1): ?>
                                    <span class="proposal-badge">
                                        <i class="fas fa-file-alt"></i> Perlu Proposal
                                    </span>
                                <?php endif; ?>
                                <h3><?= htmlspecialchars($fasilitas['nama_fasilitas']) ?></h3>
                                <span class="kondisi-badge <?= $fasilitas['kondisi'] ?>">
                                    <i class="fas fa-<?= $fasilitas['kondisi'] === 'baik' ? 'check-circle' : 'times-circle' ?>"></i>
                                    <?= ucfirst($fasilitas['kondisi']) ?>
                                </span>
                                <div class="facility-stock <?= $fasilitas['kondisi'] ?>">
                                    <i class="fas fa-boxes"></i>
                                    <span>Tersedia: <?= $fasilitas['jumlah'] ?> unit</span>
                                </div>
                                <p class="facility-description">
                                    <?= htmlspecialchars($fasilitas['deskripsi']) ?>
                                </p>
                                <?php if ($fasilitas['kondisi'] === 'baik'): ?>
                                <button class="btn btn-primary" style="width: 100%;">
                                    <i class="fas fa-calendar-plus"></i> Pinjam Fasilitas
                                </button>
                                <?php else: ?>
                                <button class="btn btn-primary" disabled style="width: 100%;">
                                    <i class="fas fa-ban"></i> Tidak Tersedia
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"><i class="fas fa-tools"></i> Pinjam Fasilitas</h2>
                <span class="close-modal" onclick="closeBookingModal()">&times;</span>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="fasilitas_id" id="fasilitas_id">
                <input type="hidden" name="memerlukan_proposal_hidden" id="memerlukan_proposal_hidden">
                
                <div class="form-group">
                    <label><i class="fas fa-sort-numeric-up"></i> Jumlah Unit *</label>
                    <input type="number" name="jumlah_pinjam" id="jumlah_pinjam" class="form-control" 
                           min="1" value="1" required>
                    <small class="form-hint">Maksimal: <strong><span id="max_jumlah"></span> unit</strong></small>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-calendar-day"></i> Tanggal Mulai *</label>
                    <input type="date" name="tanggal_mulai" id="tanggal_mulai_fasilitas" class="form-control" required 
                           min="<?= date('Y-m-d') ?>" onchange="checkDurationFasilitas()">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-calendar-check"></i> Tanggal Selesai *</label>
                    <input type="date" name="tanggal_selesai" id="tanggal_selesai_fasilitas" class="form-control" required 
                           min="<?= date('Y-m-d') ?>" onchange="checkDurationFasilitas()">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Jam Mulai *</label>
                        <input type="time" name="jam_mulai" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Jam Selesai *</label>
                        <input type="time" name="jam_selesai" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Keperluan *</label>
                    <textarea name="keperluan" class="form-control" rows="4" 
                              placeholder="Jelaskan keperluan peminjaman fasilitas..." required></textarea>
                </div>
            <div class="form-group" id="dokumen_group_fasilitas">
                <label><i class="fas fa-file-upload"></i> Dokumen Pendukung / Proposal <span id="dokumen_required_fasilitas" style="color: #ef4444;"></span></label>
                <div class="file-input-wrapper">
                    <input type="file" name="dokumen_pendukung" id="dokumen_pendukung_fasilitas" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" onchange="updateFileNameFasilitas(this)">
                    <div class="file-input-label">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 1.5rem; color: #3b82f6;"></i>
                        <div>
                            <div class="file-name-fasilitas">Pilih file (PDF, DOC, DOCX, JPG, PNG)</div>
                            <small style="color: #9ca3af;">Maksimal 5MB</small>
                        </div>
                    </div>
                </div>
                <div id="dokumen_required_notice_fasilitas">
                    <i class="fas fa-exclamation-triangle"></i> Peminjaman lebih dari 1 hari wajib melampirkan dokumen pendukung (surat izin, proposal kegiatan, dll)
                </div>
                <div id="proposal_required_notice">
                    <i class="fas fa-exclamation-triangle"></i> Fasilitas ini wajib melampirkan proposal kegiatan yang mencakup: tujuan acara, rundown kegiatan, jumlah peserta, dan penanggung jawab
                </div>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="button" onclick="closeBookingModal()" 
                        class="btn btn-outline" style="flex: 1;">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" name="book" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-paper-plane"></i> Ajukan Peminjaman
                </button>
            </div>
        </form>
    </div>
</div>

    <script>
        function openBookingModal(fasilitasId, namaFasilitas, maxJumlah, memerlukanProposal) {
            document.getElementById('bookingModal').style.display = 'block';
            document.getElementById('fasilitas_id').value = fasilitasId;
            document.getElementById('memerlukan_proposal_hidden').value = memerlukanProposal;
            document.getElementById('modalTitle').innerHTML =
                '<i class="fas fa-tools"></i> Pinjam ' + namaFasilitas;
            document.getElementById('max_jumlah').textContent = maxJumlah;
            document.getElementById('jumlah_pinjam').max = maxJumlah;
            document.getElementById('jumlah_pinjam').value = 1;
            
            // Jika memerlukan proposal, tampilkan notice dan set required
            if (memerlukanProposal == 1) {
                document.getElementById('proposal_required_notice').style.display = 'block';
                document.getElementById('dokumen_required_fasilitas').textContent = '*';
                document.getElementById('dokumen_pendukung_fasilitas').required = true;
            } else {
                document.getElementById('proposal_required_notice').style.display = 'none';
                checkDurationFasilitas(); // Check duration untuk kasus normal
            }
        }

        function closeBookingModal() {
            document.getElementById('bookingModal').style.display = 'none';
            document.getElementById('tanggal_mulai_fasilitas').value = '';
            document.getElementById('tanggal_selesai_fasilitas').value = '';
            document.getElementById('dokumen_pendukung_fasilitas').value = '';
            document.querySelector('.file-name-fasilitas').textContent =
                'Pilih file (PDF, DOC, DOCX, JPG, PNG)';
            document.getElementById('dokumen_required_notice_fasilitas').style.display = 'none';
            document.getElementById('proposal_required_notice').style.display = 'none';
            document.getElementById('dokumen_required_fasilitas').textContent = '';
        }

        function checkDurationFasilitas() {
            const startDate = document.getElementById('tanggal_mulai_fasilitas').value;
            const endDate = document.getElementById('tanggal_selesai_fasilitas').value;
            const memerlukanProposal = document.getElementById('memerlukan_proposal_hidden').value;

            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                // Jika fasilitas memerlukan proposal, dokumen selalu wajib
                if (memerlukanProposal == 1) {
                    document.getElementById('dokumen_required_fasilitas').textContent = '*';
                    document.getElementById('dokumen_pendukung_fasilitas').required = true;
                    document.getElementById('proposal_required_notice').style.display = 'block';
                } else if (diffDays > 0) {
                    // Jika lebih dari 1 hari, dokumen wajib
                    document.getElementById('dokumen_required_fasilitas').textContent = '*';
                    document.getElementById('dokumen_required_notice_fasilitas').style.display = 'block';
                    document.getElementById('dokumen_pendukung_fasilitas').required = true;
                } else {
                    // Jika 1 hari atau kurang dan tidak perlu proposal, dokumen optional
                    document.getElementById('dokumen_required_fasilitas').textContent = '';
                    document.getElementById('dokumen_required_notice_fasilitas').style.display = 'none';
                    document.getElementById('dokumen_pendukung_fasilitas').required = false;
                }
            }
        }

        function updateFileNameFasilitas(input) {
            const fileName = input.files[0]
                ? input.files[0].name
                : 'Pilih file (PDF, DOC, DOCX, JPG, PNG)';
            document.querySelector('.file-name-fasilitas').textContent = fileName;
        }

        window.onclick = function (event) {
            const modal = document.getElementById('bookingModal');
            if (event.target === modal) {
                closeBookingModal();
            }
        };
    </script>
</body>
</html>
