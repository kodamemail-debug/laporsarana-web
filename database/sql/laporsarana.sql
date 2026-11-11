-- ============================================================
-- LaporSarana — database baru untuk XAMPP (MySQL / MariaDB)
-- SMK Negeri 5 Surakarta
--
-- Cara pakai (phpMyAdmin):
--   1. Buka http://localhost/phpmyadmin
--   2. Klik tab "Import", pilih berkas ini, klik "Import".
--   Database `laporsarana` dibuat otomatis. Bila sudah ada, isinya
--   akan DIHAPUS dan dibuat ulang.
--
-- Akun awal:
--   Admin   : admin@laporsarana.test  / Admin12345
--   Teknisi : budi@ / agus@ / rina@laporsarana.test  / Teknisi12345
--   (segera ganti password setelah login pertama)
-- ============================================================

CREATE DATABASE IF NOT EXISTS `laporsarana`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `laporsarana`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `notifikasi`, `pesan`, `riwayat_pengaduan`, `penugasan`, `pengaduan`,
  `identitas_sekolah`, `sessions`, `password_reset_tokens`, `users`,
  `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `migrations`;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- Pengguna: admin, teknisi, dan pelapor (siswa/guru)
-- ------------------------------------------------------------
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Teknisi','Pelapor') NOT NULL DEFAULT 'Pelapor',
  `jenis_pengguna` enum('Siswa','Guru') DEFAULT NULL,
  `nomor_identitas` varchar(50) DEFAULT NULL COMMENT 'NIS (siswa) atau NIP (guru)',
  `kelas` varchar(30) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `keahlian` varchar(100) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_nomor_identitas_unique` (`nomor_identitas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `payload` longtext NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Data master siswa (NIS) dan guru (NIP) yang boleh mendaftar
-- ------------------------------------------------------------
CREATE TABLE `identitas_sekolah` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nomor_identitas` varchar(50) NOT NULL,
  `nama_lengkap` varchar(255) NOT NULL,
  `jenis_pengguna` enum('Siswa','Guru') NOT NULL,
  `kelas` varchar(30) DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `identitas_sekolah_nomor_identitas_unique` (`nomor_identitas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Pengaduan
-- ------------------------------------------------------------
CREATE TABLE `pengaduan` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kode_tiket` varchar(30) DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `judul` varchar(255) NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `lokasi` varchar(255) NOT NULL,
  `deskripsi` text NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `status` enum('Menunggu','Diverifikasi','Dikerjakan','Selesai','Ditolak') NOT NULL DEFAULT 'Menunggu',
  `catatan_admin` text,
  `diverifikasi_pada` timestamp NULL DEFAULT NULL,
  `tanggal_selesai` timestamp NULL DEFAULT NULL,
  `rating` tinyint unsigned DEFAULT NULL,
  `komentar_feedback` text,
  `feedback_pada` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pengaduan_kode_tiket_unique` (`kode_tiket`),
  KEY `pengaduan_status_index` (`status`),
  KEY `pengaduan_kategori_index` (`kategori`),
  CONSTRAINT `pengaduan_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Satu pengaduan boleh ditangani beberapa teknisi
CREATE TABLE `penugasan` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pengaduan_id` bigint unsigned NOT NULL,
  `teknisi_id` bigint unsigned NOT NULL,
  `ditugaskan_oleh` bigint unsigned DEFAULT NULL,
  `status` enum('Ditugaskan','Dikerjakan','Selesai') NOT NULL DEFAULT 'Ditugaskan',
  `catatan_tindakan` text,
  `foto_hasil` varchar(255) DEFAULT NULL,
  `mulai_pada` timestamp NULL DEFAULT NULL,
  `selesai_pada` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `penugasan_pengaduan_id_teknisi_id_unique` (`pengaduan_id`,`teknisi_id`),
  KEY `penugasan_teknisi_id_foreign` (`teknisi_id`),
  KEY `penugasan_ditugaskan_oleh_foreign` (`ditugaskan_oleh`),
  CONSTRAINT `penugasan_pengaduan_id_foreign` FOREIGN KEY (`pengaduan_id`) REFERENCES `pengaduan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `penugasan_teknisi_id_foreign` FOREIGN KEY (`teknisi_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `penugasan_ditugaskan_oleh_foreign` FOREIGN KEY (`ditugaskan_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `riwayat_pengaduan` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pengaduan_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `judul` varchar(150) NOT NULL,
  `keterangan` text,
  `status` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `riwayat_pengaduan_pengaduan_id_foreign` (`pengaduan_id`),
  KEY `riwayat_pengaduan_user_id_foreign` (`user_id`),
  CONSTRAINT `riwayat_pengaduan_pengaduan_id_foreign` FOREIGN KEY (`pengaduan_id`) REFERENCES `pengaduan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `riwayat_pengaduan_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat antara admin dan pelapor, satu percakapan per pengaduan
CREATE TABLE `pesan` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pengaduan_id` bigint unsigned NOT NULL,
  `pengirim_id` bigint unsigned NOT NULL,
  `isi` text NOT NULL,
  `dibaca_pada` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pesan_pengaduan_id_id_index` (`pengaduan_id`,`id`),
  KEY `pesan_pengirim_id_foreign` (`pengirim_id`),
  CONSTRAINT `pesan_pengaduan_id_foreign` FOREIGN KEY (`pengaduan_id`) REFERENCES `pengaduan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pesan_pengirim_id_foreign` FOREIGN KEY (`pengirim_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notifikasi` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `pengaduan_id` bigint unsigned DEFAULT NULL,
  `jenis` varchar(20) NOT NULL DEFAULT 'status',
  `judul` varchar(255) NOT NULL,
  `pesan` text NOT NULL,
  `dibaca_pada` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifikasi_user_id_foreign` (`user_id`),
  KEY `notifikasi_pengaduan_id_foreign` (`pengaduan_id`),
  CONSTRAINT `notifikasi_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifikasi_pengaduan_id_foreign` FOREIGN KEY (`pengaduan_id`) REFERENCES `pengaduan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabel bawaan Laravel (cache & antrean), disertakan agar tidak
-- error walau .env memakai driver "database".
-- ------------------------------------------------------------
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Data awal
-- ------------------------------------------------------------
INSERT INTO `users` (`name`,`email`,`password`,`role`,`keahlian`,`aktif`,`created_at`,`updated_at`) VALUES
('Kepala Sarpras','admin@laporsarana.test','$2y$12$8mZBVwjyTjxS2DwALFcyfOGKPTd1KLJ/VlmLyjkPVG.aSrGOd1Ffm','Admin',NULL,1,NOW(),NOW()),
('Budi Santoso','budi@laporsarana.test','$2y$12$bOCFIkxalVZ/mYnh67YgSO3C/6EUfRXtG.cLa1TOjkm1LSJcLkdn6','Teknisi','Listrik & Penerangan',1,NOW(),NOW()),
('Agus Prasetyo','agus@laporsarana.test','$2y$12$bOCFIkxalVZ/mYnh67YgSO3C/6EUfRXtG.cLa1TOjkm1LSJcLkdn6','Teknisi','Bangunan, Air & Sanitasi',1,NOW(),NOW()),
('Rina Wulandari','rina@laporsarana.test','$2y$12$bOCFIkxalVZ/mYnh67YgSO3C/6EUfRXtG.cLa1TOjkm1LSJcLkdn6','Teknisi','Perangkat Elektronik & Jaringan',1,NOW(),NOW());

-- Data contoh. Ganti dengan NIS siswa dan NIP guru yang sebenarnya
-- lewat menu Admin > Data Pengguna (bisa import CSV).
INSERT INTO `identitas_sekolah` (`nomor_identitas`,`nama_lengkap`,`jenis_pengguna`,`kelas`,`aktif`,`created_at`,`updated_at`) VALUES
('2526001','Ahmad Fauzi','Siswa','XII RPL C',1,NOW(),NOW()),
('2526002','Siti Nurhaliza','Siswa','XI TKJ A',1,NOW(),NOW()),
('2526003','Rizky Pratama','Siswa','X DKV 1',1,NOW(),NOW()),
('198503122010012005','Sri Wahyuni, S.Pd.','Guru',NULL,1,NOW(),NOW()),
('197911222005011003','Bambang Hermawan, S.Kom.','Guru',NULL,1,NOW(),NOW());
