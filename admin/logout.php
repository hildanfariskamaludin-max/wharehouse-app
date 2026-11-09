<?php
/**
 * Logout admin — hanya menerima POST + CSRF token yang valid.
 * Seluruh data session dihapus, cookie session dikedaluwarsakan.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . (is_admin_logged_in() ? 'dashboard.php' : 'login.php'));
    exit;
}

$token = $_POST['csrf_token'] ?? null;
if (!verify_csrf(is_string($token) ? $token : null)) {
    http_response_code(403);
    exit('Permintaan logout tidak valid. Silakan muat ulang dashboard dan coba lagi.');
}

destroy_admin_session();

header('Location: login.php?logout=1');
exit;
