<?php
/**
 * WAREHOUSE SYSTEM — Personal Inventory Management Landing Page
 * Tech: PHP Native + HTML5 + CSS3 + JavaScript
 *
 * Desain: Modern, Minimalist, Personal Warehouse
 * Referensi Visual: Desain Spesifik (bukan StackAdapt)
 *
 * Data di bawah ini bersifat CONTOH (placeholder) dan mudah diganti.
 */
$year    = date('Y');
$brand   = 'WAREHOUSE SYSTEM';

// Data kontak — CONTOH / PLACEHOLDER, ganti dengan data resmi perusahaan.
$contact = [
    'address' => 'Jl. Raya Storage No. 88, Jakarta Timur 13930',
    'email'   => 'halo@warehousesystem.co.id',
    'phone'   => '(021) 5550-1122',
    'hours'   => 'Senin – Sabtu, 08.00 – 17.00 WIB',
];

// Navigasi utama
$nav = [
    ['label' => 'Beranda',      'href' => '#beranda'],
    ['label' => 'Tentang',      'href' => '#tentang'],
    ['label' => 'Layanan',      'href' => '#layanan'],
    ['label' => 'Kontak',       'href' => '#kontak'],
];
?>
<!DOCTYPE html>
<html lang="id" data-theme="light" class="<?= isset($_GET['shot']) ? 'shot-mode' : '' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WAREHOUSE SYSTEM — Pengelolaan Gudang Pribadi</title>
<meta name="description" content="WAREHOUSE SYSTEM adalah solusi Sistem Informasi Manajemen Gudang Pribadi untuk pengelolaan inventaris rumah tangga, pemantauan stok, serta pencatatan barang masuk dan keluar secara terpusat dan real-time.">
<meta name="keywords" content="sistem informasi manajemen gudang pribadi, manajemen inventaris rumah tangka, pengelolaan stok pribadi, penyimpanan organisir">


<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<!-- Stylesheet -->
<link rel="stylesheet" href="assets/css/style.css?v=<?= $year ?>">
<noscript><style>.reveal{opacity:1 !important;transform:none !important}</style></noscript>
</head>
<body>

<!-- ==================================================
     1. HEADER
=================================================== -->
<header class="navbar" id="navbar" style="background: #000000; height: 72px; padding: 0 5%; display: flex; align-items: center; justify-content: space-between; width: 100%;">
  <a href="#beranda" class="logo" style="color: #ffffff; font-size: 1.5rem; font-weight: 700; letter-spacing: 0.02em; text-decoration: none; white-space: nowrap;">
    <span style="color: #0066FF; font-weight: 800;">WAREHOUSE</span> SYSTEM
  </a>
  <a href="admin/login.php" class="btn-login" style="background: #0066FF; color: #ffffff; font-size: 0.85rem; font-weight: 600; padding: 0.7rem 1.5rem; border: none; border-radius: 12px; text-decoration: none;">
    Login Admin
  </a>
</header>

<!-- ==================================================
     2. HERO SECTION
=================================================== -->
<section class="hero" aria-label="Beranda" style="background: #000000; padding: 4rem 5%; position: relative; overflow: hidden;">
  <div class="container hero-container" style="max-width: 1200px; margin: 0 auto; padding: 0; width: 100%;">
    <div class="hero-split" style="display: grid; grid-template-columns: 1.05fr 0.95fr; gap: 3.5rem; align-items: center;">
      <div class="hero-content" style="max-width: 560px;">
        <h1 style="color: #ffffff; font-size: clamp(2.4rem, 4vw, 3.25rem); font-weight: 700; line-height: 1.12; letter-spacing: -0.02em; margin: 0 0 0.85rem;">
          Kelola Barang Pribadi dengan Lebih Mudah
        </h1>
        <p style="color: #AAB3C0; font-size: 1.05rem; line-height: 1.7; margin: 0; max-width: 48ch;">
          Simpan, atur, dan pantau seluruh barang pribadi Anda dalam satu sistem pengelolaan yang praktis dan terorganisir.
        </p>
      </div>
      <div class="hero-image" style="width: 100%; height: 500px; overflow: hidden; border-radius: 16px;">
        <img src="assets/img/hero-warehouse.jpg" alt="Ruang penyimpanan pribadi yang terorganisir dengan rak, kotak-barang, dan perlengkapan rumah tangga" style="width: 100%; height: 100%; object-fit: cover; object-position: center; display: block;">
      </div>
    </div>
  </div>
</section>

<!-- ==================================================
     3. SECTION BELOW THE HERO
=================================================== -->
<section class="section" style="background: #ffffff; padding: 6rem 4%;">
  <div class="container" style="max-width: none;">
    <div class="section-content" style="display: flex; gap: 4%; align-items: center;">
      <div class="section-image" style="width: 50%; height: 420px; flex-shrink: 0; overflow: hidden; border-radius: 0;">
        <img src="assets/img/about-workers.jpg" alt="Ruang penyimpanan pribadi yang terorganisir dengan rak penyimpanan dan kotak-barang" style="width: 100%; height: 420px; object-fit: cover; object-position: center; display: block; border-radius: 0;">
      </div>
      <div class="section-text" style="width: 50%;">
        <h2 style="color: #000000; font-size: clamp(2.5rem, 4vw, 3rem); font-weight: 700; line-height: 1.2; margin: 0 0 1rem;">
          Organisasi yang Menyenangkan
        </h2>
        <p style="color: #AAB3C0; font-size: 1.1rem; line-height: 1.8; margin: 0;">
          Sistem pengelolaan inventory pribadi memudahkan Anda menemukan barang saat perlu, melacak stok, dan menjaga rumah tangga terstruktur tanpa repot.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- ==================================================
     4. FOOTER (optional, minimal)
=================================================== -->
<footer class="footer" style="background: #000000; color: #ffffff; padding: 3rem 4%; text-align: center;">
  <div style="max-width: none; width: calc(100% - 8%); margin: 0 auto;">
    <p style="margin: 0 0 1rem;">&copy; <?= $year ?> <?= $brand ?>. Semua hak dilindungi.</p>
    <p style="margin: 0; font-size: 0.85rem; opacity: 0.7;">Sistem Informasi Manajemen Gudang Pribadi</p>
  </div>
</footer>

<!-- ══════════ MODAL ARTIKEL ══════════ -->
<div class="modal" id="articleModal" role="dialog" aria-modal="true" aria-labelledby="articleModalTitle" hidden>
  <div class="modal__backdrop" data-close-modal></div>
  <div class="modal__dialog" role="document">
    <button type="button" class="modal__close" data-close-modal aria-label="Tutup artikel">
      <svg viewBox="0 0 24 24" width "20" height "20" fill "none" stroke "currentColor" stroke-width "1.8" stroke-linecap "round" stroke-linejoin "round" aria-hidden "true"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
    <div class="modal__content" id="articleModalContent"></div>
  </div>
</div>

<!-- Tombol kembali ke atas -->
<button type="button" class="back-to-top" id="backToTop" aria-label="Kembali ke atas halaman">
  <svg viewBox="0 0 24 24" width "20" height "20" fill "none" stroke "currentColor" stroke-width "2" stroke-linecap "round" stroke-linejoin "round" aria-hidden "true"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
</button>

<!-- Toast notifikasi -->
<div class="toast" id="toast" role="status" aria-live="polite" hidden></div>

<script src="assets/js/script.js?v=<?= $year ?>" defer></script>
</body>
</html>