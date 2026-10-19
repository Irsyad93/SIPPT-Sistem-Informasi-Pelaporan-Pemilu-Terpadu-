<?php
$host     = 'localhost';
$dbname   = 'db_pantau_pemilu'; 
$username = 'db_pantau_pemilu'; 
$password = 'magangBawaslu26!';

try {
    // Membuat instance koneksi PDO
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    
    // Mengatur mode error agar mudah melakukan debugging (mencari kesalahan)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Mengatur mode default pengambilan data menjadi Array Asosiatif
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Jika koneksi gagal, hentikan program dan tampilkan pesan error
    die("Koneksi Database Gagal: " . $e->getMessage());
}
?>