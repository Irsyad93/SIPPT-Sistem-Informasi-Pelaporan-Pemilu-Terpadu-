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

// 3. Mengambil data laporan yang memiliki koordinat untuk peta
$stmt_peta = $pdo->query("
    SELECT laporan.*, kategori_pelanggaran.nama_kategori 
    FROM laporan 
    LEFT JOIN kategori_pelanggaran ON laporan.kategori_id = kategori_pelanggaran.id 
    WHERE laporan.latitude IS NOT NULL AND laporan.longitude IS NOT NULL
");
$data_peta = $stmt_peta->fetchAll();

// 4. Statistik ringkas & status count lengkap
$total_laporan = $pdo->query("SELECT COUNT(*) FROM laporan")->fetchColumn();
$total_kecamatan = $pdo->query("SELECT COUNT(DISTINCT kecamatan) FROM laporan WHERE kecamatan IS NOT NULL AND kecamatan != ''")->fetchColumn();
$count_masuk = $pdo->query("SELECT COUNT(*) FROM laporan WHERE status = 'masuk'")->fetchColumn();
$count_klarifikasi = $pdo->query("SELECT COUNT(*) FROM laporan WHERE status = 'diklarifikasi'")->fetchColumn();
$count_diteruskan = $pdo->query("SELECT COUNT(*) FROM laporan WHERE status = 'diteruskan'")->fetchColumn();
$count_selesai = $pdo->query("SELECT COUNT(*) FROM laporan WHERE status = 'selesai'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Portal Pengawasan Pemilu — Bawaslu Kabupaten Kendal</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<!-- Memanggil File CSS Eksternal -->
<link rel="stylesheet" href="assets/css/style.css?v=<?= time(); ?>">
</head>
<body>

<?php if (isset($_GET['status']) && $_GET['status'] === 'sukses' && isset($_GET['kode'])): ?>
  <div class="alert-success">
    <div>
      <strong>🎉 Laporan Berhasil Dikirim!</strong><br>
      Simpan kode pelacakan Anda: <code><?= htmlspecialchars($_GET['kode']) ?></code> untuk memantau status penanganan.
    </div>
    <button onclick="this.parentElement.style.display='none';">✕</button>
  </div>
<?php endif; ?>

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
        
        <!-- Tombol Burger untuk Mobile -->
        <button class="mobile-burger-btn" onclick="togglePublicMenu()" aria-label="Buka Menu">
            ☰
        </button>

        <div class="nav-menu" id="publicNavMenu">
            <a href="#peta" onclick="togglePublicMenu()">Peta & Sebaran</a>
            <a href="#panduan" onclick="togglePublicMenu()">Edukasi</a>
            <a href="#lapor" class="btn-nav-lapor" onclick="togglePublicMenu()">Kirim Laporan</a>
        </div>
    </nav>
</header>

<!-- HERO SECTION -->
<section class="hero-modern">
  <div class="hero-container animate-up">
    <div class="hero-grid">
      
      <!-- Sisi Kiri -->
      <div>
        <div class="hero-badge">
          <span class="pulse-dot"></span> BAWASLU KABUPATEN KENDAL · KANAL RESMI PENGAWASAN
        </div>
        <h1 class="hero-title">
          MATA RAKYAT MENGAWASI <span>SETIAP SUARA</span>
        </h1>
        <p class="hero-desc">
          SIPPT adalah pusat pemantauan pemilu terpadu: petakan dugaan pelanggaran di wilayahmu, laporkan secara anonim, dan awasi penanganannya oleh Bawaslu — semuanya tersinkronisasi real-time.
        </p>
        <div class="hero-actions">
          <a href="#lapor" class="btn-primary">LAPORKAN PELANGGARAN</a>
          <a href="#peta" class="btn-secondary">JELAJAHI PETA</a>
        </div>
      </div>

      <!-- Sisi Kanan: Widget -->
      <div class="hero-widget">
        <div class="widget-title">
          <span>⏳</span> Hitung Mundur Pemungutan Suara Pemilu Serentak
        </div>
        
        <div class="countdown-grid">
          <div class="countdown-item">
            <div id="days">--</div>
            <span>Hari</span>
          </div>
          <div class="countdown-item">
            <div id="hours">--</div>
            <span>Jam</span>
          </div>
          <div class="countdown-item">
            <div id="minutes">--</div>
            <span>Menit</span>
          </div>
          <div class="countdown-item">
            <div id="seconds">--</div>
            <span>Detik</span>
          </div>
        </div>
         
        <div class="countdown-footer">
          <span>Rabu, 27 November 2026 · 07.00 WIB</span>
          <span id="days-tag">D-0</span>
        </div>

        <div class="status-title">Status Penanganan Terkini</div>
        <div class="status-bars">
          <div class="status-bar-row">
            <span>Masuk</span>
            <div class="bar-wrapper">
              <div class="bar-bg"><div class="bar-fill danger-bg" style="width: <?= $total_laporan > 0 ? ($count_masuk/$total_laporan)*100 : 0 ?>%;"></div></div>
              <span class="bar-count"><?= $count_masuk ?></span>
            </div>
          </div>
          <div class="status-bar-row">
            <span>Diklarifikasi</span>
            <div class="bar-wrapper">
              <div class="bar-bg"><div class="bar-fill warning-bg" style="width: <?= $total_laporan > 0 ? ($count_klarifikasi/$total_laporan)*100 : 0 ?>%;"></div></div>
              <span class="bar-count"><?= $count_klarifikasi ?></span>
            </div>
          </div>
          <div class="status-bar-row">
            <span>Diteruskan</span>
            <div class="bar-wrapper">
              <div class="bar-bg"><div class="bar-fill info-bg" style="width: <?= $total_laporan > 0 ? ($count_diteruskan/$total_laporan)*100 : 0 ?>%;"></div></div>
              <span class="bar-count"><?= $count_diteruskan ?></span>
            </div>
          </div>
          <div class="status-bar-row">
            <span>Selesai</span>
            <div class="bar-wrapper">
              <div class="bar-bg"><div class="bar-fill success-bg" style="width: <?= $total_laporan > 0 ? ($count_selesai/$total_laporan)*100 : 0 ?>%;"></div></div>
              <span class="bar-count"><?= $count_selesai ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Metrics -->
    <div class="hero-metrics delay-1 animate-up">
      <div class="metric-card">
        <div class="metric-num"><?= $total_laporan > 0 ? $total_laporan : 0 ?></div>
        <div class="metric-label">TOTAL LAPORAN</div>
      </div>
      <div class="metric-card">
        <div class="metric-num"><?= $count_selesai ?></div>
        <div class="metric-label">SELESAI DITINDAK</div>
      </div>
      <div class="metric-card">
        <div class="metric-num"><?= $total_kecamatan > 0 ? $total_kecamatan : 0 ?></div>
        <div class="metric-label">KECAMATAN</div>
      </div>
      <div class="metric-card">
        <div class="metric-num">100%</div>
        <div class="metric-label">PELAPOR ANONIM</div>
      </div>
    </div>
  </div>
</section>

<!-- PETA & STATUS -->
<section id="peta" class="section-container delay-2 animate-up">
  <div class="section-title-wrap">
    <span class="sec-tag">01 — Sebaran Wilayah</span>
    <h2>Peta Kerawanan & Live Tindak Lanjut</h2>
    <p>Pantau titik lokasi dugaan pelanggaran pemilu serta status penanganan penegakan hukum secara transparan.</p>
  </div>

  <div class="dashboard-grid">
    <div class="card-box map-box">
      <div id="map"></div>
      <div class="map-legend">
        <span><i class="dot danger"></i> Masuk</span>
        <span><i class="dot warning"></i> Diklarifikasi</span>
        <span><i class="dot info"></i> Diteruskan</span>
        <span><i class="dot success"></i> Selesai</span>
      </div>
    </div>

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
                    $badge_class = 'badge-masuk'; 
                    if ($status == 'diklarifikasi') $badge_class = 'badge-klarifikasi';
                    if ($status == 'diteruskan') $badge_class = 'badge-diteruskan';
                    if ($status == 'selesai') $badge_class = 'badge-selesai';
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

<!-- EDUKASI 6 GRID -->
<section id="panduan" class="section-container">
  <div class="section-title-wrap">
    <span class="sec-tag">02 — Edukasi Pengawasan Partisipatif</span>
    <h2>Warga Cerdas, Pemilu Berintegritas</h2>
    <p>Pelajari peran Anda sebagai pengawas partisipatif: kenali jenis pelanggaran, pahami alur penanganan, dan ketahui hak perlindungan Anda sebagai pelapor.</p>
  </div>

  <div class="edu-grid-6">
    <div class="card-box edu-card">
      <div>
        <div class="edu-header">
          <span class="edu-num text-info">01</span>
        </div>
        <h3>Pengawasan Partisipatif</h3>
        <p>Pemilu yang jujur bukan hanya tugas Bawaslu. Setiap warga berhak mengawasi seluruh tahapan dan melaporkan indikasi kecurangan di sekitar mereka.</p>
      </div>
      <div class="edu-footer footer-light">Amati → Dokumentasikan → Laporkan</div>
    </div>

    <div class="card-box edu-card">
      <div>
        <div class="edu-header">
          <span class="edu-num text-warning">02</span>
        </div>
        <h3>Jenis Pelanggaran</h3>
        <p>Waspadai bentuk pelanggaran umum seperti Politik Uang, APK menyalahi aturan, Netralitas ASN, hingga Kampanye Hitam/Hoaks.</p>
      </div>
      <div class="edu-footer footer-warning">Pastikan laporan memuat: apa, di mana, kapan, siapa, & bukti.</div>
    </div>

    <div class="card-box edu-card">
      <div>
        <div class="edu-header">
          <span class="edu-num text-success">03</span>
        </div>
        <h3>Alur Penanganan</h3>
        <p>Setiap laporan melalui tahap verifikasi, klarifikasi lapangan, diteruskan instansi terkait, hingga rekomendasi tindak lanjut resmi.</p>
      </div>
      <div class="edu-footer footer-success">Transparan & terpantau secara real-time.</div>
    </div>

    <div class="card-box edu-card">
      <div>
        <div class="edu-header">
          <span class="edu-num text-info">04</span>
        </div>
        <h3>Dasar Hukum</h3>
        <p>Landasan hukum pengawasan partisipatif merujuk pada regulasi resmi: UU No. 7/2017, Perbawaslu 7/2022, & Perbawaslu 2/2023.</p>
      </div>
      <div class="edu-footer footer-light">Undang-undang pengawasan pemilu yang sah.</div>
    </div>

    <div class="card-box edu-card">
      <div>
        <div class="edu-header">
          <span class="edu-num text-info">05</span>
        </div>
        <h3>Jaminan Anonimitas</h3>
        <p>Identitas pelapor dilindungi undang-undang dan dienkripsi. Anda dapat melapor tanpa nama menggunakan kode pelacakan.</p>
      </div>
      <div class="edu-footer footer-light">Kerahasiaan data dijamin 100% aman.</div>
    </div>

    <div class="card-box edu-card dark-card">
      <div>
        <div class="edu-header">
          <span class="edu-num dark-num text-warning">06</span>
        </div>
        <h3>Layanan & Hotline</h3>
        <p>Butuh konsultasi langsung atau pendampingan khusus? Hubungi layanan pusat bantuan Bawaslu Kabupaten Kendal.</p>
      </div>
      <div class="edu-footer footer-dark">Hotline: 1500-100 (Gratis)</div>
    </div>
  </div>
</section>

<!-- FORM PENGADUAN & LACAK -->
<section id="lapor" class="section-container">
  <div class="section-title-wrap">
    <span class="sec-tag">03 — Saluran Pengaduan & Pelacakan</span>
    <h2>Formulir Pengaduan Laporan</h2>
    <p>Sampaikan laporan Anda dengan melengkapi rincian wilayah dan bukti valid agar dapat segera ditindaklanjuti.</p>
  </div>

  <div class="form-layout">
    
    <form class="card-box form-card" action="proses/submit_laporan.php" method="POST" enctype="multipart/form-data">
      <div class="form-header">
        <h3 style="margin: 0; font-size: 18px;">Form Pengaduan Laporan</h3>
        <span class="realtime-badge">● REALTIME</span>
      </div>

      <div class="input-group">
        <label>NAMA LENGKAP (Opsional)</label>
        <input type="text" name="nama" placeholder="Kosongkan jika ingin melapor secara anonim">
      </div>

      <div class="input-grid">
        <div class="input-group">
          <label>JENIS PELANGGARAN <span class="req">*</span></label>
          <select name="kategori_id" required>
            <option value="">— Pilih jenis —</option>
            <?php foreach($kategori_list as $kat): ?>
              <option value="<?= $kat['id'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="input-group">
          <label>KECAMATAN <span class="req">*</span></label>
          <select name="kecamatan" required>
            <option value="">— Pilih kecamatan —</option>
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
      </div>

      <div class="input-grid">
        <div class="input-group">
          <label>DESA / KELURAHAN <span class="req">*</span></label>
          <input type="text" name="desa" placeholder="Contoh: Desa Ngampel" required>
        </div>
        <div class="input-group">
          <label>DETAIL LOKASI / TPS <span class="req">*</span></label>
          <input type="text" name="lokasi" placeholder="Contoh: TPS 04 RT 02" required>
        </div>
      </div>

      <div class="input-group">
        <label>DESKRIPSI KEJADIAN <span class="req">*</span></label>
        <textarea rows="4" name="deskripsi" placeholder="Uraikan peristiwa secara jelas..." required></textarea>
      </div>

      <div class="input-group">
        <label>UNGGAH BUKTI PENDUKUNG (Foto / Video) <span class="req">*</span></label>
        <input type="file" name="bukti" accept=".jpg, .jpeg, .png, .mp4" required class="file-input">
      </div>

      <div class="checkbox-group">
        <label>
          <input type="checkbox" name="verifikasi_benar" value="1" required>
          <span>Saya menyatakan laporan ini <strong>benar dan bukan fitnah</strong>, serta bersedia laporan diproses sesuai ketentuan Bawaslu.</span>
        </label>
      </div>

      <button type="submit" class="btn-submit-modern">KIRIM LAPORAN RESMI</button>
    </form>

    <!-- Sisi Kanan: Lacak Laporan -->
    <div class="sidebar-layout">
      <div class="card-box track-box">
        <h3><span>🔍</span> Lacak Laporan Anda</h3>
        <p>Masukkan kode pelacakan yang Anda terima saat melapor untuk melihat status penanganan terkini.</p>
        <div class="track-input-group">
          <input type="text" id="track-code" placeholder="SIPPT-2026-XXXX">
          <button onclick="lacakLaporan()">Lacak</button>
        </div>
        <div id="track-result"></div>
      </div>

      <div class="card-box info-box">
        <h3>Apa yang Terjadi Setelah Melapor?</h3>
        <div class="info-list">
          <div class="info-item">
            <span class="check">✓</span>
            <span>Laporan muncul sebagai titik di peta publik dalam hitungan detik — tanpa data pribadi Anda.</span>
          </div>
          <div class="info-item">
            <span class="check">✓</span>
            <span>Petugas piket Bawaslu memverifikasi & merangkai kronologi dalam 1x24 jam.</span>
          </div>
          <div class="info-item">
            <span class="check">✓</span>
            <span>Anda dapat memantau progres kapan pun lewat kode pelacakan.</span>
          </div>
        </div>
      </div>
    </div>

  </div>
</section>

<!-- HOTLINE -->
<section class="section-container hotline-section">
  <div class="hotline-box">
    <div class="hotline-text">
      <strong>Butuh Pendampingan?</strong>
      Hubungi hotline Bawaslu Kabupaten Kendal <span>1500-100</span> atau kunjungi kantor sekretariat — layanan gratis & rahasia.
    </div>
    <a href="#" onclick="window.scrollTo({top: 0, behavior: 'smooth'}); return false;" class="back-to-top">
      Kembali ke atas <span>↑</span>
    </a>
  </div>
</section>

<!-- FOOTER -->
<footer class="modern-footer">
  <div class="footer-grid">
    <div>
      <div class="footer-brand">
        <div class="brand-icon"><img src="logo.png" alt="Logo Bawaslu Kendal"></div>
        <h3>SIPPT</h3>
      </div>
      <p class="footer-subtitle">Sistem Informasi Pelaporan Pemilu Terpadu</p>
      <p class="footer-desc">Platform kolaborasi Bawaslu dan masyarakat untuk pemilu yang jujur, adil, dan transparan. Setiap laporan adalah suara yang dikawal.</p>
    </div>

    <div>
      <div class="footer-title">Navigasi</div>
      <ul class="footer-links">
        <li><a href="#peta">Peta Pelanggaran</a></li>
        <li><a href="#panduan">Edukasi Pengawasan</a></li>
        <li><a href="#lapor">Form Pengaduan</a></li>
      </ul>
    </div>

    <div>
      <div class="footer-title">Dasar Hukum</div>
      <ul class="footer-links text-only">
        <li>UU No. 7 Tahun 2017 tentang Pemilu</li>
        <li>Perbawaslu No. 7 Tahun 2022</li>
        <li>Perbawaslu No. 2 Tahun 2023</li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    Bawaslu Kabupaten Kendal &copy; 2026. Seluruh hak cipta dilindungi undang-undang.
  </div>
</footer>

<!-- PENGIRIMAN DATA PHP KE JAVASCRIPT -->
<script>
  const titikLaporan = <?= json_encode($data_peta) ?>;
</script>

<!-- MEMANGGIL FILE JS EKSTERNAL -->
<script src="assets/js/script.js?v=<?= time(); ?>"></script>

<!-- Script untuk Burger Menu Publik -->
<script>
    function togglePublicMenu() {
        const navMenu = document.getElementById('publicNavMenu');
        navMenu.classList.toggle('show');
    }
</script>

</body>
</html>