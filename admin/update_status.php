<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Menangkap ID dan Status Baru dari form
    $id = (int)$_POST['id'];
    $status_baru = $_POST['status'];
    
    // Validasi input agar tidak diubah sembarangan via inspect element
    $allowed_status = ['masuk', 'diklarifikasi', 'diteruskan', 'selesai'];
    if (!in_array($status_baru, $allowed_status)) {
        die("<script>alert('Status tidak valid!'); window.history.back();</script>");
    }
    
    try {
        // Proses UPDATE ke database MySQL menggunakan PDO
        $sql = "UPDATE laporan SET status = :status WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':status' => $status_baru,
            ':id'     => $id
        ]);
        
        // Kembalikan ke halaman detail dengan pesan sukses
        echo "<script>
                alert('Status laporan berhasil diperbarui!'); 
                window.location.href = 'detail.php?id=" . $id . "';
              </script>";
        exit;
        
    } catch (PDOException $e) {
        die("Terjadi kesalahan saat memperbarui status: " . $e->getMessage());
    }
    
} else {
    // Jika file diakses langsung dari URL tanpa POST
    header("Location: index.php");
    exit;
}
?>