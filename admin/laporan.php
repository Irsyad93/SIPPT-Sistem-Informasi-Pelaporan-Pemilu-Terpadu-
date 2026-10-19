<?php
require_once '../config/database.php';

// 1. Proses Hapus Laporan
if (isset($_GET['hapus'])) {
    $id_hapus = intval($_GET['hapus']);
    try {
        $stmt_file = $pdo->prepare("SELECT bukti FROM laporan WHERE id = :id LIMIT 1");
        $stmt_file->execute([':id' => $id_hapus]);
        $row_file = $stmt_file->fetch();
        
        if ($row_file && !empty($row_file['bukti'])) {
            $path_file = "../uploads/" . $row_file['bukti'];
            if (file_exists($path_file)) {
                unlink($path_file);
            }
        }
        $stmt_del = $pdo->prepare("DELETE FROM laporan WHERE id = :id");
        $stmt_del->execute([':id' => $id_hapus]);

        echo "<script>window.location='index.php?page=laporan&pesan=sukses_hapus';</script>";
        exit;
    } catch (PDOException $e) { }
}

// 2. Tangkap Parameter Filter Tanggal
$tgl_awal = $_GET['tgl_awal'] ?? '';
$tgl_akhir = $_GET['tgl_akhir'] ?? '';

// 3. Susun Query Berdasarkan Filter
$query = "SELECT laporan.*, kategori_pelanggaran.nama_kategori 
          FROM laporan 
          LEFT JOIN kategori_pelanggaran ON laporan.kategori_id = kategori_pelanggaran.id ";
$params = [];

// Jika filter tanggal diisi
if (!empty($tgl_awal) && !empty($tgl_akhir)) {
    $query .= " WHERE DATE(laporan.waktu_lapor) BETWEEN ? AND ? ";
    $params[] = $tgl_awal;
    $params[] = $tgl_akhir;
}

$query .= " ORDER BY laporan.waktu_lapor DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$daftar_laporan = $stmt->fetchAll();
?>

