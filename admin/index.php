<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// 1. Muat Sidebar Kiri
require_once 'layout/header.php';

// 2. Muat Konten Berdasarkan Parameter URL
$page = $_GET['page'] ?? 'dashboard';
switch ($page) {
    case 'dashboard':
        include 'dashboard.php';
        break;
    case 'laporan':
        include 'laporan.php';
        break;
    case 'password':
        include 'password.php';
        break;
    default:
        include 'dashboard.php';
        break;
}

// 3. Muat Penutup HTML
require_once 'layout/footer.php';
?>