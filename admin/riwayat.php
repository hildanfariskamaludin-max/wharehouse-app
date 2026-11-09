<?php
/**
 * Riwayat Transaksi.
 * Menampilkan seluruh transaksi barang masuk dan keluar dari database,
 * diurutkan dari yang terbaru, dengan filter jenis dan pencarian barang.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

require_admin();

$pdo = db();

$jenis = (string) ($_GET['jenis'] ?? 'semua');
if (!in_array($jenis, ['semua', 'masuk', 'keluar'], true)) {
    $jenis = 'semua';
}

$search = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($search) > 50) {
    $search = mb_substr($search, 0, 50);
}

$where  = [];
$params = [];

if ($jenis !== 'semua') {
    $where[]          = 't.jenis = :jenis';
    $params[':jenis'] = $jenis;
}

$sql = 'SELECT t.id, t.tanggal, t.jenis, t.jumlah, t.keterangan, t.created_at,
               b.kode_barang, b.nama_barang, b.satuan
        FROM transaksi t
        INNER JOIN barang b ON b.id = t.barang_id';

if ($where !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY t.tanggal DESC, t.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Nama barang dan keterangan tersimpan terenkripsi.
foreach ($rows as &$row) {
    $row['kode_tampil']       = decrypt_sensitive((string) $row['kode_barang']);
    $row['nama_tampil']       = decrypt_sensitive((string) $row['nama_barang']);
    $row['satuan_tampil']     = decrypt_sensitive((string) $row['satuan']);
    $row['keterangan_tampil'] = $row['keterangan'] !== null && $row['keterangan'] !== ''
        ? decrypt_sensitive((string) $row['keterangan'])
        : '';
}
unset($row);

// Pencarian kode/nama/satuan dilakukan setelah dekripsi.
if ($search !== '') {
    $rows = array_values(array_filter(
        $rows,
        static function (array $row) use ($search): bool {
            return stripos((string) $row['kode_tampil'], $search) !== false
                || stripos((string) $row['nama_tampil'], $search) !== false
                || stripos((string) $row['satuan_tampil'], $search) !== false;
        }
    ));
}

$totalData   = count($rows);
$totalMasuk  = 0;
$totalKeluar = 0;

foreach ($rows as $row) {
    if ($row['jenis'] === 'masuk') {
        $totalMasuk += (int) $row['jumlah'];
    } else {
        $totalKeluar += (int) $row['jumlah'];
    }
}

$pageTitle  = 'Riwayat Transaksi';
$activeMenu = 'riwayat';

require __DIR__ . '/includes/header.php';
?>

<nav class="admin-breadcrumb" aria-label="Breadcrumb">
  <span>Panel Admin</span>
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
  <a href="dashboard.php">Dashboard</a>
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
  <span class="is-current">Riwayat</span>
</nav>

<div class="page-head">
  <div>
    <h1>Riwayat Transaksi</h1>
    <p>Seluruh transaksi barang masuk dan keluar, diurutkan dari yang terbaru.</p>
  </div>
  <div class="page-head__actions">
    <a href="barang_masuk.php" class="btn btn--outline btn--sm">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"/><path d="M8 11l4 4 4-4"/></svg>
      Barang Masuk
    </a>
    <a href="barang_keluar.php" class="btn btn--outline btn--sm">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21V9"/><path d="M8 13l4-4 4 4"/></svg>
      Barang Keluar
    </a>
  </div>
</div>

<section class="admin-panel filter-panel" aria-labelledby="filterTitle">
  <div class="admin-panel__head">
    <h2 id="filterTitle">Filter Riwayat</h2>
    <span class="panel-count"><?= number_format($totalData, 0, ',', '.') ?> transaksi</span>
  </div>

  <form method="get" action="riwayat.php" class="filter-bar">
    <div class="filter-field">
      <label for="jenis">Jenis Transaksi</label>
      <select id="jenis" name="jenis">
        <option value="semua"  <?= $jenis === 'semua' ? 'selected' : '' ?>>Semua Transaksi</option>
        <option value="masuk"  <?= $jenis === 'masuk' ? 'selected' : '' ?>>Barang Masuk</option>
        <option value="keluar" <?= $jenis === 'keluar' ? 'selected' : '' ?>>Barang Keluar</option>
      </select>
    </div>

    <div class="filter-field filter-search">
      <label for="q">Cari Barang</label>
      <div class="input-with-icon">
        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
        <input type="search" id="q" name="q" value="<?= e($search) ?>"
               placeholder="Kode atau nama barang..." maxlength="50">
      </div>
    </div>

    <div class="filter-actions">
      <button type="submit" class="btn btn--primary btn--sm">Terapkan</button>
      <?php if ($jenis !== 'semua' || $search !== ''): ?>
        <a href="riwayat.php" class="btn btn--ghost-dark btn--sm">Reset</a>
      <?php endif; ?>
    </div>
  </form>

  <?php if ($jenis !== 'semua' || $search !== ''): ?>
    <p class="filter-summary">
      Menampilkan
      <strong><?= $jenis === 'semua' ? 'semua jenis' : ($jenis === 'masuk' ? 'barang masuk' : 'barang keluar') ?></strong>
      <?php if ($search !== ''): ?>
        dengan kata kunci <strong>&ldquo;<?= e($search) ?>&rdquo;</strong>
      <?php endif; ?>.
    </p>
  <?php endif; ?>
</section>

<div class="summary-chips" aria-label="Ringkasan hasil filter">
  <span class="summary-chip">
    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 2.6-6.4"/><path d="M3 4v5h5"/><path d="M12 7v5l3 2"/></svg>
    Total transaksi: <strong><?= number_format($totalData, 0, ',', '.') ?></strong>
  </span>
  <span class="summary-chip summary-chip--green">
    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"/><path d="M8 11l4 4 4-4"/></svg>
    Unit masuk: <strong><?= number_format($totalMasuk, 0, ',', '.') ?></strong>
  </span>
  <span class="summary-chip summary-chip--orange">
    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21V9"/><path d="M8 13l4-4 4 4"/></svg>
    Unit keluar: <strong><?= number_format($totalKeluar, 0, ',', '.') ?></strong>
  </span>
</div>

<section class="admin-panel" aria-labelledby="historyTitle">
  <div class="admin-panel__head">
    <h2 id="historyTitle">Daftar Transaksi</h2>
    <span class="panel-count">Terbaru di atas</span>
  </div>

  <?php if ($rows === []): ?>
    <div class="empty-state">
      <span class="empty-state__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 2.6-6.4"/><path d="M3 4v5h5"/><path d="M12 7v5l3 2"/></svg>
      </span>
      <p><?= ($jenis !== 'semua' || $search !== '') ? 'Tidak ada transaksi yang sesuai filter.' : 'Belum ada transaksi tercatat.' ?></p>
      <small><?= ($jenis !== 'semua' || $search !== '') ? 'Coba ubah jenis transaksi atau kata kunci pencarian.' : 'Transaksi barang masuk dan keluar akan muncul di sini.' ?></small>
      <?php if ($jenis !== 'semua' || $search !== ''): ?>
        <a href="riwayat.php" class="btn btn--outline btn--sm">Reset Filter</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table data-table--history">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Kode Barang</th>
            <th>Nama Barang</th>
            <th>Jenis Transaksi</th>
            <th class="text-right">Jumlah</th>
            <th>Keterangan</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td class="text-nowrap"><?= e(tanggal_indo($row['tanggal'])) ?></td>
              <td><code class="code-chip<?= decryption_unlocked() ? '' : ' text-garbled' ?>"><?= e($row['kode_tampil']) ?></code></td>
              <td><strong class="table-primary<?= decryption_unlocked() ? '' : ' text-garbled' ?>"><?= e($row['nama_tampil']) ?></strong></td>
              <td>
                <span class="badge badge--<?= e($row['jenis']) ?>">
                  <?php if ($row['jenis'] === 'masuk'): ?>
                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v12"/><path d="M8 12l4 4 4-4"/></svg>
                    Masuk
                  <?php else: ?>
                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20V8"/><path d="M8 12l4-4 4 4"/></svg>
                    Keluar
                  <?php endif; ?>
                </span>
              </td>
              <td class="text-right text-nowrap">
                <span class="<?= $row['jenis'] === 'masuk' ? 'text-green' : 'text-orange' ?>">
                  <?= $row['jenis'] === 'masuk' ? '+' : '−' ?><?= number_format((int) $row['jumlah'], 0, ',', '.') ?>
                </span>
                <span class="<?= decryption_unlocked() ? '' : ' text-garbled' ?>"><?= e($row['satuan_tampil']) ?></span>
              </td>
              <td class="table-muted<?= decryption_unlocked() ? '' : ' text-garbled' ?>"><?= $row['keterangan_tampil'] !== '' ? e($row['keterangan_tampil']) : '—' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
