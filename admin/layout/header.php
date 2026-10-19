<?php
// Tangkap parameter page untuk menentukan menu aktif
$page = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - SIPPT Bawaslu Kendal</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #0F172A; 
            --accent: #0284C7;
            --text-main: #334155;
            --text-muted: #64748B;
            --bg-body: #F8FAFC;
            --sidebar-width: 260px;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-body);
            margin: 0;
            padding: 0;
            color: var(--text-main);
            display: flex;
        }
        
        /* --- SIDEBAR KIRI (DESKTOP DEFAULT) --- */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--primary);
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            padding: 24px 20px;
            box-shadow: 4px 0 15px rgba(0,0,0,0.05);
            z-index: 1002;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar-brand { margin-bottom: 40px; padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .sidebar-brand h1 { margin: 0; font-size: 20px; color: #F8FAFC; letter-spacing: 0.5px; }
        .sidebar-brand span { display: inline-block; margin-top: 8px; font-size: 11px; background: #1E293B; color: #38BDF8; padding: 4px 10px; border-radius: 6px; border: 1px solid #334155; font-weight: 600; }
        
        .sidebar-nav { display: flex; flex-direction: column; gap: 8px; flex-grow: 1; }
        .sidebar-nav a { color: #CBD5E1; text-decoration: none; font-size: 14px; font-weight: 600; padding: 12px 16px; border-radius: 8px; transition: all 0.2s; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.1); color: #FFFFFF; }
        .sidebar-nav a.logout { background: rgba(239, 68, 68, 0.1); color: #EF4444; margin-top: auto; }
        .sidebar-nav a.logout:hover { background: #EF4444; color: #FFF; }

        /* --- MOBILE HEADER & OVERLAY (HIDDEN ON DESKTOP) --- */
        .mobile-header {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%;
            background: var(--primary);
            color: #FFFFFF;
            padding: 16px 20px;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .mobile-brand h1 { margin: 0; font-size: 18px; font-weight: 700; }
        .burger-btn {
            background: none; border: none; color: #FFFFFF;
            font-size: 24px; cursor: pointer; padding: 0;
        }
        .sidebar-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100vh;
            background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(2px);
            z-index: 1001; opacity: 0; transition: opacity 0.3s;
        }

        /* --- AREA KONTEN KANAN --- */
        .main-content {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            min-height: 100vh;
            padding: 30px 40px;
            transition: all 0.3s;
        }

        /* --- KOMPONEN UMUM --- */
        .card-panel { background: #FFFFFF; padding: 24px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #E2E8F0; margin-bottom: 24px; }
        .btn-danger-soft { background: #FEF2F2; color: #EF4444; border: 1px solid #FCA5A5; padding: 6px 12px; border-radius: 6px; font-weight: 700; cursor: pointer; text-decoration: none; font-size: 12px; }
        .btn-primary-soft { background: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE; padding: 6px 12px; border-radius: 6px; font-weight: 700; cursor: pointer; text-decoration: none; font-size: 12px; }
        .dashboard-grid-top { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px; }

        /* =========================================
           RESPONSIF TABLET & MOBILE (Max 992px)
           ========================================= */
        @media (max-width: 992px) {
            .mobile-header { display: flex; }
            .sidebar-overlay.active { display: block; opacity: 1; }
            
            /* Sembunyikan sidebar ke kiri */
            .sidebar { transform: translateX(-100%); }
            /* Tampilkan saat class active ditambahkan */
            .sidebar.active { transform: translateX(0); }
            
            /* Konten utama dibuat penuh dan diberi jarak atas untuk mobile header */
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 90px 20px 30px;
            }
            
            /* Grid dashboard ditumpuk ke bawah */
            .dashboard-grid-top { grid-template-columns: 1fr; }
            
            /* Sesuaikan grid rekapan angka (misal: 4 kotak menjadi 2 baris) */
            div[style*="grid-template-columns: repeat(4, 1fr)"] {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }
        
        /* HP Kecil */
        @media (max-width: 576px) {
            div[style*="grid-template-columns: repeat(4, 1fr)"] {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</head>
<body>

<!-- MOBILE HEADER (Muncul di layar kecil) -->
<header class="mobile-header">
    <div class="mobile-brand">
        <h1>SIPPT Admin</h1>
    </div>
    <button class="burger-btn" onclick="toggleSidebar()">☰</button>
</header>

<!-- OVERLAY LATAR BELAKANG -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- SIDEBAR NAVIGASI KIRI -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <h1>SIPPT Admin</h1>
        <span>Bawaslu Kendal</span>
    </div>
    <nav class="sidebar-nav">
        <a href="index.php?page=dashboard" class="<?= $page == 'dashboard' ? 'active' : '' ?>">Dashboard</a>
        <a href="index.php?page=laporan" class="<?= $page == 'laporan' ? 'active' : '' ?>">Daftar Laporan</a>
        <a href="index.php?page=password" class="<?= $page == 'password' ? 'active' : '' ?>">Ubah Password</a>
        <a href="logout.php" class="logout" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?');">Keluar</a>
    </nav>
</aside>

<!-- BUNGKUSAN KONTEN KANAN -->
<main class="main-content">

<!-- SCRIPT UNTUK TOGGLE SIDEBAR -->
<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
        document.getElementById('sidebarOverlay').classList.toggle('active');
    }
</script>