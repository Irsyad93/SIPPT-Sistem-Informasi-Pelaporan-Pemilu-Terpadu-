-- 1. Buat dan gunakan database
CREATE DATABASE IF NOT EXISTS db_pantau_pemilu;
USE db_pantau_pemilu;

-- 2. Hapus tabel jika sebelumnya sudah ada (opsional, untuk mencegah error duplikasi)
DROP TABLE IF EXISTS laporan;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS kategori_pelanggaran;

-- 3. Buat tabel users (admin & petugas)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'petugas_kecamatan') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 4. Buat tabel kategori pelanggaran
CREATE TABLE kategori_pelanggaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL
);

-- 5. Buat tabel laporan pengaduan
CREATE TABLE laporan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_pelapor VARCHAR(100) DEFAULT NULL,
    lokasi_kejadian VARCHAR(255) NOT NULL,
    kategori_id INT NOT NULL,
    deskripsi TEXT NOT NULL,
    file_bukti VARCHAR(255) DEFAULT NULL,
    status ENUM('masuk', 'diklarifikasi', 'diteruskan', 'selesai') DEFAULT 'masuk',
    waktu_lapor DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori_pelanggaran(id) ON DELETE RESTRICT
);

-- 6. Masukkan data awal kategori pelanggaran
INSERT INTO kategori_pelanggaran (id, nama_kategori) VALUES
(1, 'Politik Uang'), 
(2, 'Netralitas ASN'), 
(3, 'APK Ilegal'), 
(4, 'Intimidasi'), 
(5, 'Lainnya');

-- 7. Masukkan akun admin default (password: admin123 sudah di-hash)
INSERT INTO users (id, username, password, role) VALUES 
(1, 'admin', '$2y$10$a/R/wP8/3/gGvjI0.rT6m.T4zL6FqZ/Z41.qG23qJqXo95b.33s/K', 'admin');