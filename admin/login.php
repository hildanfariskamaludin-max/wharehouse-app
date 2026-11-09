<?php
/**
 * WAREHOUSE SYSTEM — Halaman Login Admin
 *
 * Keamanan:
 *  - PDO prepared statement (anti SQL Injection)
 *  - password_verify() terhadap hash password_hash()
 *  - session_regenerate_id(true) setelah login
 *  - CSRF token pada form
 *  - password salah tetap masuk dengan tampilan data acak (tanpa akses ubah)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

// Sudah login? Langsung ke dashboard.
if (is_admin_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$errors      = [];
$username    = '';
$notice      = '';
$noticeType  = 'info';

// Pesan status dari redirect (logout / sesi berakhir / belum login).
if (isset($_GET['logout'])) {
    $notice     = 'Anda telah keluar dari sistem dengan aman.';
    $noticeType = 'success';
} elseif (isset($_GET['timeout'])) {
    $notice = 'Sesi Anda berakhir karena tidak ada aktivitas. Silakan masuk kembali.';
} elseif (isset($_GET['expired'])) {
    $notice = 'Silakan masuk terlebih dahulu untuk mengakses dashboard admin.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $token    = $_POST['csrf_token'] ?? null;

    if (!verify_csrf(is_string($token) ? $token : null)) {
        $errors[] = 'Sesi formulir tidak valid atau kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif ($username === '' || $password === '') {
        $errors[] = 'Username dan password wajib diisi.';
    } elseif (mb_strlen($username) > 50 || strlen($password) > 255) {
        $errors[] = 'Username atau password melebihi panjang yang diizinkan.';
    } else {
        try {
            $pdo = db();
            $stmt = $pdo->prepare('SELECT id, username, password FROM admin WHERE username = :username LIMIT 1');
            $stmt->execute([':username' => $username]);
            $admin = $stmt->fetch();

            $dummyHash  = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
            $hash       = is_array($admin) ? $admin['password'] : $dummyHash;
            // ★ GATE LOGIN (presentasi): password dicek via bcrypt hash.
            //   Benar -> flag app_authenticated = true (dekripsi aktif).
            //   Salah -> flag false (tampil acak + simpan ditolak).
            $passwordOk = is_array($admin) && password_verify($password, $hash);

            session_regenerate_id(true);

            unset($_SESSION['csrf_token']);

            $_SESSION['app_authenticated'] = $passwordOk;
            $_SESSION['admin_id']          = $passwordOk
                ? (int) $admin['id']
                : 0;
            $_SESSION['admin_username']    = $passwordOk
                ? (string) $admin['username']
                : 'Tamu';
            $_SESSION['login_at']          = time();
            $_SESSION['last_activity']     = time();

            header('Location: dashboard.php');
            exit;
        } catch (Throwable $e) {
            error_log('[LOGIN] ' . $e->getMessage());
            $errors[] = 'Login tidak dapat diproses saat ini. Pastikan database sudah diimpor dan MySQL berjalan.';
        }
    }
}

// Token CSRF untuk form (diperbarui bila belum ada).
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Admin — WAREHOUSE SYSTEM</title>
<meta name="robots" content="noindex, nofollow">
<meta name="description" content="Halaman login administrator WAREHOUSE SYSTEM.">


<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet" href="../assets/css/style.css?v=3">
<link rel="stylesheet" href="../assets/css/login.css?v=3">
</head>
<body class="login-page">

<main class="login-shell">

  <header class="login-brand">
    <span class="login-brand__name">WAREHOUSE<span>SYSTEM</span></span>
  </header>

  <section class="login-card" aria-labelledby="loginTitle">

    <h1 class="login-title" id="loginTitle">Masuk ke Panel Admin</h1>
    <p class="login-subtitle">Silakan masuk menggunakan akun administrator Anda.</p>

    <?php if ($errors !== []): ?>
      <div class="login-alert login-alert--error" role="alert">
        <span class="login-alert__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><path d="M12 16h.01"/></svg>
        </span>
        <div class="login-alert__body">
          <strong>Login gagal</strong>
          <p><?= e($errors[0]) ?></p>
        </div>
      </div>
    <?php elseif ($notice !== ''): ?>
      <div class="login-alert login-alert--<?= e($noticeType) ?>" role="status">
        <span class="login-alert__icon" aria-hidden="true">
          <?php if ($noticeType === 'success'): ?>
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/></svg>
          <?php else: ?>
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><path d="M12 8h.01"/></svg>
          <?php endif; ?>
        </span>
        <div class="login-alert__body">
          <p><?= e($notice) ?></p>
        </div>
      </div>
    <?php endif; ?>

    <form class="login-form" method="post" action="login.php" id="loginForm">
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

      <div class="login-field">
        <label for="username">Username</label>
        <div class="login-input">
          <span class="login-input__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6.5 8-6.5s8 2.5 8 6.5"/></svg>
          </span>
          <input type="text" id="username" name="username" value="<?= e($username) ?>"
                 placeholder="Masukkan username" maxlength="50" autocomplete="username"
                 spellcheck="false" required autofocus>
        </div>
      </div>

      <div class="login-field">
        <label for="password">Password</label>
        <div class="login-input">
          <span class="login-input__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/><path d="M12 15v2"/></svg>
          </span>
          <input type="password" id="password" name="password"
                 placeholder="Masukkan password" maxlength="255"
                 autocomplete="current-password" required>
          <button type="button" class="pw-toggle" id="pwToggle"
                  aria-label="Tampilkan password" aria-pressed="false">
            <svg class="pw-icon pw-icon--show" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="pw-icon pw-icon--hide" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.9 10.9 0 0 1 12 19c-6.5 0-10-7-10-7a18.7 18.7 0 0 1 4.06-4.94"/><path d="M9.9 4.24A10.8 10.8 0 0 1 12 4c6.5 0 10 7 10 7a18.6 18.6 0 0 1-2.16 3.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M2 2l20 20"/></svg>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn--primary login-submit" id="loginSubmit">
        <span class="login-submit__text">Masuk ke Dashboard</span>
        <span class="login-submit__spinner" aria-hidden="true"></span>
        <svg class="login-submit__arrow" viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </button>
    </form>

    <p class="login-secure">
      <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-3.6 8-10V5l-8-3-8 3v7c0 6.4 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
      Koneksi terlindungi · Akses khusus administrator
    </p>

    <div class="login-divider" aria-hidden="true"><span></span></div>

    <a href="../index.php" class="login-back">
      <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Kembali ke Beranda
    </a>
  </section>

  <footer class="login-footer">
    <p>&copy; <?= date('Y') ?> WAREHOUSE SYSTEM · Panel Administrasi</p>
    <p>Belum memiliki akses? Hubungi administrator sistem.</p>
  </footer>

</main>

<script src="../assets/js/login.js" defer></script>
</body>
</html>
