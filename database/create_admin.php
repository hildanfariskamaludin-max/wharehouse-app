<?php
/**
 * Installer akun admin pertama — WAREHOUSE SYSTEM
 *
 * Jalankan lewat terminal (CLI), bukan browser:
 *     php database/create_admin.php
 *
 * Opsional, langsung menentukan username:
 *     php database/create_admin.php admin
 *
 * Script ini:
 *   - membuat database & tabel admin bila belum ada,
 *   - meminta username + password (input password disembunyikan bila terminal mendukung),
 *   - menyimpan password menggunakan password_hash(),
 *   - TIDAK menuliskan password asli ke dalam kode / file mana pun.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Script ini hanya dapat dijalankan melalui terminal (CLI).\n");
}

function out(string $message): void
{
    fwrite(STDOUT, $message . PHP_EOL);
}

function fail(string $message): void
{
    fwrite(STDERR, '[GAGAL] ' . $message . PHP_EOL);
    exit(1);
}

function ask(string $label): string
{
    fwrite(STDOUT, $label);
    $line = fgets(STDIN);
    if ($line === false) {
        fail('Input tidak tersedia. Jalankan secara interaktif di terminal.');
    }

    return trim($line);
}

/**
 * Membaca password tanpa menampilkannya di terminal bila memungkinkan.
 */
function ask_password(string $label): string
{
    $isWindows = PHP_OS_FAMILY === 'Windows';
    $isTty     = function_exists('stream_isatty') && @stream_isatty(STDIN);

    if ($isWindows && $isTty) {
        fwrite(STDOUT, $label);
        $command = 'powershell -NoProfile -Command "$s = Read-Host -AsSecureString; '
            . '$b = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($s); '
            . '[Runtime.InteropServices.Marshal]::PtrToStringBSTR($b)"';
        $result = shell_exec($command);
        if (is_string($result) && trim($result) !== '') {
            fwrite(STDOUT, PHP_EOL);
            return rtrim($result, "\r\n");
        }
        // Fallback bila PowerShell tidak tersedia.
        return ask($label);
    }

    if (!$isWindows && $isTty && function_exists('shell_exec')) {
        shell_exec('stty -echo');
        $value = ask($label);
        shell_exec('stty echo');
        fwrite(STDOUT, PHP_EOL);

        return $value;
    }

    // Mode non-interaktif (mis. input dari pipe) — password dibaca biasa.
    return ask($label);
}

out('');
out('=====================================================');
out('  WAREHOUSE SYSTEM - Pembuatan Akun Admin Pertama');
out('=====================================================');
out('');

$username = $argv[1] ?? '';
if ($username === '') {
    $username = ask('Username [admin]: ');
    if ($username === '') {
        $username = 'admin';
    }
}

if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
    fail('Username harus 3-50 karakter dan hanya boleh berisi huruf, angka, titik, garis bawah, atau strip.');
}

try {
    // 1) Pastikan database & tabel tersedia (aman dijalankan berulang).
    $server = db_server();
    if (!preg_match('/^[A-Za-z0-9_]+$/', DB_NAME)) {
        fail('Nama database tidak valid. Periksa konstanta DB_NAME.');
    }
    $server->exec(sprintf(
        'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
        DB_NAME
    ));

    $pdo = db();
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `admin` (
            `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `username`   VARCHAR(50)  NOT NULL,
            `password`   VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_admin_username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    // 2) Hanya untuk akun pertama.
    $adminCount = (int) $pdo->query('SELECT COUNT(*) FROM admin')->fetchColumn();
    if ($adminCount > 0) {
        out('[INFO] Tabel admin sudah berisi akun.');
        out('       Installer ini hanya membuat akun admin pertama.');
        out('       Jika ingin mengatur ulang, kelola langsung melalui database.');
        exit(0);
    }

    // 3) Minta password dua kali.
    out('Password minimal 8 karakter (disarankan 12+ kombinasi huruf, angka, dan simbol).');
    out('');

    while (true) {
        $password = ask_password('Password: ');
        if (strlen($password) < 8) {
            out('[!] Password minimal 8 karakter. Coba lagi.');
            continue;
        }
        if (strlen($password) > 255) {
            out('[!] Password terlalu panjang (maksimal 255 karakter).');
            continue;
        }
        if (strcasecmp($password, $username) === 0) {
            out('[!] Password tidak boleh sama dengan username.');
            continue;
        }

        $confirm = ask_password('Ulangi password: ');
        if (!hash_equals($password, $confirm)) {
            out('[!] Konfirmasi password tidak cocok. Coba lagi.');
            continue;
        }
        break;
    }

    // 4) Simpan hash — password asli tidak pernah disimpan.
    $hash = password_hash($password, PASSWORD_DEFAULT);
    if (!is_string($hash) || $hash === '') {
        fail('Gagal membuat hash password.');
    }

    $stmt = $pdo->prepare('INSERT INTO admin (username, password) VALUES (:username, :password)');
    $stmt->execute([
        ':username' => $username,
        ':password' => $hash,
    ]);

    // Bersihkan variabel sensitif dari memori.
    $password = $confirm = null;
    unset($password, $confirm);

    out('');
    out('[BERHASIL] Akun admin berhasil dibuat.');
    out('  Username : ' . $username);
    out('  Password : (disimpan sebagai hash password_hash)');
    out('');
    out('Langkah berikutnya:');
    out('  1. Pastikan Laragon berjalan.');
    out('  2. Buka http://localhost/UTS_KeamananData/admin/login.php');
    out('  3. Masuk menggunakan username dan password yang baru dibuat.');
    out('');
} catch (Throwable $e) {
    if ($e instanceof PDOException && $e->getCode() === '23000') {
        fail('Username sudah digunakan. Jalankan ulang dan pilih username lain.');
    }
    fail($e->getMessage());
}
