<?php
/**
 * Layout atas panel admin.
 *
 * Variabel yang dapat diisi sebelum include:
 *   $pageTitle  (string)  — judul halaman, default "Dashboard"
 *   $activeMenu (string)  — dashboard|masuk|keluar|riwayat
 */

declare(strict_types=1);

$pageTitle  = $pageTitle ?? 'Dashboard';
$activeMenu = $activeMenu ?? 'dashboard';
$username   = (string) ($_SESSION['admin_username'] ?? 'Admin');
$initial    = mb_strtoupper(mb_substr($username, 0, 1));
$flash      = flash_get();
$userRole   = is_app_authenticated() ? 'Administrator' : 'Tamu';

$navClass = static fn (string $key): string => 'admin-nav__link' . ($activeMenu === $key ? ' is-active' : '');
$navAria  = static fn (string $key): string => $activeMenu === $key ? ' aria-current="page"' : '';
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> — WAREHOUSE SYSTEM</title>
<meta name="robots" content="noindex, nofollow">


<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet" href="../assets/css/style.css?v=4">
<link rel="stylesheet" href="../assets/css/admin.css?v=4">
</head>
<body class="admin-page">

<div class="admin-overlay" id="sidebarOverlay" aria-hidden="true"></div>

<aside class="admin-sidebar" id="adminSidebar" aria-label="Navigasi panel admin">

  <a href="dashboard.php" class="admin-sidebar__brand" aria-label="WAREHOUSE SYSTEM — Dashboard">
    <span class="admin-sidebar__logo" aria-hidden="true">
      <svg viewBox="0 0 48 48" width="38" height="38" fill="none">
        <rect width="48" height="48" rx="12" fill="url(#gSidebarLogo)"/>
        <path d="M10 26 L24 12 L38 26" stroke="#fff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M15 24.5 V36 H33 V24.5" stroke="#fff" stroke-width="3.2" stroke-linejoin="round"/>
        <path d="M21.5 36 V29.5 H26.5 V36" stroke="#BFDBFE" stroke-width="2.6" stroke-linejoin="round"/>
        <defs>
          <linearGradient id="gSidebarLogo" x1="0" y1="0" x2="48" y2="48" gradientUnits="userSpaceOnUse">
            <stop stop-color="#0066FF"/><stop offset="1" stop-color="#0052CC"/>
          </linearGradient>
        </defs>
      </svg>
    </span>
    <span class="admin-sidebar__brandtext">
      <strong>WAREHOUSE<span>SYSTEM</span></strong>
      <small>Panel Admin</small>
    </span>
  </a>

  <nav class="admin-nav" aria-label="Menu utama">
    <span class="admin-nav__label">Menu Utama</span>

    <a href="dashboard.php" class="<?= $navClass('dashboard') ?>"<?= $navAria('dashboard') ?>>
      <span>Dashboard</span>
    </a>

    <a href="barang_masuk.php" class="<?= $navClass('masuk') ?>"<?= $navAria('masuk') ?>>
      <span>Barang Masuk</span>
    </a>

    <a href="barang_keluar.php" class="<?= $navClass('keluar') ?>"<?= $navAria('keluar') ?>>
      <span>Barang Keluar</span>
    </a>

    <a href="riwayat.php" class="<?= $navClass('riwayat') ?>"<?= $navAria('riwayat') ?>>
      <span>Riwayat</span>
    </a>
  </nav>

  <div class="admin-sidebar__bottom">
    <p class="admin-sidebar__version">WAREHOUSE SYSTEM v1.0</p>
    <form action="logout.php" method="post" class="admin-sidebar__logout">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <button type="submit" class="admin-nav__link admin-nav__link--logout">
        <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
        <span>Logout</span>
      </button>
    </form>
  </div>
</aside>

<div class="admin-shell">

  <header class="admin-topbar">
    <div class="admin-topbar__left">
      <button type="button" class="admin-menu-toggle" id="sidebarToggle"
              aria-label="Buka menu navigasi" aria-controls="adminSidebar" aria-expanded="false">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
      </button>
      <div class="admin-topbar__heading">
        <span>Panel Admin</span>
        <strong><?= e($pageTitle) ?></strong>
      </div>
    </div>

    <div class="admin-topbar__right">
      <a href="../index.php" class="admin-topbar__site" target="_blank" rel="noopener">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 4 5.7 4 9s-1.5 6.4-4 9c-2.5-2.6-4-5.7-4-9s1.5-6.4 4-9z"/></svg>
        <span>Lihat Website</span>
      </a>

      <div class="admin-user">
        <span class="admin-user__avatar" aria-hidden="true"><?= e($initial) ?></span>
        <span class="admin-user__meta">
          <strong><?= e($username) ?></strong>
          <small><?= e($userRole) ?></small>
        </span>
      </div>
    </div>
  </header>

  <main class="admin-main">
    <div class="admin-container">

      <?php if ($flash !== null): ?>
        <div class="admin-alert admin-alert--<?= e($flash['type']) ?>" role="status">
          <span class="admin-alert__icon" aria-hidden="true">
            <?php if ($flash['type'] === 'success'): ?>
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/></svg>
            <?php else: ?>
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><path d="M12 16h.01"/></svg>
            <?php endif; ?>
          </span>
          <p><?= e($flash['message']) ?></p>
        </div>
      <?php endif; ?>
