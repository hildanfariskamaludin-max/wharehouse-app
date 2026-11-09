<?php
/**
 * Pencatatan Barang Keluar.
 * Stok barang berkurang otomatis dan transaksi tersimpan di tabel `transaksi`.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/transaksi.php';

require_admin();

$pdo = db();

$items = $pdo->query(
    'SELECT id, kode_barang, nama_barang, satuan, stok
     FROM barang
     ORDER BY nama_barang ASC'
)->fetchAll();

$itemsById = [];
foreach ($items as &$item) {
    $item['kode_tampil']             = decrypt_sensitive((string) $item['kode_barang']);
    $item['nama_tampil']             = decrypt_sensitive((string) $item['nama_barang']);
    $item['satuan_tampil']           = decrypt_sensitive((string) $item['satuan']);
    $itemsById[(int) $item['id']] = $item;
}
unset($item);

$errors = [];
$form   = [
    'barang_id'  => '',
    'jumlah'     => '',
    'tanggal'    => date('Y-m-d'),
    'keterangan' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;

    if (!verify_csrf(is_string($token) ? $token : null)) {
        flash_set('error', 'Sesi formulir tidak valid. Silakan coba lagi.');
        redirect('barang_keluar.php');
    }

    $form = [
        'barang_id'  => trim((string) ($_POST['barang_id'] ?? '')),
        'jumlah'     => trim((string) ($_POST['jumlah'] ?? '')),
        'tanggal'    => trim((string) ($_POST['tanggal'] ?? '')),
        'keterangan' => trim((string) ($_POST['keterangan'] ?? '')),
    ];

    if (!decryption_unlocked()) {
        $errors[] = 'Transaksi tidak dapat disimpan. Silakan coba lagi.';
    }

    $barangId = filter_var($form['barang_id'], FILTER_VALIDATE_INT);
    if (!$barangId || !isset($itemsById[$barangId])) {
        $errors[] = 'Pilih barang yang valid.';
    }

    $jumlah = filter_var($form['jumlah'], FILTER_VALIDATE_INT);
    if ($jumlah === false || $jumlah < 1 || $jumlah > 1000000000) {
        $errors[] = 'Jumlah harus berupa angka bulat lebih dari 0.';
    }

    $tanggal = DateTime::createFromFormat('Y-m-d', $form['tanggal']);
    if (!$tanggal || $tanggal->format('Y-m-d') !== $form['tanggal']) {
        $errors[] = 'Tanggal transaksi tidak valid.';
    }

    if (mb_strlen($form['keterangan']) > 255) {
        $errors[] = 'Keterangan maksimal 255 karakter.';
    }

    if ($errors === []) {
        try {
            simpan_transaksi(
                $pdo,
                (int) $barangId,
                'keluar',
                (int) $jumlah,
                $form['tanggal'],
                $form['keterangan'] !== '' ? $form['keterangan'] : null
            );

            flash_set('success', 'Transaksi barang keluar berhasil dicatat. Stok telah diperbarui.');
            redirect('barang_keluar.php');
        } catch (TransaksiException $e) {
            $errors[] = $e->getMessage();
        } catch (Throwable $e) {
            error_log('[BARANG KELUAR] ' . $e->getMessage());
            $errors[] = 'Transaksi gagal disimpan. Silakan coba lagi.';
        }
    }
}

$stmt = $pdo->query(
    "SELECT t.id, t.tanggal, t.jumlah, t.keterangan, b.kode_barang, b.nama_barang, b.satuan
     FROM transaksi t
     INNER JOIN barang b ON b.id = t.barang_id
     WHERE t.jenis = 'keluar'
     ORDER BY t.tanggal DESC, t.id DESC
     LIMIT 10"
);
$recent = $stmt->fetchAll();

foreach ($recent as &$tx) {
    $tx['kode_tampil']       = decrypt_sensitive((string) $tx['kode_barang']);
    $tx['nama_tampil']       = decrypt_sensitive((string) $tx['nama_barang']);
    $tx['satuan_tampil']     = decrypt_sensitive((string) $tx['satuan']);
    $tx['keterangan_tampil'] = $tx['keterangan'] !== null && $tx['keterangan'] !== ''
        ? decrypt_sensitive((string) $tx['keterangan'])
        : '';
}
unset($tx);

$pageTitle  = 'Barang Keluar';
$activeMenu = 'keluar';

require __DIR__ . '/includes/header.php';
?>

<nav class="admin-breadcrumb" aria-label="Breadcrumb">
  <span>Panel Admin</span>
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
  <a href="dashboard.php">Dashboard</a>
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
  <span class="is-current">Barang Keluar</span>
</nav>

<div class="page-head">
  <div>
    <h1>Barang Keluar</h1>
    <p>Catat pengeluaran barang. Stok akan berkurang dan tidak boleh melebihi stok tersedia.</p>
  </div>
  <div class="page-head__actions">
    <a href="barang.php" class="btn btn--outline btn--sm">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.27 6.96L12 12l8.73-5.04M12 22.08V12"/></svg>
      Data Barang
    </a>
  </div>
</div>

<div class="crud-layout">

  <section class="admin-panel form-panel" aria-labelledby="keluarFormTitle">
    <div class="admin-panel__head">
      <h2 id="keluarFormTitle">Form Barang Keluar</h2>
      <span class="admin-panel__icon admin-panel__icon--orange" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21V9"/><path d="M8 13l4-4 4 4"/><path d="M4 7V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2"/></svg>
      </span>
    </div>

    <?php if ($errors !== []): ?>
      <div class="admin-alert admin-alert--error" role="alert">
        <span class="admin-alert__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><path d="M12 16h.01"/></svg>
        </span>
        <div class="admin-alert__body">
          <strong>Transaksi belum dapat disimpan</strong>
          <ul class="admin-alert__list">
            <?php foreach ($errors as $error): ?>
              <li><?= e($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($items === []): ?>
      <div class="empty-state">
        <span class="empty-state__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.27 6.96L12 12l8.73-5.04M12 22.08V12"/></svg>
        </span>
        <p>Belum ada data barang.</p>
        <small>Tambahkan barang terlebih dahulu sebelum mencatat transaksi.</small>
        <a href="barang.php" class="btn btn--primary btn--sm">Tambah Data Barang</a>
      </div>
    <?php else: ?>
      <form method="post" action="barang_keluar.php" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="form-field">
          <label for="barang_id">Barang</label>
          <select id="barang_id" name="barang_id" required>
            <option value="">— Pilih barang —</option>
            <?php foreach ($items as $item): ?>
              <option value="<?= e($item['id']) ?>" <?= (string) $item['id'] === $form['barang_id'] ? 'selected' : '' ?>
                      <?= (int) $item['stok'] === 0 ? 'disabled' : '' ?>>
                <?= e($item['kode_tampil']) ?> — <?= e($item['nama_tampil']) ?>
                (stok: <?= number_format((int) $item['stok'], 0, ',', '.') ?> <?= e($item['satuan_tampil']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label for="jumlah">Jumlah Keluar</label>
            <input type="number" id="jumlah" name="jumlah" value="<?= e($form['jumlah']) ?>"
                   min="1" max="1000000000" step="1" placeholder="0" required>
          </div>

          <div class="form-field">
            <label for="tanggal">Tanggal</label>
            <input type="date" id="tanggal" name="tanggal" value="<?= e($form['tanggal']) ?>" required>
          </div>
        </div>

        <div class="form-field">
          <label for="keterangan">Keterangan <span class="label-optional">(opsional)</span></label>
          <textarea id="keterangan" name="keterangan" rows="3" maxlength="255"
                    placeholder="Contoh: Pengiriman pesanan pelanggan"><?= e($form['keterangan']) ?></textarea>
        </div>

        <p class="form-hint form-hint--orange">
          Jumlah keluar tidak boleh melebihi stok tersedia.
        </p>

        <div class="form-actions">
          <button type="submit" class="btn btn--primary">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
            Simpan Transaksi
          </button>
        </div>
      </form>
    <?php endif; ?>
  </section>

  <section class="admin-panel" aria-labelledby="recentKeluarTitle">
    <div class="admin-panel__head">
      <h2 id="recentKeluarTitle">Barang Keluar Terbaru</h2>
      <a href="riwayat.php?jenis=keluar" class="panel-link">Lihat semua</a>
    </div>

    <?php if ($recent === []): ?>
      <div class="empty-state empty-state--compact">
        <span class="empty-state__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21V9"/><path d="M8 13l4-4 4 4"/><path d="M4 7V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2"/></svg>
        </span>
        <p>Belum ada transaksi barang keluar.</p>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Barang</th>
              <th class="text-right">Jumlah</th>
              <th>Keterangan</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent as $tx): ?>
              <tr>
                <td class="text-nowrap"><?= e(tanggal_indo($tx['tanggal'])) ?></td>
                <td>
                  <strong class="table-primary<?= decryption_unlocked() ? '' : ' text-garbled' ?>"><?= e($tx['nama_tampil']) ?></strong>
                  <small class="table-secondary<?= decryption_unlocked() ? '' : ' text-garbled' ?>"><?= e($tx['kode_tampil']) ?></small>
                </td>
                <td class="text-right text-nowrap"><span class="text-orange">−<?= number_format((int) $tx['jumlah'], 0, ',', '.') ?></span> <span class="<?= decryption_unlocked() ? '' : ' text-garbled' ?>"><?= e($tx['satuan_tampil']) ?></span></td>
                <td class="table-muted<?= decryption_unlocked() ? '' : ' text-garbled' ?>"><?= $tx['keterangan_tampil'] !== '' ? e($tx['keterangan_tampil']) : '—' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
