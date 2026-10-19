<?php
session_start();
// Pastikan admin login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';

$format = $_GET['format'] ?? 'excel';
$tgl_awal = $_GET['tgl_awal'] ?? '';
$tgl_akhir = $_GET['tgl_akhir'] ?? '';

// Susun Query
$query = "SELECT laporan.*, kategori_pelanggaran.nama_kategori 
          FROM laporan 
          LEFT JOIN kategori_pelanggaran ON laporan.kategori_id = kategori_pelanggaran.id ";
$params = [];

if (!empty($tgl_awal) && !empty($tgl_akhir)) {
    $query .= " WHERE DATE(laporan.waktu_lapor) BETWEEN ? AND ? ";
    $params[] = $tgl_awal;
    $params[] = $tgl_akhir;
}
$query .= " ORDER BY laporan.waktu_lapor DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$daftar_laporan = $stmt->fetchAll();

// Nama File Output
$filename = "Rekap_Laporan_SIPPT_" . date('Ymd');

// Atur Header berdasarkan format pilihan
if ($format == 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename.xls\"");
} else {
    header("Content-Type: application/vnd.ms-word");
    header("Content-Disposition: attachment; filename=\"$filename.doc\"");
}
?>
<!-- Deklarasi XML khusus agar MS Word dapat membaca styling halaman -->
<html xmlns:o="urn:schemas-microsoft-com:office:office" 
      xmlns:w="urn:schemas-microsoft-com:office:word" 
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="utf-8">
    <style>
        /* Pengaturan khusus untuk Microsoft Word agar kertas menjadi Landscape */
        @page WordSection1 {
            size: 841.9pt 595.3pt; /* Ukuran A4 Mendatar (Landscape) */
            mso-page-orientation: landscape;
            margin: 0.5in; /* Margin keliling 0.5 inci */
        }
        div.WordSection1 { 
            page: WordSection1; 
        }
        
        /* Pengaturan Tabel agar rapi dan teks otomatis turun/wrap */
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto; 
        }
        th, td {
            border: 1px solid #000000;
            padding: 6px;
            font-size: 11pt;
            font-family: Arial, sans-serif;
            vertical-align: top;
            word-wrap: break-word; /* Memaksa teks turun, tidak memanjangkan tabel */
        }
        th {
            background-color: #E2E8F0;
            text-align: center;
        }
        .text-center { text-align: center; }
        .text-uppercase { text-transform: uppercase; }
    </style>
</head>
<body>
    <!-- Bungkus konten dalam div WordSection1 agar orientasi Landscape aktif -->
    <div class="WordSection1">
        <h2 class="text-center" style="margin-bottom: 5px;">REKAPITULASI LAPORAN PENGAWASAN PEMILU</h2>
        <p class="text-center" style="margin-top: 0; margin-bottom: 20px;">Bawaslu Kabupaten Kendal</p>
        
        <?php if(!empty($tgl_awal) && !empty($tgl_akhir)): ?>
            <p><strong>Periode:</strong> <?= date('d/m/Y', strtotime($tgl_awal)) ?> s/d <?= date('d/m/Y', strtotime($tgl_akhir)) ?></p>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th width="3%">NO</th>
                    <th width="10%">KODE</th>
                    <th width="10%">WAKTU</th>
                    <th width="10%">PELAPOR</th>
                    <th width="10%">WILAYAH</th>
                    <th width="10%">TPS</th>
                    <th width="12%">KATEGORI</th>
                    <th width="25%">DESKRIPSI KEJADIAN</th>
                    <th width="10%">STATUS</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($daftar_laporan) > 0): ?>
                    <?php $no = 1; foreach ($daftar_laporan as $row): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['kode_unik'] ?? '-') ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($row['waktu_lapor'])) ?></td>
                        <td><?= htmlspecialchars($row['nama_pelapor'] ?: 'Anonim') ?></td>
                        <td>
                            Kec. <?= htmlspecialchars($row['kecamatan']) ?><br>
                            Desa <?= htmlspecialchars($row['desa']) ?>
                        </td>
                        <td class="text-center"><?= htmlspecialchars($row['lokasi_kejadian']) ?></td>
                        <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
                        <td><?= htmlspecialchars($row['deskripsi']) ?></td>
                        <td class="text-center text-uppercase"><strong><?= $row['status'] ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="text-center" style="padding: 20px;">Tidak ada data laporan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>