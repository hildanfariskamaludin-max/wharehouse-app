<?php
/**
 * Helper autentikasi & session admin.
 *
 * - Session cookie HttpOnly + SameSite=Lax
 * - Strict mode (mencegah session fixation dari ID yang tidak dikenal)
 * - Regenerasi ID dilakukan di admin/login.php setelah login berhasil
 * - Batas waktu idle 30 menit
 * - CSRF token untuk form login & logout
 */

declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');

const ADMIN_IDLE_TIMEOUT = 1800; // 30 menit (detik)

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_name('WHSSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

require_once __DIR__ . '/encryption.php';

/**
 * Escape output HTML.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Acak satu karakter teks (huruf/angka diacak; spasi dan tanda baca
 * dipertahankan agar layout tidak rusak).
 */
function scramble_random_text(string $text): string
{
    static $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    $out = '';
    foreach (mb_str_split($text) ?: str_split($text) as $ch) {
        if (preg_match('/^[\p{L}\p{N}]$/u', $ch)) {
            $out .= $chars[random_int(0, strlen($chars) - 1)];
            continue;
        }

        $out .= $ch;
    }

    return $out;
}

/**
 * Acak nilai atribut yang terlihat (placeholder, title, alt, aria-label,
 * value). Hidden input dilewati agar form dan CSRF tetap bekerja.
 */
function scramble_tag(string $tag): string
{
    if (preg_match('/^<input\b/i', $tag)
        && preg_match('/\btype\s*=\s*(?:"hidden"|\'hidden\'|hidden)/i', $tag)) {
        return $tag;
    }

    $pattern = '/\b(placeholder|title|alt|aria-label|value)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i';

    return preg_replace_callback($pattern, static function (array $m): string {
        $name  = $m[1];
        $value = $m[2] ?? ($m[3] ?? ($m[4] ?? ''));

        return $name . '="' . scramble_random_text($value) . '"';
    }, $tag);
}

/**
 * Acak seluruh teks tampilan halaman.
 * Tag HTML, atribut fungsional, dan isi <script>/<style> dibiarkan;
 * teks di luar tag diacak. Kata "Logout" dipertahankan agar tetap bisa keluar.
 */
function scramble_page_output(string $html): string
{
    $pattern = '/(<(?:[^>"\'<]|"[^"]*"|\'[^\']*\')*>)/u';
    $parts   = preg_split($pattern, $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

    if ($parts === false) {
        return $html;
    }

    $out = '';
    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }

        if ($part[0] === '<') {
            $out .= scramble_tag($part);
            continue;
        }

        if (trim($part) === 'Logout') {
            $out .= $part;
            continue;
        }

        $out .= scramble_random_text($part);
    }

    return $out;
}

/**
 * Redirect lalu hentikan eksekusi.
 */
function redirect(string $location): void
{
    header('Location: ' . $location);
    exit;
}

/**
 * Simpan pesan flash untuk ditampilkan pada request berikutnya.
 */
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Ambil (dan hapus) pesan flash dari session.
 *
 * @return array{type:string,message:string}|null
 */
function flash_get(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    if (!is_array($flash) || !isset($flash['type'], $flash['message'])) {
        return null;
    }

    return [
        'type'    => (string) $flash['type'],
        'message' => (string) $flash['message'],
    ];
}

/**
 * Format tanggal database (Y-m-d) menjadi format Indonesia.
 */
function tanggal_indo(?string $date): string
{
    if ($date === null || $date === '') {
        return '-';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }

    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    return date('d', $timestamp) . ' ' . $bulan[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
}

/**
 * Token CSRF milik session saat ini.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Verifikasi token CSRF menggunakan perbandingan waktu-konstan.
 */
function verify_csrf(?string $token): bool
{
    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function is_app_user(): bool
{
    return isset($_SESSION['app_authenticated']);
}

function is_admin_logged_in(): bool
{
    return is_app_user();
}

/**
 * Hentikan seluruh session admin dan hapus cookie session.
 */
function destroy_admin_session(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/**
 * Wajib masuk aplikasi. Redirect ke halaman login bila belum masuk
 * atau bila sesi melewati batas waktu idle.
 *
 * Password salah tetap boleh masuk; tetapi tanpa kredensial valid aplikasi
 * hanya menampilkan data acak dan menolak aksi ubah.
 */
function require_admin(): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');

    if (!is_app_authenticated()) {
        ob_start('scramble_page_output');
    }

    if (!is_app_user()) {
        header('Location: login.php?expired=1');
        exit;
    }

    $lastActivity = (int) ($_SESSION['last_activity'] ?? 0);
    if ($lastActivity > 0 && (time() - $lastActivity) > ADMIN_IDLE_TIMEOUT) {
        destroy_admin_session();
        header('Location: login.php?timeout=1');
        exit;
    }

    $_SESSION['last_activity'] = time();
}
