<?php
/**
 * CRUD Data Barang.
 * Diakomodasi dari kartu "Data Barang" pada Dashboard.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

require_admin();

$pdo    = db();
$errors = [];
$form   = [
    'id'          => 0,
    'kode_barang' => '',
    'nama_barang' => '',
    'satuan'      => 'pcs',
    'stok'        => 0,
];

/* ── Mode edit (GET) ───────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    if (!decryption_unlocked()) {
        flash_set('error', 'Data tidak dapat diedit. Silakan coba lagi.');
        redirect('barang.php');
    }

    $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);

    if ($editId) {
        $stmt = $pdo->prepare('SELECT id, kode_barang, nama_barang, satuan, stok FROM barang WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $editId]);
        $row = $stmt->fetch();

        if ($row) {
            $form                = $row;
            $form['kode_barang'] = decrypt_sensitive((string) $row['kode_barang']);
            $form['nama_barang'] = decrypt_sensitive((string) $row['nama_barang']);
            $form['satuan']      = decrypt_sensitive((string) $row['satuan']);
        } else {
            flash_set('error', 'Data barang tidak ditemukan.');
            redirect('barang.php');
        }
    }
}

/* ── Proses POST ───────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;

    if (!verify_csrf(is_string($token) ? $token : null)) {
        flash_set('error', 'Sesi formulir tidak valid. Silakan coba lagi.');
        redirect('barang.php');
    }

    $action = (string) ($_POST['action'] ?? '');

    /* Hapus barang */
    if ($action === 'delete') {
        if (!decryption_unlocked()) {
            flash_set('error', 'Data tidak dapat dihapus. Silakan coba lagi.');
            redirect('barang.php');
        }

        $deleteId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

        if (!$deleteId) {
            flash_set('error', 'Data barang tidak valid.');
            redirect('barang.php');
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM transaksi WHERE barang_id = :id');
        $stmt->execute([':id' => $deleteId]);
        $used = (int) $stmt->fetchColumn();

        if ($used > 0) {
            flash_set('error', 'Barang tidak dapat dihapus karena sudah memiliki riwayat transaksi.');
            redirect('barang.php');
        }

        $stmt = $pdo->prepare('DELETE FROM barang WHERE id = :id');
        $stmt->execute([':id' => $deleteId]);

        flash_set('success', 'Data barang berhasil dihapus.');
        redirect('barang.php');
    }

    /* Simpan (tambah / edit) */
    if ($action === 'save') {
        $form = [
            'id'          => (int) ($_POST['id'] ?? 0),
            'kode_barang' => strtoupper(trim((string) ($_POST['kode_barang'] ?? ''))),
            'nama_barang' => trim((string) ($_POST['nama_barang'] ?? '')),
            'satuan'      => trim((string) ($_POST['satuan'] ?? '')),
            'stok'        => trim((string) ($_POST['stok'] ?? '')),
        ];

        if (!preg_match('/^[A-Z0-9._-]{2,30}$/', $form['kode_barang'])) {
            $errors[] = 'Kode barang wajib 2–30 karakter, hanya huruf, angka, titik, garis bawah, atau strip.';
        }

        $namaLength = mb_strlen($form['nama_barang']);
        if ($namaLength < 2 || $namaLength > 100) {
            $errors[] = 'Nama barang wajib 2–100 karakter.';
        }

        if ($form['satuan'] === '' || mb_strlen($form['satuan']) > 20) {
            $errors[] = 'Satuan wajib diisi, maksimal 20 karakter.';
        }

        $stok = filter_var($form['stok'], FILTER_VALIDATE_INT);
        if ($stok === false || $stok < 0 || $stok > 1000000000) {
            $errors[] = 'Stok harus berupa angka bulat antara 0 dan 1.000.000.000.';
        }

        if ($form['id'] > 0) {
            $stmt = $pdo->prepare('SELECT id FROM barang WHERE id = :id');
            $stmt->execute([':id' => $form['id']]);
            if (!$stmt->fetch()) {
                $errors[] = 'Data barang yang akan diperbarui tidak ditemukan.';
            }
        }

        if ($errors === [] && !decryption_unlocked()) {
            $errors[] = 'Data tidak dapat disimpan. Silakan coba lagi.';
        }

        if ($errors === []) {
            $rowsKode = $pdo->query('SELECT id, kode_barang FROM barang')->fetchAll();
            $kodeSama = false;

            foreach ($rowsKode as $rowKode) {
                if ($form['id'] > 0 && (int) $rowKode['id'] === $form['id']) {
                    continue;
                }

                if (strcasecmp(decrypt_sensitive((string) $rowKode['kode_barang']), $form['kode_barang']) === 0) {
                    $kodeSama = true;
                    break;
                }
            }

            if ($kodeSama) {
                $errors[] = 'Kode barang sudah digunakan. Gunakan kode lain.';
            }
        }

        if ($errors === []) {
            try {
                // ★ ENKRIPSI (presentasi): plaintext -> ciphertext AES-256 sebelum simpan DB.
                $kodeTerenkripsi   = encrypt_sensitive($form['kode_barang']);
                $namaTerenkripsi   = encrypt_sensitive($form['nama_barang']);
                $satuanTerenkripsi = encrypt_sensitive($form['satuan']);
            } catch (Throwable $e) {
                error_log('[BARANG] Enkripsi data barang gagal: ' . $e->getMessage());
                $errors[] = 'Data barang tidak dapat dienkripsi. Silakan coba lagi.';
            }
        }

        if ($errors === []) {
            if ($form['id'] > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE barang
                     SET kode_barang = :kode, nama_barang = :nama, satuan = :satuan, stok = :stok
                     WHERE id = :id'
                );
                $stmt->execute([
                    ':kode'   => $kodeTerenkripsi,
                    ':nama'   => $namaTerenkripsi,
                    ':satuan' => $satuanTerenkripsi,
                    ':stok'   => $stok,
                    ':id'     => $form['id'],
                ]);
                flash_set('success', 'Data barang berhasil diperbarui.');
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO barang (kode_barang, nama_barang, satuan, stok)
                     VALUES (:kode, :nama, :satuan, :stok)'
                );
                $stmt->execute([
                    ':kode'   => $kodeTerenkripsi,
                    ':nama'   => $namaTerenkripsi,
                    ':satuan' => $satuanTerenkripsi,
                    ':stok'   => $stok,
                ]);
                flash_set('success', 'Barang baru berhasil ditambahkan.');
            }

            redirect('barang.php');
        }
    }
}

