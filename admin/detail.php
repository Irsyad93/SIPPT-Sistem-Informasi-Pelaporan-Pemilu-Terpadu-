<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../config/database.php';

// Ambil ID laporan dari parameter URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$pesan_sukses = '';$pesan_error = '';

// Proses jika form tindak lanjut disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status_baru = trim($_POST['status']);
    // Validasi status yang diizinkan
    $valid_status = ['masuk', 'diklarifikasi', 'diteruskan', 'selesai'];
    
    if (in_array($status_baru,$valid_status)) {
        try {
            // Update status laporan di database
            $update =$pdo->prepare("
                UPDATE laporan 
                SET status = :status 
                WHERE id = :id
            ");
            $update->execute([
                ':status' => $status_baru,
                ':id'     => $id
            ]);

            $pesan_sukses = "Tindak lanjut dan status laporan berhasil diperbarui!";
        } catch (PDOException $e) {$pesan_error = "Gagal memperbarui database: " . $e->getMessage();
        }
    } else {
        $pesan_error = "Pilihan status tidak valid!";
    }
}

try {
    // Ambil data laporan beserta nama kategorinya
    $stmt =$pdo->prepare("
        SELECT laporan.*, kategori_pelanggaran.nama_kategori 
        FROM laporan 
        JOIN kategori_pelanggaran ON laporan.kategori_id = kategori_pelanggaran.id 
        WHERE laporan.id = :id LIMIT 1
    ");
    $stmt->execute([':id' =>$id]);
    $laporan =$stmt->fetch();

    if (!$laporan) {
        die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'><h3>Data laporan tidak ditemukan!</h3><a href='index.php'>&larr; Kembali ke Dasbor</a></div>");
    }
} catch (PDOException $e) {
    die("Kesalahan database: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail & Tindak Lanjut Laporan - Panel Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #F8FAFC;
            margin: 0;
            padding: 30px;
            color: #334155;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #FFFFFF;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #E2E8F0;
        }
        .header-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #F1F5F9;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header-title h2 { margin: 0; color: #0F172A; font-size: 22px; }
        .back-btn {
            background: #64748B;
            color: white;
            padding: 8px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .back-btn:hover { background: #475569; }
        
        .info-group { margin-bottom: 20px; }
        .info-label { font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase; margin-bottom: 6px; display: block; }
        .info-value { font-size: 15px; color: #1E293B; background: #F8FAFC; padding: 12px; border-radius: 8px; border: 1px solid #E2E8F0; }
        
        /* Kotak Form Tindak Lanjut */
        .tindak-lanjut-card {
            background: #F8FAFC;
            border: 1px solid #CBD5E1;
            padding: 20px;
            border-radius: 12px;
            margin-top: 30px;
        }
        .tindak-lanjut-card h3 {
            margin-top: 0;
            color: #0F172A;
            font-size: 18px;
            margin-bottom: 15px;
            border-bottom: 1px solid #E2E8F0;
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1.5px solid #CBD5E1;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            outline: none;
            background: #FFFFFF;
            transition: all 0.2s;
        }
        .form-control:focus {
            border-color: #0284C7;
            box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.1);
        }
        .btn-submit {
            background: #0284C7;
            color: white;
            border: none;
            padding: 11px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-submit:hover { background: #0369A1; }

        .alert-success {
            background: #EFF6FF;
            color: #1D4ED8;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #BFDBFE;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        .alert-error {
            background: #FEF2F2;
            color: #DC2626;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #FCA5A5;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        /* Area Lampiran Bukti */
        .bukti-box {
            margin-top: 15px;
            text-align: center;
            background: #F1F5F9;
            padding: 20px;
            border-radius: 12px;
            border: 1px dashed #CBD5E1;
        }
        .bukti-img {
            max-width: 100%;
            max-height: 350px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            cursor: pointer;
            transition: transform 0.2s;
        }
        .bukti-img:hover { transform: scale(1.02); }
        
        .action-file {
            margin-top: 15px;
            display: flex;
            justify-content: center;
            gap: 12px;
        }
        .btn-action {
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            border: none;
        }
        .btn-download { background: #0284C7; color: white; }
        .btn-download:hover { background: #0369A1; }
        
        /* Modal Lightbox */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            padding-top: 50px;
            left: 0; top: 0; width: 100%; height: 100%;
            background-color: rgba(15, 23, 42, 0.9);
            text-align: center;
        }
        .modal-content {
            max-width: 90%;
            max-height: 85vh;
            border-radius: 8px;
        }
        .close-modal {
            position: absolute;
            top: 20px; right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
        .close-modal:hover { color: #38BDF8; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-title">
        <h2>Detail & Tindak Lanjut Laporan</h2>
        <a href="index.php" class="back-btn">Kembali ke Dasbor</a>
    </div>

    <?php if (!empty($pesan_sukses)): ?>
        <div class="alert-success"><?= $pesan_sukses ?></div>
    <?php endif; ?>

    <?php if (!empty($pesan_error)): ?>
        <div class="alert-error"><?= $pesan_error ?></div>
    <?php endif; ?>

    <div class="info-group">
        <span class="info-label">Nama Pelapor</span>
        <div class="info-value"><?= !empty($laporan['nama_pelapor']) ? htmlspecialchars($laporan['nama_pelapor']) : '<em>(Anonim)</em>' ?></div>
    </div>

    <div class="info-group">
        <span class="info-label">Kategori Pelanggaran</span>
        <div class="info-value"><strong><?= htmlspecialchars($laporan['nama_kategori']) ?></strong></div>
    </div>

    <div class="info-group">
        <span class="info-label">Wilayah Administratif</span>
        <div class="info-value">
            Kecamatan: <strong><?= htmlspecialchars($laporan['kecamatan'] ?: '-') ?></strong><br>
            Desa/Kelurahan: <strong><?= htmlspecialchars($laporan['desa'] ?: '-') ?></strong>
        </div>
    </div>

    <div class="info-group">
        <span class="info-label">Lokasi Kejadian / TPS</span>
        <div class="info-value"><?= htmlspecialchars($laporan['lokasi_kejadian']) ?></div>
    </div>

    <div class="info-group">
        <span class="info-label">Waktu Lapor</span>
        <div class="info-value"><?= $laporan['waktu_lapor'] ?></div>
    </div>

    <div class="info-group">
        <span class="info-label">Status Saat Ini</span>
        <div class="info-value">
            <span style="padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 13px; background: #E0F2FE; color: #0369A1;">
                <?= strtoupper($laporan['status']) ?>
            </span>
        </div>
    </div>

    <div class="info-group">
        <span class="info-label">Deskripsi Kejadian</span>
        <div class="info-value" style="white-space: pre-line;"><?= htmlspecialchars($laporan['deskripsi']) ?></div>
    </div>

    <!-- Bagian Bukti Lampiran dari Folder Uploads -->
    <div class="info-group">
        <span class="info-label">Bukti Lampiran</span>
        <div class="bukti-box">
            <?php if (!empty($laporan['bukti'])): ?>
                <?php 
                    $file_path = "../uploads/" . $laporan['bukti'];
                    $ekstensi = strtolower(pathinfo($laporan['bukti'], PATHINFO_EXTENSION));
                ?>
                
                <?php if (in_array($ekstensi, ['jpg', 'jpeg', 'png'])): ?>
                    <img src="<?= $file_path ?>" alt="Bukti Laporan" class="bukti-img" onclick="openModal('<?= $file_path ?>')" title="Klik untuk memperbesar">
                    <p style="font-size: 12px; color: #64748B; margin: 8px 0 0;">(Klik gambar untuk memperbesar tampilan)</p>
                <?php elseif ($ekstensi === 'mp4'): ?>
                    <video controls style="max-width: 100%; max-height: 350px; border-radius: 8px;">
                        <source src="<?= $file_path ?>" type="video/mp4">
                        Browser Anda tidak mendukung pemutaran video.
                    </video>
                <?php else: ?>
                    <p style="color: #64748B; font-style: italic;">Berkas terunggah tidak berupa gambar atau video standar.</p>
                <?php endif; ?>

                <div class="action-file">
                    <a href="<?= $file_path ?>" download class="btn-action btn-download">
                        Unduh Berkas Bukti
                    </a>
                </div>
            <?php else: ?>
                <p style="color: #64748B; font-style: italic; margin: 0;">Tidak ada file bukti/lampiran yang diunggah.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Form Tindak Lanjut Admin -->
    <div class="tindak-lanjut-card">
        <h3>Form Tindak Lanjut Laporan</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label>Ubah Status Laporan</label>
                <select name="status" class="form-control" required>
                    <option value="masuk" <?= $laporan['status'] === 'masuk' ? 'selected' : '' ?>>Masuk (Belum Diproses)</option>
                    <option value="diklarifikasi" <?= $laporan['status'] === 'diklarifikasi' ? 'selected' : '' ?>>Diklarifikasi</option>
                    <option value="diteruskan" <?= $laporan['status'] === 'diteruskan' ? 'selected' : '' ?>>Diteruskan ke Instansi Terkait</option>
                    <option value="selesai" <?= $laporan['status'] === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                </select>
            </div>

            <button type="submit" class="btn-submit">Simpan Tindak Lanjut</button>
        </form>
    </div>
</div>

<!-- Elemen Modal Perbesar Gambar -->
<div id="imageModal" class="modal" onclick="closeModal()">
    <span class="close-modal">&times;</span>
    <img class="modal-content" id="imgExpanded">
</div>

<script>
    function openModal(imgSrc) {
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('imgExpanded');
        modal.style.display = "block";
        modalImg.src = imgSrc;
    }

    function closeModal() {
        document.getElementById('imageModal').style.display = "none";
    }
</script>

</body>
</html>