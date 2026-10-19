<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Tangkap data dari form
    $nama = !empty($_POST['nama']) ? $_POST['nama'] : 'Anonim';
    $kecamatan = $_POST['kecamatan'];
    $desa = $_POST['desa'];
    $lokasi = $_POST['lokasi'];
    $kategori_id = $_POST['kategori_id'];
    $deskripsi = $_POST['deskripsi'];
    
    // 2. Mapping koordinat pusat wilayah Kecamatan di Kabupaten Kendal
    $koordinat_kecamatan = [
        'Boja'              => ['lat' => -7.1023, 'lng' => 110.2794],
        'Brangsong'         => ['lat' => -6.9388, 'lng' => 110.2458],
        'Cepiring'          => ['lat' => -6.9168, 'lng' => 110.1557],
        'Gemuh'             => ['lat' => -6.9691, 'lng' => 110.1118],
        'Kaliwungu'         => ['lat' => -6.9427, 'lng' => 110.2831],
        'Kaliwungu Selatan' => ['lat' => -6.9859, 'lng' => 110.2736],
        'Kangkung'          => ['lat' => -6.9126, 'lng' => 110.0882],
        'Kendal'            => ['lat' => -6.9217, 'lng' => 110.2040],
        'Limbangan'         => ['lat' => -7.1517, 'lng' => 110.2885],
        'Ngampel'           => ['lat' => -6.9678, 'lng' => 110.2033],
        'Pagerruyung'       => ['lat' => -7.0271, 'lng' => 110.0242],
        'Patean'            => ['lat' => -7.0864, 'lng' => 110.0631],
        'Patebon'           => ['lat' => -6.9137, 'lng' => 110.1834],
        'Pegandon'          => ['lat' => -6.9532, 'lng' => 110.1652],
        'Plantungan'        => ['lat' => -7.0258, 'lng' => 109.9392],
        'Ringinarum'        => ['lat' => -6.9654, 'lng' => 110.0768],
        'Rowosari'          => ['lat' => -6.8995, 'lng' => 110.0543],
        'Singorojo'         => ['lat' => -7.1005, 'lng' => 110.1812],
        'Sukorejo'          => ['lat' => -7.0426, 'lng' => 110.0463],
        'Weleri'            => ['lat' => -6.9347, 'lng' => 110.0577],
    ];

    // Ambil koordinat berdasarkan pilihan kecamatan, berikan default pusat Kendal jika tidak ditemukan
    $lat = $koordinat_kecamatan[$kecamatan]['lat'] ?? -6.9217;
    $lng = $koordinat_kecamatan[$kecamatan]['lng'] ?? 110.2040;

    // 3. Tangani Unggahan File Bukti (Foto/Video)
    $bukti = '';
    if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['bukti']['tmp_name'];
        $fileName = $_FILES['bukti']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Validasi ekstensi file
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'mp4'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = '../uploads/';
            
            // Buat folder uploads jika belum ada
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            
            $dest_path = $uploadFileDir . $newFileName;
            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                $bukti = $newFileName;
            }
        }
    }

    // 4. Simpan data ke database menggunakan Prepared Statement
    try {
        // Catatan: Pastikan tabel laporan Anda memiliki kolom `kode_unik`
        $stmt = $pdo->prepare("
            INSERT INTO laporan (nama_pelapor, kecamatan, desa, lokasi_kejadian, kategori_id, deskripsi, bukti, latitude, longitude, status, waktu_lapor) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'masuk', NOW())
        ");
        
        $stmt->execute([
            $nama, 
            $kecamatan, 
            $desa, 
            $lokasi, 
            $kategori_id, 
            $deskripsi, 
            $bukti, 
            $lat, 
            $lng
        ]);

        // Ambil ID laporan yang baru saja dimasukkan
        $laporan_id = $pdo->lastInsertId();
        
        // Buat kode pelacakan unik, contoh: SIPPT-2026-0005
        $kode_unik = 'SIPPT-2026-' . str_pad($laporan_id, 4, '0', STR_PAD_LEFT);

        // Update baris laporan tersebut dengan kode_unik yang baru dibuat
        $stmt_update = $pdo->prepare("UPDATE laporan SET kode_unik = ? WHERE id = ?");
        $stmt_update->execute([$kode_unik, $laporan_id]);

        // Redirect kembali ke halaman utama dengan membawa parameter status sukses dan kode_unik
        header("Location: ../index.php?status=sukses&kode=" . $kode_unik);
        exit();

    } catch (PDOException $e) {
        echo "Gagal menyimpan laporan: " . $e->getMessage();
    }
}