$editId  = (int) $form['id'];
$isEdit  = $editId > 0;
$search  = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($search) > 50) {
    $search = mb_substr($search, 0, 50);
}

$stmt = $pdo->query(
    'SELECT id, kode_barang, nama_barang, satuan, stok, updated_at FROM barang ORDER BY id ASC'
);
$items = $stmt->fetchAll();

// Kode, nama, dan satuan tersimpan terenkripsi.
// ★ DEKRIPSI (presentasi): ciphertext DB -> plaintext untuk tampil di halaman admin.
foreach ($items as &$item) {
    $item['kode_tampil']   = decrypt_sensitive((string) $item['kode_barang']);
    $item['nama_tampil']   = decrypt_sensitive((string) $item['nama_barang']);
    $item['satuan_tampil'] = decrypt_sensitive((string) $item['satuan']);
}
unset($item);

if ($search !== '') {
    $items = array_values(array_filter(
        $items,
        static function (array $item) use ($search): bool {
            return stripos((string) $item['kode_tampil'], $search) !== false
                || stripos((string) $item['nama_tampil'], $search) !== false
                || stripos((string) $item['satuan_tampil'], $search) !== false;
        }
    ));
}

if (decryption_unlocked()) {
    usort($items, static function (array $a, array $b): int {
        $cmp = strcasecmp((string) $a['nama_tampil'], (string) $b['nama_tampil']);
        if ($cmp !== 0) {
            return $cmp;
        }

        return strcasecmp((string) $a['kode_tampil'], (string) $b['kode_tampil']);
    });
}

$pageTitle  = 'Data Barang';
$activeMenu = 'dashboard';

require __DIR__ . '/includes/header.php';
?>

<nav class="admin-breadcrumb" aria-label="Breadcrumb">
  <span>Panel Admin</span>
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
  <a href="dashboard.php">Dashboard</a>
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
  <span class="is-current">Data Barang</span>
</nav>

<div class="page-head">
  <div>
    <h1>Data Barang</h1>
    <p>Kelola data master barang yang digunakan pada transaksi gudang.</p>
  </div>
  <div class="page-head__actions">
    <a href="riwayat.php" class="btn btn--outline btn--sm">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 2.6-6.4"/><path d="M3 4v5h5"/><path d="M12 7v5l3 2"/></svg>
      Riwayat Transaksi
    </a>
  </div>
</div>

