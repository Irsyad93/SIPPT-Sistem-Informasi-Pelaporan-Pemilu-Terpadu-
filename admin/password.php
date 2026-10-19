<?php
require_once '../config/database.php';

$nama_tabel = 'users'; 
$pesan = '';
$tipe = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_lama = trim($_POST['password_lama']);
    $password_baru = trim($_POST['password_baru']);
    $konfirmasi_baru = trim($_POST['konfirmasi_baru']);
    
    $admin_id = $_SESSION['admin_id'] ?? 1; 

    try {
        $stmt = $pdo->prepare("SELECT * FROM $nama_tabel WHERE id = ?");
        $stmt->execute([$admin_id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            $db_password = trim($admin['password']);

            $is_password_correct = false;
            // Cek apakah password di database berbentuk Hash
            if (password_verify($password_lama, $db_password)) {
                $is_password_correct = true;
            } 
            // Cek apakah password di database masih Plaintext (seperti yang baru Anda ubah di phpMyAdmin)
            elseif ($password_lama === $db_password) { 
                $is_password_correct = true;
            }

            if ($is_password_correct) {
                if ($password_baru === $konfirmasi_baru) {
                    // Enkripsi kata sandi baru
                    $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
                    $update = $pdo->prepare("UPDATE $nama_tabel SET password = ? WHERE id = ?");
                    $update->execute([$password_hash, $admin_id]);
                    
                    $pesan = "Kata sandi berhasil diperbarui! Akun Anda kini dilindungi dengan enkripsi.";
                    $tipe = "success";
                } else {
                    $pesan = "Kata sandi baru dan konfirmasi tidak cocok. Silakan coba lagi.";
                    $tipe = "error";
                }
            } else {
                $pesan = "Kata sandi lama yang Anda masukkan salah.";
                $tipe = "error";
            }
        } else {
            $pesan = "Data pengguna tidak ditemukan.";
            $tipe = "error";
        }
    } catch (PDOException $e) {
        $pesan = "Terjadi kesalahan sistem: " . $e->getMessage();
        $tipe = "error";
    }
}
?>

<style>
    .pass-container { max-width: 550px; margin: 40px auto; background: #FFFFFF; border-radius: 20px; box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.08); border: 1px solid #E2E8F0; overflow: hidden; }
    .pass-header { background: linear-gradient(135deg, #0B132B 0%, #1C2541 100%); padding: 30px; text-align: center; color: #FFFFFF; }
    .pass-header h2 { margin: 0; font-size: 22px; font-weight: 800; letter-spacing: 0.5px; }
    .pass-header p { margin: 8px 0 0; font-size: 13px; color: #94A3B8; }
    .pass-body { padding: 30px; }
    .alert-box { padding: 14px 18px; border-radius: 10px; margin-bottom: 25px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
    .alert-success { background: #ECFDF5; color: #065F46; border: 1px solid #6EE7B7; }
    .alert-error { background: #FEF2F2; color: #991B1B; border: 1px solid #FCA5A5; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 8px; letter-spacing: 0.5px; }
    .input-wrapper { position: relative; display: flex; align-items: center; }
    
    /* Padding kanan diubah menjadi 110px agar teks yang panjang tidak tertutup tombol Sembunyikan */
    .form-group input { width: 100%; padding: 14px 110px 14px 16px; border: 1px solid #CBD5E1; border-radius: 10px; font-size: 14px; color: #0F172A; background: #F8FAFC; transition: all 0.3s ease; font-family: inherit; }
    .form-group input:focus { outline: none; border-color: #0284C7; background: #FFFFFF; box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.1); }
    
    /* Gaya tombol teks Lihat/Sembunyikan */
    .toggle-pass { position: absolute; right: 12px; background: #F1F5F9; border: 1px solid #CBD5E1; font-size: 12px; font-weight: 600; color: #0284C7; cursor: pointer; padding: 6px 12px; border-radius: 6px; transition: all 0.2s; font-family: inherit; }
    .toggle-pass:hover { background: #E2E8F0; color: #0369A1; }
    
    .btn-submit { width: 100%; background: #0284C7; color: #FFFFFF; border: none; padding: 16px; border-radius: 10px; font-size: 15px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; margin-top: 10px; }
    .btn-submit:hover { background: #0369A1; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(2, 132, 199, 0.3); }
</style>

<div class="pass-container">
    <div class="pass-header">
        <h2>🔒 Keamanan Akun</h2>
        <p>Perbarui kata sandi Anda secara berkala untuk menjaga keamanan panel admin SIPPT.</p>
    </div>
    
    <div class="pass-body">
        <?php if (!empty($pesan)): ?>
            <div class="alert-box <?= $tipe == 'success' ? 'alert-success' : 'alert-error' ?>">
                <span><?= $tipe == 'success' ? '✅' : '⚠️' ?></span>
                <?= $pesan ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>KATA SANDI LAMA</label>
                <div class="input-wrapper">
                    <input type="password" name="password_lama" id="pass_lama" placeholder="Masukkan kata sandi saat ini" required>
                    <button type="button" class="toggle-pass" onclick="togglePassword('pass_lama', this)">Lihat</button>
                </div>
            </div>

            <div class="form-group">
                <label>KATA SANDI BARU</label>
                <div class="input-wrapper">
                    <input type="password" name="password_baru" id="pass_baru" placeholder="Minimal 8 karakter" required>
                    <button type="button" class="toggle-pass" onclick="togglePassword('pass_baru', this)">Lihat</button>
                </div>
            </div>

            <div class="form-group">
                <label>KONFIRMASI KATA SANDI BARU</label>
                <div class="input-wrapper">
                    <input type="password" name="konfirmasi_baru" id="pass_konfirm" placeholder="Ketik ulang kata sandi baru" required>
                    <button type="button" class="toggle-pass" onclick="togglePassword('pass_konfirm', this)">Lihat</button>
                </div>
            </div>

            <button type="submit" class="btn-submit">Simpan Kata Sandi Baru</button>
        </form>
    </div>
</div>

<script>
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    if (input.type === "password") {
        input.type = "text";
        button.textContent = "Sembunyikan"; 
    } else {
        input.type = "password";
        button.textContent = "Lihat"; 
    }
}
</script>