<style>
    /* Styling khusus tombol export */
    .btn-export { padding: 8px 14px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
    .btn-export.print { background: #E2E8F0; color: #475569; }
    .btn-export.print:hover { background: #CBD5E1; }
    .btn-export.excel { background: #DCFCE7; color: #166534; border: 1px solid #BBF7D0; }
    .btn-export.excel:hover { background: #BBF7D0; }
    .btn-export.word { background: #DBEAFE; color: #1E40AF; border: 1px solid #BFDBFE; }
    .btn-export.word:hover { background: #BFDBFE; }
    
    @media print {
        /* Sembunyikan elemen yang tidak perlu saat di-print */
        body { background: #FFF; }
        .sidebar, .btn-export, .form-filter, th:nth-child(7), td:nth-child(7) { display: none !important; }
        .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
        .card-panel { border: none; box-shadow: none; padding: 0; }
    }
</style>

<?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'sukses_hapus'): ?>
    <div style="background: #EFF6FF; color: #1D4ED8; padding: 12px; border-radius: 8px; border: 1px solid #BFDBFE; margin-bottom: 20px; font-size: 14px; font-weight: 500;">
        Laporan yang tidak valid berhasil dihapus dari sistem.
    </div>
<?php endif; ?>

<div class="card-panel">
    <!-- Header Tabel & Tombol Cetak/Download -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px; margin-bottom: 20px;">
        <div>
            <h2 style="margin: 0; font-size: 18px;">Daftar Seluruh Laporan Masuk</h2>
            <p style="margin: 4px 0 0; color: #64748B; font-size: 13px;">Kelola pengaduan warga atau unduh rekapan laporan berdasarkan periode tanggal.</p>
        </div>
        
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn-export print">Cetak</button>
            <a href="export.php?format=excel&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" class="btn-export excel">Download Excel</a>
            <a href="export.php?format=word&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" class="btn-export word">Download Word</a>
        </div>
    </div>

    <!-- Form Filter Tanggal -->
    <form class="form-filter" method="GET" action="index.php" style="background: #F8FAFC; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #E2E8F0; display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
        <input type="hidden" name="page" value="laporan">
        
        <div>
            <label style="font-size: 12px; font-weight: 700; color: #475569; display: block; margin-bottom: 6px;">DARI TANGGAL</label>
            <input type="date" name="tgl_awal" value="<?= $tgl_awal ?>" style="padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-family: inherit;">
        </div>
        
        <div>
            <label style="font-size: 12px; font-weight: 700; color: #475569; display: block; margin-bottom: 6px;">SAMPAI TANGGAL</label>
            <input type="date" name="tgl_akhir" value="<?= $tgl_akhir ?>" style="padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-family: inherit;">
        </div>
        
        <div style="display: flex; gap: 10px;">
            <button type="submit" style="background: #0F172A; color: #FFF; padding: 11px 20px; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; font-family: inherit;">Terapkan Filter</button>
            
            <?php if(!empty($tgl_awal)): ?>
                <a href="index.php?page=laporan" style="padding: 11px 20px; color: #EF4444; font-weight: 700; text-decoration: none; border: 1px solid #FCA5A5; border-radius: 8px; background: #FEF2F2;">Reset</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Tabel Data Laporan -->
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
            <thead>
                <tr style="border-bottom: 2px solid #E2E8F0; color: #475569;">
                    <th style="padding: 12px;">NO</th>
                    <th style="padding: 12px;">WAKTU LAPOR</th>
                    <th style="padding: 12px;">PELAPOR</th>
                    <th style="padding: 12px;">WILAYAH</th>
                    <th style="padding: 12px;">KATEGORI</th>
                    <th style="padding: 12px;">STATUS</th>
                    <th style="padding: 12px; text-align: center;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($daftar_laporan) > 0): ?>
                    <?php $no = 1; foreach ($daftar_laporan as $row): ?>
                    <tr style="border-bottom: 1px solid #F1F5F9;">
                        <td style="padding: 12px; font-weight: 600;"><?= $no++ ?></td>
                        <td style="padding: 12px;"><?= date('d/m/Y H:i', strtotime($row['waktu_lapor'])) ?></td>
                        <td style="padding: 12px;"><?= htmlspecialchars($row['nama_pelapor'] ?: 'Anonim') ?></td>
                        <td style="padding: 12px;">
                            <strong>Kec. <?= htmlspecialchars($row['kecamatan'] ?: '-') ?></strong><br>
                            <span style="color: #64748B; font-size: 11px;">Desa: <?= htmlspecialchars($row['desa'] ?: '-') ?></span>
                        </td>
                        <td style="padding: 12px;"><?= htmlspecialchars($row['nama_kategori']) ?></td>
                        <td style="padding: 12px;">
                            <span style="padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 800; 
                                background: <?= $row['status'] == 'selesai' ? '#ECFDF5' : ($row['status'] == 'diklarifikasi' ? '#FFFBEB' : '#FEF2F2') ?>; 
                                color: <?= $row['status'] == 'selesai' ? '#10B981' : ($row['status'] == 'diklarifikasi' ? '#F59E0B' : ($row['status'] == 'diteruskan' ? '#38BDF8' : '#EF4444')) ?>;">
                                <?= strtoupper($row['status']) ?>
                            </span>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <div style="display: flex; gap: 6px; justify-content: center;">
                                <a href="detail.php?id=<?= $row['id'] ?>" class="btn-primary-soft">Detail</a>
                                <a href="index.php?page=laporan&hapus=<?= $row['id'] ?>" class="btn-danger-soft" onclick="return confirm('Adakah anda yakin ingin menghapus laporan ini?')">Hapus</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align: center; padding: 30px;">Tiada data laporan pada periode tersebut.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>