<div class="crud-layout">

  <section class="admin-panel form-panel" aria-labelledby="formTitle">
    <div class="admin-panel__head">
      <h2 id="formTitle"><?= $isEdit ? 'Edit Barang' : 'Tambah Barang' ?></h2>
      <span class="admin-panel__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
      </span>
    </div>

    <?php if ($errors !== []): ?>
      <div class="admin-alert admin-alert--error" role="alert">
        <span class="admin-alert__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><path d="M12 16h.01"/></svg>
        </span>
        <div class="admin-alert__body">
          <strong>Periksa kembali data berikut</strong>
          <ul class="admin-alert__list">
            <?php foreach ($errors as $error): ?>
              <li><?= e($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    <?php endif; ?>

    <form method="post" action="barang.php" class="admin-form">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= e($editId) ?>">

      <div class="form-field">
        <label for="kode_barang">Kode Barang</label>
        <input type="text" id="kode_barang" name="kode_barang" value="<?= e($form['kode_barang']) ?>"
               maxlength="30" placeholder="Contoh: BRG-001" autocomplete="off" required>
      </div>

      <div class="form-field">
        <label for="nama_barang">Nama Barang</label>
        <input type="text" id="nama_barang" name="nama_barang" value="<?= e($form['nama_barang']) ?>"
               maxlength="100" placeholder="Contoh: Karton Box Besar" autocomplete="off" required>
      </div>

      <div class="form-row">
        <div class="form-field">
          <label for="satuan">Satuan</label>
          <input type="text" id="satuan" name="satuan" value="<?= e($form['satuan']) ?>"
                 maxlength="20" placeholder="pcs" autocomplete="off" required>
        </div>

        <div class="form-field">
          <label for="stok">Stok</label>
          <input type="number" id="stok" name="stok" value="<?= e($form['stok']) ?>"
                 min="0" max="1000000000" step="1" required>
        </div>
      </div>

      <p class="form-hint">Stok diperbarui otomatis saat transaksi masuk/keluar dicatat.</p>

      <div class="form-actions">
        <button type="submit" class="btn btn--primary">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
          <?= $isEdit ? 'Simpan Perubahan' : 'Tambah Barang' ?>
        </button>

        <?php if ($isEdit): ?>
          <a href="barang.php" class="btn btn--outline">Batal</a>
        <?php endif; ?>
      </div>
    </form>
  </section>

  <section class="admin-panel" aria-labelledby="listTitle">
    <div class="admin-panel__head">
      <h2 id="listTitle">Daftar Barang</h2>
      <span class="panel-count"><?= number_format(count($items), 0, ',', '.') ?> barang</span>
    </div>

    <form method="get" action="barang.php" class="filter-bar filter-bar--inline">
      <div class="filter-field filter-search">
        <label for="q">Cari barang</label>
        <div class="input-with-icon">
          <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
          <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Kode atau nama barang..." maxlength="50">
        </div>
      </div>
      <div class="filter-actions">
        <button type="submit" class="btn btn--outline btn--sm">Cari</button>
        <?php if ($search !== ''): ?>
          <a href="barang.php" class="btn btn--ghost-dark btn--sm">Reset</a>
        <?php endif; ?>
      </div>
    </form>

    <?php if ($items === []): ?>
      <div class="empty-state">
        <span class="empty-state__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.27 6.96L12 12l8.73-5.04M12 22.08V12"/></svg>
        </span>
        <p><?= $search !== '' ? 'Barang tidak ditemukan.' : 'Belum ada data barang.' ?></p>
        <small><?= $search !== '' ? 'Coba kata kunci lain.' : 'Tambahkan barang melalui formulir di samping.' ?></small>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Kode</th>
              <th>Nama Barang</th>
              <th>Satuan</th>
              <th class="text-right">Stok</th>
              <th class="text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr class="<?= $editId === (int) $item['id'] ? 'is-editing' : '' ?>">
                <td><code class="code-chip<?= decryption_unlocked() ? '' : ' text-garbled' ?>"><?= e($item['kode_tampil']) ?></code></td>
                <td><strong class="table-primary<?= decryption_unlocked() ? '' : ' text-garbled' ?>"><?= e($item['nama_tampil']) ?></strong></td>
                <td class="<?= decryption_unlocked() ? '' : ' text-garbled' ?>"><?= e($item['satuan_tampil']) ?></td>
                <td class="text-right text-nowrap">
                  <?php if ((int) $item['stok'] === 0): ?>
                    <span class="badge badge--empty">Habis</span>
                  <?php else: ?>
                    <strong><?= number_format((int) $item['stok'], 0, ',', '.') ?></strong>
                  <?php endif; ?>
                </td>
                <td class="text-right text-nowrap">
                  <div class="table-actions">
                    <a href="barang.php?<?= e(http_build_query(array_filter(['edit' => $item['id'], 'q' => $search !== '' ? $search : null]))) ?>"
                       class="btn btn--outline btn--xs">Edit</a>
                    <form method="post" action="barang.php" class="inline-form"
                          data-confirm="Hapus barang &quot;<?= e($item['nama_tampil']) ?>&quot;? Tindakan ini tidak dapat dibatalkan.">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                      <button type="submit" class="btn btn--danger btn--xs">Hapus</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
