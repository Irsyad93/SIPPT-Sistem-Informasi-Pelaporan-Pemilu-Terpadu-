<?php
session_start();

if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

require_once '../config/database.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = 'Username dan Password wajib diisi!';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user) {
                // Logika pengecekan ganda (Hash & Plaintext)
                $is_valid = false;
                
                // 1. Cek jika password di database menggunakan enkripsi (Hash)
                if (password_verify($password, $user['password'])) {
                    $is_valid = true;
                } 
                // 2. Cek jika password di database masih teks biasa (Plaintext)
                elseif ($password === $user['password']) {
                    $is_valid = true;
                }

                if ($is_valid) {
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['admin_role'] = $user['role'];

                    header("Location: index.php");
                    exit;
                } else {
                    $error = 'Username atau Password salah!';
                }
            } else {
                $error = 'Username atau Password salah!';
            }
        } catch (PDOException $e) {
            $error = 'Kesalahan database: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Portal Pantau Pemilu</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h2>Panel Admin</h2>
            <p>Portal Pengawasan Pemilu</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="field">
                <label>Username</label>
                <input type="text" name="username" placeholder="Masukkan username" required autocomplete="off">
            </div>
            
            <div class="field">
                <label>Password</label>
                <div class="input-group">
                    <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                    <button type="button" class="toggle-password" onclick="togglePassword()">Lihat</button>
                </div>
            </div>

            <button type="submit" class="btn">Masuk ke Sistem</button>
        </form>
    </div>

    <script>
        function togglePassword() {
            const passInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.toggle-password');
            
            if (passInput.type === 'password') {
                passInput.type = 'text';
                toggleBtn.textContent = 'Sembunyikan';
            } else {
                passInput.type = 'password';
                toggleBtn.textContent = 'Lihat';
            }
        }
    </script>
</body>
</html>