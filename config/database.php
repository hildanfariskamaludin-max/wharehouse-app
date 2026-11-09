<?php
/**
 * Konfigurasi koneksi database — PDO (MySQL/MariaDB)
 *
 * Nilai default mengikuti instalasi Laragon (root tanpa password).
 * Ubah sesuai konfigurasi Anda, atau gunakan environment variable:
 * DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS.
 *
 * CATATAN: file ini tidak menampilkan apa pun jika diakses langsung.
 */

declare(strict_types=1);

define('DB_HOST', getenv('DB_HOST') !== false ? getenv('DB_HOST') : '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') !== false ? getenv('DB_PORT') : '3306');
define('DB_NAME', getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'warehouse_system');
define('DB_USER', getenv('DB_USER') !== false ? getenv('DB_USER') : 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

/**
 * Membuat / mengembalikan koneksi PDO ke database aplikasi.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        DB_HOST,
        DB_PORT,
        DB_NAME
    );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        error_log('[DB] Koneksi gagal: ' . $e->getMessage());
        throw new RuntimeException('Koneksi database gagal. Pastikan MySQL Laragon berjalan dan database sudah diimpor.');
    }

    return $pdo;
}

/**
 * Koneksi ke server MySQL tanpa memilih database.
 * Digunakan oleh installer akun admin pertama.
 */
function db_server(): PDO
{
    $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT);

    try {
        return new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        error_log('[DB] Koneksi server gagal: ' . $e->getMessage());
        throw new RuntimeException('Tidak dapat terhubung ke server MySQL. Periksa pengaturan di config/database.php.');
    }
}
