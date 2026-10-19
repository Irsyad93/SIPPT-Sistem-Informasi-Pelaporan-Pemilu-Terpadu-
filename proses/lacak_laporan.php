<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$code = $_GET['code'] ?? '';

if (empty($code)) {
    echo json_encode(['status' => 'error', 'pesan' => 'Kode pelacakan kosong.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT kecamatan, status FROM laporan WHERE kode_unik = ?");
    $stmt->execute([$code]);
    $data = $stmt->fetch();

    if ($data) {
        echo json_encode([
            'status' => 'success',
            'kecamatan' => $data['kecamatan'],
            'kategori' => $data['status']
        ]);
    } else {
        echo json_encode([
            'status' => 'not_found',
            'pesan' => 'Kode pelacakan tidak ditemukan di database.'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'pesan' => 'Kesalahan database.']);
}