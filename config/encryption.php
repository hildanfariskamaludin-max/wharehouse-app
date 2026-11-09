<?php
/**
 * Enkripsi data sensitif — WAREHOUSE SYSTEM (demo UTS Keamanan Data)
 *
 * Skema:
 *   - AES-256-CTR (tanpa authentication tag, hanya untuk demonstrasi).
 *   - Kunci 32 byte disimpan di file config/app.key (tidak diakses browser).
 *   - Data terenkripsi tetap tersimpan sebagai:
 *         enc:v1:<base64(iv + ciphertext)>
 *   - Login admin menentukan tampilan: password benar menampilkan data asli,
 *     password salah tetap dapat membuka aplikasi tetapi isi data ditampilkan acak.
 *
 * =========================================================================
 *  PANDUAN PRESENTASI — ALUR ENKRIPSI / DEKRIPSI DI FILE INI
 * =========================================================================
 *  [1] KUNCI          -> app_data_key() ............ kunci AES 32 byte
 *  [2] GATE LOGIN     -> is_app_authenticated(),
 *                        decryption_unlocked() ..... gerbang tampil/simpan
 *  [3] ENKRIPSI       -> encrypt_value_with_key(),
 *                        encrypt_sensitive() ....... sebelum disimpan DB
 *  [4] DEKRIPSI       -> decrypt_value_with_key(),
 *                        decrypt_sensitive() ....... sebelum tampil di admin
 * =========================================================================
 *
 * Keterangan tambahan:
 *   - Password login BUKAN kunci AES. Password di-hash bcrypt (password_hash)
 *     dan dicek via password_verify() di admin/login.php; yang dibuka gate
 *     di atas adalah flag sessi app_authenticated.
 *   - Kunci AES tidak diturunkan dari password; diambil dari config/app.key.
 *   - Field yang dienkripsi: barang.kode_barang, barang.nama_barang,
 *     barang.satuan, transaksi.keterangan.
 *   - Field stok/jumlah/tanggal/jenis tetap plaintext (butuh query/hitung).
 *
 * PENTING:
 *   Ini adalah model demonstrasi. Kunci aplikasi adalah rahasia server;
 *   pembatasan tampilan tidak menggantikan kontrol akses yang benar.
 */

declare(strict_types=1);

const ENCRYPTION_CIPHER = 'aes-256-ctr';
const ENCRYPTION_PREFIX = 'enc:v1:';

/**
 * =====================================================================
 * [1] KUNCI — Kunci AES 32 byte dari config/app.key (base64)
 * =====================================================================
 * Kunci ini dipakai untuk ENKRIPSI dan DEKRIPSI (simetris).
 */
function app_data_key(): string
{
    static $key = null;

    if ($key !== null) {
        return $key;
    }

    $path = __DIR__ . '/app.key';
    if (!is_file($path)) {
        throw new RuntimeException(
            'Kunci aplikasi belum tersedia. Jalankan: php database/setup_encryption.php'
        );
    }

    $raw = trim((string) file_get_contents($path));
    $key = base64_decode($raw, true);

    if (!is_string($key) || strlen($key) !== 32) {
        throw new RuntimeException('File config/app.key tidak valid.');
    }

    return $key;
}

/**
 * =====================================================================
 * [2] GATE LOGIN — Apakah password sudah benar?
 * =====================================================================
 * Diatur di admin/login.php setelah password_verify() lolos.
 * Selama gate MATI: tampil acak + simpan ditolak.
 * Selama gate NYALA: tampil asli + boleh simpan (terenkripsi).
 */

/**
 * Apakah sesi saat ini milik admin yang password-nya benar?
 */
function is_app_authenticated(): bool
{
    return ($_SESSION['app_authenticated'] ?? false) === true;
}

/**
 * Kompatibilitas dengan kode tampilan/aksi.
 */
function decryption_unlocked(): bool
{
    return is_app_authenticated();
}

/**
 * Apakah nilai sudah dalam format terenkripsi?
 */
function is_encrypted_value(string $value): bool
{
    return str_starts_with($value, ENCRYPTION_PREFIX);
}

/**
 * =====================================================================
 * [3] ENKRIPSI — plaintext -> ciphertext (sebelum disimpan ke database)
 * =====================================================================
 * Alur: plaintext + kunci AES + IV acak -> AES-256-CTR ->
 *       "enc:v1:" + base64(IV + ciphertext)
 */
function encrypt_value_with_key(string $plaintext, string $key): string
{
    if ($plaintext === '') {
        return '';
    }

    if (strlen($key) !== 32) {
        throw new RuntimeException('Kunci enkripsi harus 32 byte.');
    }

    $iv     = random_bytes(16);
    $cipher = openssl_encrypt($plaintext, ENCRYPTION_CIPHER, $key, OPENSSL_RAW_DATA, $iv);

    if ($cipher === false) {
        throw new RuntimeException('Proses enkripsi gagal.');
    }

    return ENCRYPTION_PREFIX . base64_encode($iv . $cipher);
}

/**
 * Enkripsi nilai sebelum disimpan ke database.
 * Hanya boleh dipanggil saat admin terautentikasi.
 *
 * @throws RuntimeException bila admin belum terautentikasi.
 */
function encrypt_sensitive(?string $plaintext): ?string
{
    if ($plaintext === null || $plaintext === '') {
        return $plaintext;
    }

    if (is_encrypted_value($plaintext)) {
        return $plaintext;
    }

    if (!is_app_authenticated()) {
        throw new RuntimeException('Password belum benar; data tidak disimpan.');
    }

    return encrypt_value_with_key($plaintext, app_data_key());
}

/**
 * =====================================================================
 * [4] DEKRIPSI — ciphertext -> plaintext (sebelum tampil di halaman admin)
 * =====================================================================
 * Alur: "enc:v1:" + base64(IV + ciphertext) -> ciphertext + IV ->
 *       AES-256-CTR decrypt dengan kunci yang sama -> plaintext asli.
 */
function decrypt_value_with_key(string $stored, string $key): string|false
{
    if (!is_encrypted_value($stored)) {
        return $stored;
    }

    if (strlen($key) !== 32) {
        return false;
    }

    $payload = base64_decode(substr($stored, strlen(ENCRYPTION_PREFIX)), true);
    if (!is_string($payload) || strlen($payload) <= 16) {
        return false;
    }

    $iv     = substr($payload, 0, 16);
    $cipher = substr($payload, 16);

    return openssl_decrypt($cipher, ENCRYPTION_CIPHER, $key, OPENSSL_RAW_DATA, $iv);
}

/**
 * Dekripsi nilai untuk ditampilkan.
 *
 * - Password salah/kosong -> karakter acak
 * - Password benar        -> teks asli
 * - Data lama (teks)      -> ditampilkan apa adanya
 */
function decrypt_sensitive(?string $stored): string
{
    if ($stored === null || $stored === '') {
        return '';
    }

    if (!is_encrypted_value($stored)) {
        return $stored;
    }

    $key = app_data_key();

    if (!is_app_authenticated()) {
        // Data hanya ditampilkan sebagai hasil acak yang tidak dapat dibaca.
        return substr(base64_encode(hash_hmac('sha256', $stored, $key, true)), 0, 32);
    }

    $plain = decrypt_value_with_key($stored, $key);

    return $plain === false ? '⛔ Data tidak dapat dibaca' : $plain;
}
