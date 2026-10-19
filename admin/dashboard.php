<?php
require_once '../config/database.php';

// 1. Data Rekapan Angka
$total_laporan = $pdo->query("SELECT COUNT(*) FROM laporan")->fetchColumn();
$count_masuk = $pdo->query("SELECT COUNT(*) FROM laporan WHERE status = 'masuk'")->fetchColumn();
$count_klarifikasi = $pdo->query("SELECT COUNT(*) FROM laporan WHERE status = 'diklarifikasi'")->fetchColumn();
$count_diteruskan = $pdo->query("SELECT COUNT(*) FROM laporan WHERE status = 'diteruskan'")->fetchColumn();
$count_selesai = $pdo->query("SELECT COUNT(*) FROM laporan WHERE status = 'selesai'")->fetchColumn();

// 2. Data Grafik Kategori (Batang)
$stmt_kat = $pdo->query("SELECT kp.nama_kategori, COUNT(l.id) as jumlah FROM kategori_pelanggaran kp LEFT JOIN laporan l ON kp.id = l.kategori_id GROUP BY kp.id");
$data_kategori = $stmt_kat->fetchAll();
$lbl_kat = []; $jml_kat = [];
foreach($data_kategori as $row) { $lbl_kat[] = $row['nama_kategori']; $jml_kat[] = $row['jumlah']; }

// 3. Data Grafik Tren 6 Bulan Terakhir (Garis)
$stmt_tren = $pdo->query("SELECT DATE_FORMAT(waktu_lapor, '%b %Y') as bulan, COUNT(id) as total FROM laporan GROUP BY DATE_FORMAT(waktu_lapor, '%Y-%m') ORDER BY waktu_lapor ASC LIMIT 6");
$data_tren = $stmt_tren->fetchAll();
$lbl_tren = []; $jml_tren = [];
foreach($data_tren as $row) { $lbl_tren[] = $row['bulan']; $jml_tren[] = $row['total']; }

// 4. Data Grafik Status (Donat)
$stmt_stat = $pdo->query("SELECT status, COUNT(id) as total FROM laporan GROUP BY status");
$data_status = $stmt_stat->fetchAll();
$lbl_stat = []; $jml_stat = []; $warna_stat = [];
foreach($data_status as $row) {
    $lbl_stat[] = strtoupper($row['status']);
    $jml_stat[] = $row['total'];
    if ($row['status'] == 'masuk') $warna_stat[] = '#EF4444';
    if ($row['status'] == 'diklarifikasi') $warna_stat[] = '#F59E0B';
    if ($row['status'] == 'diteruskan') $warna_stat[] = '#38BDF8';
    if ($row['status'] == 'selesai') $warna_stat[] = '#10B981';
}
?>

<div style="margin-bottom: 24px;">
    <h1 style="margin: 0; font-size: 24px; color: #0F172A;">Dashboard Pengawasan</h1>
    <p style="margin: 4px 0 0; color: #64748B; font-size: 14px;">Analitik terpadu data pengaduan masyarakat.</p>
</div>

<!-- KARTU REKAPAN -->
<div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 24px;">
    <div class="card-panel" style="margin:0; text-align: center;">
        <div style="font-size: 28px; font-weight: 800; color: #0284C7; font-family: 'JetBrains Mono', monospace;"><?= $total_laporan ?></div>
        <div style="font-size: 11px; color: #64748B; font-weight: 700; margin-top: 4px;">TOTAL LAPORAN</div>
    </div>
    <div class="card-panel" style="margin:0; text-align: center; border-bottom: 4px solid #EF4444;">
        <div style="font-size: 28px; font-weight: 800; color: #EF4444; font-family: 'JetBrains Mono', monospace;"><?= $count_masuk ?></div>
        <div style="font-size: 11px; color: #64748B; font-weight: 700; margin-top: 4px;">BARU MASUK</div>
    </div>
    <div class="card-panel" style="margin:0; text-align: center; border-bottom: 4px solid #F59E0B;">
        <div style="font-size: 28px; font-weight: 800; color: #F59E0B; font-family: 'JetBrains Mono', monospace;"><?= $count_klarifikasi ?></div>
        <div style="font-size: 11px; color: #64748B; font-weight: 700; margin-top: 4px;">PROSES KLARIFIKASI</div>
    </div>
    <div class="card-panel" style="margin:0; text-align: center; border-bottom: 4px solid #38BDF8;">
        <div style="font-size: 28px; font-weight: 800; color: #38BDF8; font-family: 'JetBrains Mono', monospace;"><?= $count_diteruskan ?></div>
        <div style="font-size: 11px; color: #64748B; font-weight: 700; margin-top: 4px;">DITERUSKAN</div>
    </div>
    <div class="card-panel" style="margin:0; text-align: center; border-bottom: 4px solid #10B981;">
        <div style="font-size: 28px; font-weight: 800; color: #10B981; font-family: 'JetBrains Mono', monospace;"><?= $count_selesai ?></div>
        <div style="font-size: 11px; color: #64748B; font-weight: 700; margin-top: 4px;">SELESAI DITINDAK</div>
    </div>
</div>

<!-- GRID VISUALISASI ATAS -->
<div class="dashboard-grid-top">
    <!-- Tren Bulanan -->
    <div class="card-panel" style="margin: 0;">
        <h3 style="margin-top: 0; font-size: 15px;">Tren Laporan (6 Bulan Terakhir)</h3>
        <div style="position: relative; height: 250px; width: 100%;"><canvas id="chartTren"></canvas></div>
    </div>
    <!-- Proporsi Status -->
    <div class="card-panel" style="margin: 0;">
        <h3 style="margin-top: 0; font-size: 15px;">Distribusi Status Laporan</h3>
        <div style="position: relative; height: 250px; width: 100%;"><canvas id="chartStatus"></canvas></div>
    </div>
</div>

<!-- VISUALISASI BAWAH -->
<div class="card-panel">
    <h3 style="margin-top: 0; font-size: 15px;">Statistik Jenis Dugaan Pelanggaran</h3>
    <div style="position: relative; height: 300px; width: 100%;"><canvas id="chartKategori"></canvas></div>
</div>

<script>
  // 1. Grafik Garis (Tren Laporan)
  new Chart(document.getElementById('chartTren').getContext('2d'), {
      type: 'line',
      data: {
          labels: <?= json_encode($lbl_tren) ?>,
          datasets: [{
              label: 'Jumlah Masuk', data: <?= json_encode($jml_tren) ?>,
              borderColor: '#0284C7', backgroundColor: 'rgba(2, 132, 199, 0.1)',
              borderWidth: 2, fill: true, tension: 0.3
          }]
      },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
  });

  // 2. Grafik Donat (Proporsi Status)
  new Chart(document.getElementById('chartStatus').getContext('2d'), {
      type: 'doughnut',
      data: {
          labels: <?= json_encode($lbl_stat) ?>,
          datasets: [{ data: <?= json_encode($jml_stat) ?>, backgroundColor: <?= json_encode($warna_stat) ?>, borderWidth: 0 }]
      },
      options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
  });

  // 3. Grafik Batang (Kategori Pelanggaran)
  new Chart(document.getElementById('chartKategori').getContext('2d'), {
      type: 'bar',
      data: {
          labels: <?= json_encode($lbl_kat) ?>,
          datasets: [{ label: 'Jumlah Laporan', data: <?= json_encode($jml_kat) ?>, backgroundColor: '#1E293B', borderRadius: 4 }]
      },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
  });
</script>