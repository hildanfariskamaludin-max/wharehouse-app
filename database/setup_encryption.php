<?php
/**
 * Setup enkripsi data — WAREHOUSE SYSTEM
 *
 * Jalankan melalui terminal:
 *     php database/setup_encryption.php
 *
 * Script ini:
 *   - memastikan config/app.key tersedia (32 byte, base64),
 *   - mengenkripsi nilai plaintext lama pada kolom:
 *         barang.kode_barang
 *         barang.nama_barang
 *         barang.satuan
 *         transaksi.keterangan
 *     tanpa menghapus atau mengubah data lain.
 *
 * Kunci aplikasi tidak diturunkan dari password login; kunci disimpan di
 * config/app.key yang diblokir dari akses langsung melalui web.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/encryption.php';

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

$appKeyPath = __DIR__ . '/../config/app.key';

if (!is_file($appKeyPath)) {
    $key = random_bytes(32);
    if (file_put_contents($appKeyPath, base64_encode($key) . PHP_EOL, LOCK_EX) === false) {
        fail('Tidak dapat membuat config/app.key.');
    }

    out('[OK] Kunci aplikasi dibuat: config/app.key');
} else {
    out('[OK] Kunci aplikasi sudah ada.');
}

$appKey = app_data_key();
$pdo    = db();

$kodeCount       = 0;
$barangCount     = 0;
$satuanCount     = 0;
$keteranganCount = 0;

$pdo->beginTransaction();

try {
    $items = $pdo->query('SELECT id, kode_barang, nama_barang, satuan FROM barang')->fetchAll();
    $updateBarang = $pdo->prepare(
        'UPDATE barang
         SET kode_barang = :kode, nama_barang = :nama, satuan = :satuan
         WHERE id = :id'
    );

    foreach ($items as $item) {
        $id         = (int) $item['id'];
        $origKode   = (string) $item['kode_barang'];
        $origNama   = (string) $item['nama_barang'];
        $origSatuan = (string) $item['satuan'];

        $kode   = ($origKode === '' || is_encrypted_value($origKode))
            ? $origKode
            : encrypt_value_with_key($origKode, $appKey);
        $nama   = ($origNama === '' || is_encrypted_value($origNama))
            ? $origNama
            : encrypt_value_with_key($origNama, $appKey);
        $satuan = ($origSatuan === '' || is_encrypted_value($origSatuan))
            ? $origSatuan
            : encrypt_value_with_key($origSatuan, $appKey);

        $kodeChanged   = $kode !== $origKode;
        $namaChanged   = $nama !== $origNama;
        $satuanChanged = $satuan !== $origSatuan;

        if (!$kodeChanged && !$namaChanged && !$satuanChanged) {
            continue;
        }

        $updateBarang->execute([
            ':kode'   => $kode,
            ':nama'   => $nama,
            ':satuan' => $satuan,
            ':id'     => $id,
        ]);

        $kodeCount   += $kodeChanged ? 1 : 0;
        $barangCount += $namaChanged ? 1 : 0;
        $satuanCount += $satuanChanged ? 1 : 0;
    }

    $transaksi = $pdo->query(
        'SELECT id, keterangan FROM transaksi WHERE keterangan IS NOT NULL AND keterangan <> \'\''
    )->fetchAll();
    $updateTransaksi = $pdo->prepare('UPDATE transaksi SET keterangan = :keterangan WHERE id = :id');

    foreach ($transaksi as $row) {
        $stored = (string) $row['keterangan'];

        if (is_encrypted_value($stored)) {
            $keterangan = $stored;
        } else {
            $keterangan = encrypt_value_with_key($stored, $appKey);
            $keteranganCount++;
        }

        if ($keterangan !== $stored) {
            $updateTransaksi->execute([
                ':keterangan' => $keterangan,
                ':id'         => (int) $row['id'],
            ]);
        }
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fail('Migrasi enkripsi gagal: ' . $e->getMessage());
}

out('[OK] Kode barang dienkripsi  : ' . $kodeCount);
out('[OK] Nama barang dienkripsi  : ' . $barangCount);
out('[OK] Satuan dienkripsi       : ' . $satuanCount);
out('[OK] Keterangan dienkripsi   : ' . $keteranganCount);
out('[SELESAI] Data lama tetap aman dan tidak dihapus.');