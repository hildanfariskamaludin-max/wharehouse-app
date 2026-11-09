<?php
/**
 * Helper pencatatan transaksi barang masuk/keluar.
 *
 * Semua perubahan stok dilakukan dalam satu database transaction
 * dengan SELECT ... FOR UPDATE agar stok tidak bentrok saat diakses bersamaan.
 */

declare(strict_types=1);

require_once __DIR__ . '/encryption.php';

/**
 * Exception bisnis transaksi (pesan aman untuk ditampilkan ke pengguna).
 * Dipisahkan dari PDOException agar detail database tidak bocor ke UI.
 */
class TransaksiException extends RuntimeException
{
}

/**
 * @param 'masuk'|'keluar' $jenis
 *
 * @throws TransaksiException bila barang tidak ada atau stok tidak mencukupi.
 */
function simpan_transaksi(
    PDO $pdo,
    int $barangId,
    string $jenis,
    int $jumlah,
    string $tanggal,
    ?string $keterangan
): void {
    if (!in_array($jenis, ['masuk', 'keluar'], true)) {
        throw new InvalidArgumentException('Jenis transaksi tidak valid.');
    }

    if ($jumlah < 1) {
        throw new InvalidArgumentException('Jumlah transaksi harus lebih dari 0.');
    }

    if (!decryption_unlocked()) {
        throw new TransaksiException('Transaksi tidak dapat disimpan. Silakan coba lagi.');
    }

    // ★ ENKRIPSI (presentasi): keterangan plaintext -> ciphertext sebelum simpan DB.
    $keterangan = $keterangan !== null && $keterangan !== ''
        ? encrypt_sensitive($keterangan)
        : null;

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'SELECT id, kode_barang, nama_barang, stok
             FROM barang
             WHERE id = :id
             FOR UPDATE'
        );
        $stmt->execute([':id' => $barangId]);
        $barang = $stmt->fetch();

        if (!$barang) {
            throw new TransaksiException('Barang tidak ditemukan.');
        }

        $stokSaatIni = (int) $barang['stok'];

        if ($jenis === 'keluar' && $jumlah > $stokSaatIni) {
            throw new TransaksiException(
                'Stok tidak mencukupi. Stok tersedia: '
                . number_format($stokSaatIni, 0, ',', '.') . '.'
            );
        }

        $stmt = $pdo->prepare(
            'INSERT INTO transaksi (barang_id, jenis, jumlah, tanggal, keterangan)
             VALUES (:barang_id, :jenis, :jumlah, :tanggal, :keterangan)'
        );
        $stmt->execute([
            ':barang_id'  => $barangId,
            ':jenis'      => $jenis,
            ':jumlah'     => $jumlah,
            ':tanggal'    => $tanggal,
            ':keterangan' => $keterangan,
        ]);

        $stokBaru = $jenis === 'masuk'
            ? $stokSaatIni + $jumlah
            : $stokSaatIni - $jumlah;

        $stmt = $pdo->prepare('UPDATE barang SET stok = :stok WHERE id = :id');
        $stmt->execute([
            ':stok' => $stokBaru,
            ':id'   => $barangId,
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}
