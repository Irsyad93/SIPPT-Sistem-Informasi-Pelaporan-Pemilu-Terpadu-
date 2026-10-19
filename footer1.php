<?php
// Memanggil file koneksi database
require_once 'config/database.php';

// 1. Mengambil data kategori pelanggaran dari database untuk form
$stmt_kat = $pdo->query("SELECT * FROM kategori_pelanggaran");
$kategori_list = $stmt_kat->fetchAll();

// 2. Mengambil data laporan terbaru untuk "Live Tindak Lanjut"
$stmt_laporan = $pdo->query("
    SELECT laporan.*, kategori_pelanggaran.nama_kategori 
    FROM laporan 
    LEFT JOIN kategori_pelanggaran ON laporan.kategori_id = kategori_pelanggaran.id 
    ORDER BY laporan.waktu_lapor DESC 
    LIMIT 5
");
$live_laporan = $stmt_laporan->fetchAll();

// 3. Mengambil data laporan yang memiliki koordinat untuk peta interaktif secara real-time
$stmt_peta = $pdo->query("
    SELECT laporan.*, kategori_pelanggaran.nama_kategori 
    FROM laporan 
    LEFT JOIN kategori_pelanggaran ON laporan.kategori_id = kategori_pelanggaran.id 
    WHERE laporan.latitude IS NOT NULL AND laporan.longitude IS NOT NULL
");
$data_peta = $stmt_peta->fetchAll();

// 4. Statistik ringkas
$total_laporan = $pdo->query("SELECT COUNT(*) FROM laporan")->fetchColumn();
$total_kecamatan = $pdo->query("SELECT COUNT(DISTINCT kecamatan) FROM laporan WHERE kecamatan IS NOT NULL AND kecamatan != ''")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Portal Pengawasan Pemilu — Bawaslu Kendal</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- NAVBAR UTAMA -->
<header class="site-header">
    <nav class="public-navbar">
        <a href="index.php" class="nav-brand">
            <div class="brand-icon"><img src="logo.png" alt="Logo Bawaslu Kendal"></div>
            <div>
                <h1>SIPPT (Sistem Informasi Pelaporan Pemilu Terpadu)</h1>
                <span>Bawaslu Kabupaten Kendal</span>
            </div>
        </a>
        <div class="nav-menu">
            <a href="#peta">Peta & Sebaran</a>
            <a href="#panduan">Edukasi</a>
            <a href="#lapor" class="btn-nav-lapor">Kirim Laporan</a>
        </div>
    </nav>
</header>

<!-- HERO SECTION -->
<section class="hero-modern">
  <div class="hero-container animate-up">
    <div class="hero-badge">
      <span class="pulse-dot"></span> Pengawasan Pemilu Partisipatif & Transparan
    </div>
    <h1 class="hero-title">Kawal Demokrasi Bersama, Laporkan Pelanggaran Tanpa Ragu.</h1>
    <p class="hero-desc">Platform resmi pemantauan dan pengaduan pemilu terpadu. Identitas pelapor dilindungi undang-undang, progres laporan dipantau secara real-time.</p>

    <div class="hero-metrics delay-1 animate-up">
      <div class="metric-card">
        <div class="metric-num"><?= $total_laporan > 0 ? $total_laporan : 0 ?></div>
        <div class="metric-label">Laporan Masuk</div>
      </div>
      <div class="metric-card">
        <div class="metric-num"><?= $total_kecamatan > 0 ? $total_kecamatan : 0 ?></div>
        <div class="metric-label">Kecamatan Dilaporkan</div>
      </div>
      <div class="metric-card">
        <div class="metric-num">100%</div>
        <div class="metric-label">Anonimitas Aman</div>
      </div>
      <div class="metric-card">
        <div class="metric-num">24/7</div>
        <div class="metric-label">Siaga Pengawasan</div>
      </div>
    </div>
  </div>
</section>

<!-- PETA KERAWANAN & LIVE STATUS -->
<section id="peta" class="section-container delay-2 animate-up">
  <div class="section-title-wrap">
    <span class="sec-tag">01 — Sebaran Wilayah</span>
    <h2>Peta Kerawanan & Live Tindak Lanjut</h2>
    <p>Pantau titik lokasi dugaan pelanggaran pemilu serta status penanganan penegakan hukum secara transparan.</p>
  </div>

  <div class="dashboard-grid">
    <!-- Peta Interaktif Real-Time dari Database -->
    <div class="card-box map-box">
      <div id="map"></div>
      <div class="map-legend">
        <span><i class="dot danger"></i> Masuk / Rawan</span>
        <span><i class="dot warning"></i> Proses Klarifikasi</span>
        <span><i class="dot success"></i> Selesai Ditindak</span>
      </div>
    </div>

    <!-- Live Tindak Lanjut Terhubung Database -->
    <div class="card-box status-box">
      <div class="status-header">
        <h3>Live Tindak Lanjut</h3>
        <span class="live-pulse">● Live</span>
      </div>
      <div class="status-list">
        <?php if (count($live_laporan) > 0): ?>
            <?php foreach ($live_laporan as $lap): ?>
                <?php 
                    $status = $lap['status'];
                    $badge_class = 'rawan'; 
                    if ($status == 'diklarifikasi') $badge_class = 'waspada';
                    if ($status == 'diteruskan') $badge_class = 'waspada';
                    if ($status == 'selesai') $badge_class = 'aman';
                ?>
                <div class="status-item">
                    <div class="status-info">
                        <strong><?= htmlspecialchars($lap['nama_kategori'] ?? 'Pelanggaran') ?></strong>
                        <span><?= htmlspecialchars($lap['kecamatan'] ? 'Kec. ' . $lap['kecamatan'] : $lap['lokasi_kejadian']) ?></span>
                    </div>
                    <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($status) ?></span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-status">Belum ada laporan masuk dari warga.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- EDUKASI -->
<section id="panduan" class="section-container">
  <div class="section-title-wrap">
    <span class="sec-tag">02 — Panduan Publik</span>
    <h2>Catatan Singkat Pengawasan Pemilu</h2>
    <p>Pahami hak dan prosedur pelaporan untuk menciptakan pemilu yang bersih dan berintegritas.</p>
  </div>

  <div class="edu-grid">
    <div class="card-box edu-card">
      <div class="edu-icon">🤝</div>
      <h3>Partisipasi Aktif Warga</h3>
      <p>Masyarakat memiliki hak penuh untuk mengawasi dan melaporkan segala bentuk indikasi kecurangan atau pelanggaran pemilu di sekitar mereka.</p>
    </div>
    <div class="card-box edu-card">
      <div class="edu-icon">🔒</div>
      <h3>Perlindungan Pelapor</h3>
      <p>Keamanan dan kerahasiaan identitas pelapor dijamin sepenuhnya oleh regulasi pengawasan pemilu. Anda dapat memilih untuk tetap anonim.</p>
    </div>
    <div class="card-box edu-card">
      <div class="edu-icon">⚡</div>
      <h3>Respon Cepat Tim</h3>
      <p>Setiap laporan yang masuk akan melalui verifikasi awal oleh staf ahli sebelum diteruskan ke jajaran pengawas lapangan (Panwascam).</p>
    </div>
  </div>
</section>

<!-- FORM LAPOR DENGAN KECAMATAN & DESA -->
<section id="lapor" class="section-container">
  <div class="section-title-wrap">
    <span class="sec-tag">03 — Saluran Pengaduan</span>
    <h2>Formulir Laporan Pelanggaran</h2>
    <p>Sampaikan laporan Anda dengan melengkapi rincian wilayah dan bukti valid agar dapat segera ditindaklanjuti.</p>
  </div>

  <div class="form-layout">
    <form class="report card" action="proses/submit_laporan.php" method="POST" enctype="multipart/form-data">
      
      <div class="input-group">
        <label>Nama Lengkap (Opsional)</label>
        <input type="text" name="nama" placeholder="Kosongkan jika ingin melapor secara anonim">
      </div>
      
      <!-- Pilihan Kecamatan -->
      <div class="input-group">
        <label>Kecamatan <span class="req">*</span></label>
        <select name="kecamatan" required style="width: 100%; padding: 13px 16px; border: 1px solid #CBD5E1; border-radius: 10px; font-family: inherit; font-size: 14px; background: #FFFFFF;">
          <option value="">-- Pilih Kecamatan di Kendal --</option>
          <option value="Boja">Boja</option>
          <option value="Brangsong">Brangsong</option>
          <option value="Cepiring">Cepiring</option>
          <option value="Gemuh">Gemuh</option>
          <option value="Kaliwungu">Kaliwungu</option>
          <option value="Kaliwungu Selatan">Kaliwungu Selatan</option>
          <option value="Kangkung">Kangkung</option>
          <option value="Kendal">Kendal</option>
          <option value="Limbangan">Limbangan</option>
          <option value="Ngampel">Ngampel</option>
          <option value="Pagerruyung">Pagerruyung</option>
          <option value="Patean">Patean</option>
          <option value="Patebon">Patebon</option>
          <option value="Pegandon">Pegandon</option>
          <option value="Plantungan">Plantungan</option>
          <option value="Ringinarum">Ringinarum</option>
          <option value="Rowosari">Rowosari</option>
          <option value="Singorojo">Singorojo</option>
          <option value="Sukorejo">Sukorejo</option>
          <option value="Weleri">Weleri</option>
        </select>
      </div>

      <!-- Input Desa / Kelurahan -->
      <div class="input-group">
        <label>Desa / Kelurahan <span class="req">*</span></label>
        <input type="text" name="desa" placeholder="Contoh: Desa Ngampel" required>
      </div>

      <div class="input-group">
        <label>Detail Lokasi / TPS <span class="req">*</span></label>
        <input type="text" name="lokasi" placeholder="Contoh: TPS 04 RT 02" required>
      </div>
      
      <div class="input-group">
        <label>Kategori Dugaan Pelanggaran <span class="req">*</span></label>
        <div class="chips-container">
          <?php foreach($kategori_list as $kat): ?>
          <label class="chip-label">
            <input type="radio" name="kategori_id" value="<?= $kat['id'] ?>" required>
            <span><?= htmlspecialchars($kat['nama_kategori']) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      
      <div class="input-group">
        <label>Deskripsi Kejadian <span class="req">*</span></label>
        <textarea rows="4" name="deskripsi" placeholder="Uraikan peristiwa secara jelas..." required></textarea>
      </div>
      
      <div class="input-group">
        <label>Unggah Bukti Pendukung (Foto / Video) <span class="req">*</span></label>
        <input type="file" name="bukti" accept=".jpg, .jpeg, .png, .mp4" required>
      </div>
      
      <button type="submit" class="btn-submit-modern">Kirim Laporan Resmi</button>
    </form>

    <!-- Alur Investigasi -->
    <div class="card-box guide-card">
      <h3>Alur Penanganan Laporan</h3>
      <div class="timeline">
        <div class="timeline-step">
          <div class="t-number">1</div>
          <div class="t-content"><strong>Laporan Masuk</strong><span>Terekam secara aman di pusat database server Bawaslu.</span></div>
        </div>
        <div class="timeline-step">
          <div class="t-number">2</div>
          <div class="t-content"><strong>Verifikasi Dokumen</strong><span>Pemeriksaan keabsahan bukti foto/video oleh tim verifikator.</span></div>
        </div>
        <div class="timeline-step">
          <div class="t-number">3</div>
          <div class="t-content"><strong>Klarifikasi Lapangan</strong><span>Pengawas turun langsung menyelidiki lokasi kejadian perkara.</span></div>
        </div>
        <div class="timeline-step">
          <div class="t-number">4</div>
          <div class="t-content"><strong>Tindak Lanjut / Selesai</strong><span>Penegakan hukum atau penutupan kasus dengan status transparan.</span></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FOOTER MODERN -->
<footer class="modern-footer">
  <div class="footer-content">
    <div class="footer-brand">BAWASLU KABUPATEN KENDAL</div>
    <p>Sistem Informasi Pemantauan Pemilu Terpadu (SIPPT) &copy; 2026. Seluruh hak cipta dilindungi undang-undang.</p>
  </div>
</footer>

<script>
  // Inisialisasi Peta Leaflet dengan Data Real-Time dari Database
  const map = L.map('map', { scrollWheelZoom:false }).setView([-6.92, 110.20], 10.3);
  L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; OpenStreetMap', maxZoom: 18
  }).addTo(map);

  // Menerima data koordinat langsung dari database PHP
  const titikLaporan = <?= json_encode($data_peta) ?>;

  const warnaStatus = {
    'masuk': '#EF4444',
    'diklarifikasi': '#F59E0B',
    'diteruskan': '#F59E0B',
    'selesai': '#10B981'
  };
  
  titikLaporan.forEach(t => {
    if (t.latitude && t.longitude) {
      let warna = warnaStatus[t.status] || '#0284C7';
      L.circleMarker([parseFloat(t.latitude), parseFloat(t.longitude)], {
        radius: 9, color: '#fff', fillColor: warna, fillOpacity: 0.9, weight: 2
      }).addTo(map).bindPopup(`
        <strong>${t.nama_kategori || 'Pelanggaran'}</strong><br>
        <span style="font-size:12px; color:#64748B;">Kec. ${t.kecamatan || '-'} / Desa ${t.desa || '-'}</span><br>
        <span style="font-size:11px; font-weight:700; color:${warna};">Status: ${t.status.toUpperCase()}</span>
      `);
    }
  });

  // Animasi Masuk Saat Scroll
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.animationPlayState = 'running';
      }
    });
  });
  document.querySelectorAll('.animate-up').forEach((el) => {
    el.style.animationPlayState = 'paused';
    observer.observe(el);
  });
</script>

</body>
</html>