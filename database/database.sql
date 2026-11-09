-- ============================================================
--  WAREHOUSE SYSTEM — Skema Database
--  Sistem Informasi Manajemen Gudang
--
--  Cara impor:
--    1. Buka phpMyAdmin / HeidiSQL (Laragon)
--    2. Jalankan seluruh isi file ini
--    3. Buat akun admin pertama lewat terminal:
--         php database/create_admin.php
--    4. Siapkan kunci aplikasi dan enkripsi data lama:
--         php database/setup_encryption.php
--
--  CATATAN KEAMANAN:
--  File ini TIDAK berisi akun admin dengan password asli.
--  Password admin wajib dibuat lewat create_admin.php agar
--  di-hash menggunakan password_hash() sebelum disimpan.
-- ============================================================

CREATE DATABASE IF NOT EXISTS `warehouse_system`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `warehouse_system`;

CREATE TABLE IF NOT EXISTS `kunci_dekripsi` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama`          VARCHAR(50)  NOT NULL DEFAULT 'Kunci Utama',
  `password_hash` VARCHAR(255) NOT NULL,
  `salt`          VARCHAR(64)  NOT NULL,
  `iterations`    INT UNSIGNED NOT NULL DEFAULT 120000,
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kunci_dekripsi_nama` (`nama`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(50)  NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_username` (`username`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `barang` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kode_barang`  VARCHAR(100) NOT NULL,
  `nama_barang`  VARCHAR(255) NOT NULL,
  `satuan`       VARCHAR(100) NOT NULL DEFAULT 'pcs',
  `stok`         INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
  -- Keunikan kode barang dicek di aplikasi karena kode tersimpan terenkripsi.
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `transaksi` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `barang_id`  INT UNSIGNED NOT NULL,
  `jenis`      ENUM('masuk','keluar') NOT NULL,
  `jumlah`     INT UNSIGNED NOT NULL,
  `tanggal`    DATE         NOT NULL,
  `keterangan` VARCHAR(512) NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_transaksi_tanggal` (`tanggal`),
  KEY `idx_transaksi_jenis` (`jenis`),
  KEY `idx_transaksi_barang` (`barang_id`),
  CONSTRAINT `fk_transaksi_barang`
    FOREIGN KEY (`barang_id`) REFERENCES `barang` (`id`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Akun admin tidak dibuat di sini (lihat catatan di atas).
