<?php
/**
 * Dashboard admin.
 * Menampilkan ringkasan data barang dan transaksi langsung dari database.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

require_admin();

$pdo = db();

$totalBarang  = (int) $pdo->query('SELECT COUNT(*) FROM barang')->fetchColumn();
$totalStok    = (int) $pdo->query('SELECT COALESCE(SUM(stok), 0) FROM barang')->fetchColumn();
$masukBulan   = (int) $pdo->query(
    "SELECT COALESCE(SUM(jumlah), 0) FROM transaksi
     WHERE jenis = 'masuk' AND tanggal >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
)->fetchColumn();
$keluarBulan  = (int) $pdo->query(
    "SELECT COALESCE(SUM(jumlah), 0) FROM transaksi
     WHERE jenis = 'keluar' AND tanggal >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
)->fetchColumn();

$recent = $pdo->query(
    'SELECT t.id, t.tanggal, t.jenis, t.jumlah, b.kode_barang, b.nama_barang, b.satuan
     FROM transaksi t
     INNER JOIN barang b ON b.id = t.barang_id
     ORDER BY t.tanggal DESC, t.id DESC
     LIMIT 5'
)->fetchAll();

$barangRecent = $pdo->query(
    'SELECT id, kode_barang, nama_barang, satuan, stok
     FROM barang
     ORDER BY updated_at DESC, id DESC
     LIMIT 6'
)->fetchAll();

foreach ($barangRecent as &$item) {
    $item['kode_tampil']   = decrypt_sensitive((string) $item['kode_barang']);
    $item['nama_tampil']   = decrypt_sensitive((string) $item['nama_barang']);
    $item['satuan_tampil'] = decrypt_sensitive((string) $item['satuan']);
}
unset($item);

$username  = (string) ($_SESSION['admin_username'] ?? 'Admin');
$loginAt   = (int) ($_SESSION['login_at'] ?? time());
$loginLabel = tanggal_indo(date('Y-m-d', $loginAt)) . ', ' . date('H:i', $loginAt) . ' WIB';

$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard';

require __DIR__ . '/includes/header.php';
?>

<nav class="admin-breadcrumb" aria-label="Breadcrumb">
  <span>Panel Admin</span>
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
  <span class="is-current">Dashboard</span>
</nav>

<section class="welcome-card" aria-labelledby="welcomeTitle">
  <div class="welcome-card__content">
    <span class="welcome-badge">Ringkasan Gudang</span>
    <h1 id="welcomeTitle">Selamat datang, <?= e($username) ?></h1>
    <p>
      Kelola data barang, catat transaksi masuk dan keluar, lalu pantau seluruh
      riwayat gudang dari satu panel.
    </p>
    <div class="welcome-card__actions">
      <a href="barang.php" class="btn btn--primary">
        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
        Kelola Data Barang
      </a>
      <a href="riwayat.php" class="btn btn--ghost">
        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 2.6-6.4"/><path d="M3 4v5h5"/><path d="M12 7v5l3 2"/></svg>
        Lihat Riwayat
      </a>
    </div>
  </div>
  <div class="welcome-card__status">
    <span class="status-pill"><span class="status-pill__dot" aria-hidden="true"></span> Sesi Aktif</span>
    <small>Login: <?= e($loginLabel) ?></small>
  </div>
</section>

<section class="stat-grid" aria-label="Statistik gudang">
  <article class="stat-card">
    <span class="stat-card__icon stat-card__icon--navy" aria-hidden="true">
      <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.27 6.96L12 12l8.73-5.04M12 22.08V12"/></svg>
    </span>
    <div class="stat-card__body">
      <span class="stat-card__label">Data Barang</span>
      <strong class="stat-card__value"><?= number_format($totalBarang, 0, ',', '.') ?></strong>
      <small>SKU terdaftar</small>
    </div>
  </article>

  <article class="stat-card">
    <span class="stat-card__icon stat-card__icon--blue" aria-hidden="true">
      <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l9 5-9 5-9-5 9-5z"/><path d="M3 12l9 5 9-5M3 17l9 5 9-5"/></svg>
    </span>
    <div class="stat-card__body">
      <span class="stat-card__label">Total Stok</span>
      <strong class="stat-card__value"><?= number_format($totalStok, 0, ',', '.') ?></strong>
      <small>Unit tersimpan</small>
    </div>
  </article>

  <article class="stat-card">
    <span class="stat-card__icon stat-card__icon--green" aria-hidden="true">
      <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="M8 11l4 4 4-4"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
    </span>
    <div class="stat-card__body">
      <span class="stat-card__label">Barang Masuk</span>
      <strong class="stat-card__value"><?= number_format($masukBulan, 0, ',', '.') ?></strong>
      <small>Unit bulan ini</small>
    </div>
  </article>

  <article class="stat-card">
    <span class="stat-card__icon stat-card__icon--orange" aria-hidden="true">
      <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21V9"/><path d="M8 13l4-4 4 4"/><path d="M4 7V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2"/></svg>
    </span>
    <div class="stat-card__body">
      <span class="stat-card__label">Barang Keluar</span>
      <strong class="stat-card__value"><?= number_format($keluarBulan, 0, ',', '.') ?></strong>
      <small>Unit bulan ini</small>
    </div>
  </article>
</section>

<section class="admin-panel dashboard-barang" aria-labelledby="dashboardBarangTitle">
  <div class="admin-panel__head">
    <h2 id="dashboardBarangTitle">Data Barang</h2>
    <a href="barang.php" class="panel-link">Kelola</a>
  </div>

  <?php if ($barangRecent === []): ?>
    <p class="table-muted">Belum ada data barang.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Kode</th>
            <th>Nama Barang</th>
            <th>Satuan</th>
            <th class="text-right">Stok</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($barangRecent as $item): ?>
            <tr>
              <td><code class="code-chip<?= decryption_unlocked() ? '' : ' text-garbled' ?>"><?= e($item['kode_tampil']) ?></code></td>
              <td><strong class="table-primary<?= decryption_unlocked() ? '' : ' text-garbled' ?>"><?= e($item['nama_tampil']) ?></strong></td>
              <td class="<?= decryption_unlocked() ? '' : ' text-garbled' ?>"><?= e($item['satuan_tampil']) ?></td>
              <td class="text-right text-nowrap"><strong><?= number_format((int) $item['stok'], 0, ',', '.') ?></strong></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<section class="module-grid" aria-label="Akses cepat">
  <a href="barang.php" class="module-card">
    <span class="module-card__icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.27 6.96L12 12l8.73-5.04M12 22.08V12"/></svg>
    </span>
    <span class="module-card__text">
      <strong>Data Barang</strong>
      <small>Tambah, ubah, dan hapus data master barang.</small>
    </span>
    <svg class="module-card__arrow" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
  </a>

  <a href="barang_masuk.php" class="module-card">
    <span class="module-card__icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="M8 11l4 4 4-4"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
    </span>
    <span class="module-card__text">
      <strong>Barang Masuk</strong>
      <small>Catat penerimaan barang dan tambah stok.</small>
    </span>
    <svg class="module-card__arrow" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
  </a>

  <a href="barang_keluar.php" class="module-card">
    <span class="module-card__icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21V9"/><path d="M8 13l4-4 4 4"/><path d="M4 7V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2"/></svg>
    </span>
    <span class="module-card__text">
      <strong>Barang Keluar</strong>
      <small>Catat pengeluaran barang dan kurangi stok.</small>
    </span>
    <svg class="module-card__arrow" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
  </a>

  <a href="riwayat.php" class="module-card">
    <span class="module-card__icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 2.6-6.4"/><path d="M3 4v5h5"/><path d="M12 7v5l3 2"/></svg>
    </span>
    <span class="module-card__text">
      <strong>Riwayat</strong>
      <small>Telusuri seluruh transaksi masuk dan keluar.</small>
    </span>
    <svg class="module-card__arrow" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
  </a>
</section>

<div class="dashboard-grid">

  <section class="admin-panel" aria-labelledby="recentTitle">
    <div class="admin-panel__head">
      <h2 id="recentTitle">Transaksi Terbaru</h2>
      <a href="riwayat.php" class="panel-link">Lihat semua</a>
    </div>

    <?php if ($recent === []): ?>
      <div class="empty-state empty-state--compact">
        <span class="empty-state__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 2.6-6.4"/><path d="M3 4v5h5"/><path d="M12 7v5l3 2"/></svg>
        </span>
        <p>Belum ada transaksi.</p>
        <small>Catat barang masuk atau keluar untuk melihat riwayat di sini.</small>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Barang</th>
              <th>Jenis</th>
              <th class="text-right">Jumlah</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent as $tx): ?>
              <tr>
                <td class="text-nowrap"><?= e(tanggal_indo($tx['tanggal'])) ?></td>
                <td>
                  <strong class="table-primary<?= decryption_unlocked() ? '' : ' text-garbled' ?>"><?= e(decrypt_sensitive($tx['nama_barang'])) ?></strong>
                  <small class="table-secondary<?= decryption_unlocked() ? '' : ' text-garbled' ?>"><?= e(decrypt_sensitive($tx['kode_barang'])) ?></small>
                </td>
                <td><span class="badge badge--<?= e($tx['jenis']) ?>"><?= $tx['jenis'] === 'masuk' ? 'Masuk' : 'Keluar' ?></span></td>
                <td class="text-right text-nowrap"><?= number_format((int) $tx['jumlah'], 0, ',', '.') ?> <span class="<?= decryption_unlocked() ? '' : ' text-garbled' ?>"><?= e(decrypt_sensitive($tx['satuan'])) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <section class="admin-panel" aria-labelledby="sessionTitle">
    <div class="admin-panel__head">
      <h2 id="sessionTitle">Informasi Sesi</h2>
      <span class="admin-panel__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
      </span>
    </div>
    <dl class="session-list">
      <div><dt>Username</dt><dd><?= e($username) ?></dd></div>
      <div><dt>Peran</dt><dd>Administrator</dd></div>
      <div><dt>Waktu login</dt><dd><?= e($loginLabel) ?></dd></div>
      <div><dt>Batas idle</dt><dd>30 menit</dd></div>
      <div><dt>Status autentikasi</dt><dd><span class="pill pill--green">Terverifikasi</span></dd></div>
    </dl>
  </section>
</div>

<section class="admin-panel admin-panel--notes" aria-labelledby="notesTitle">
  <div class="admin-panel__head">
    <h2 id="notesTitle">Catatan</h2>
    <span class="admin-panel__icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
    </span>
  </div>
  <ul class="admin-notes">
    <li><span class="admin-notes__check" aria-hidden="true"><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></span>Data barang dan transaksi tersimpan di database MySQL.</li>
    <li><span class="admin-notes__check" aria-hidden="true"><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></span>Stok diperbarui otomatis saat transaksi masuk atau keluar dicatat.</li>
    <li><span class="admin-notes__check" aria-hidden="true"><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></span>Riwayat dapat difilter berdasarkan jenis transaksi dan dicari berdasarkan barang.</li>
    <li><span class="admin-notes__check" aria-hidden="true"><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></span>Nama barang dan keterangan transaksi disimpan terenkripsi (AES-256-CTR).</li>
    <li><span class="admin-notes__check" aria-hidden="true"><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></span>Password dekripsi terpisah dari password login admin.</li>
  </ul>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
