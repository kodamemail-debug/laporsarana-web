# LaporSarana — SMK Negeri 5 Surakarta

Sistem pengaduan sarana sekolah (Laravel). Peran: Pelapor (siswa/guru), Admin (Kepala Sarpras), Teknisi.

## Pasang di XAMPP

1. Jalankan **Apache** dan **MySQL** di XAMPP Control Panel.
2. Buka `http://localhost/phpmyadmin` → tab **Import** → pilih `database/sql/laporsarana.sql` → **Import**.
   (Database `laporsarana` dibuat otomatis beserta akun awal.)
3. Salin `.env.example` menjadi `.env`, lalu di terminal pada folder projek:
   ```
   composer install
   php artisan key:generate
   php artisan storage:link
   php artisan serve
   ```
   Buka `http://localhost:8000`.

> Alternatif tanpa import SQL: buat database kosong `laporsarana`, lalu `php artisan migrate --seed`.
> Pilih salah satu cara saja, jangan keduanya.

## Akun awal

| Peran   | Email                    | Password     |
|---------|--------------------------|--------------|
| Admin   | admin@laporsarana.test   | Admin12345   |
| Teknisi | budi@laporsarana.test    | Teknisi12345 |
| Teknisi | agus@laporsarana.test    | Teknisi12345 |
| Teknisi | rina@laporsarana.test    | Teknisi12345 |

Ganti password lewat menu Profil. Akun pelapor dibuat lewat halaman Daftar:
siswa memakai **NIS**, guru memakai **NIP**, dan nomornya harus ada di menu
Admin → Data Pengguna (contoh data sudah disertakan; bisa import CSV).

## Catatan fitur

- **Rekap Laporan** (admin): grafik batang/garis, waktu lengkap (jam WIB), unduh CSV, dan **Cetak / PDF**.
  Tombol Cetak / PDF membuka halaman rapi; di jendela cetak pilih **Simpan sebagai PDF**.
- **Lokasi pengaduan** berupa dropdown. Daftarnya ada di `app/Models/Pengaduan.php` (konstanta `LOKASI`), silakan sesuaikan nama ruang.
- **Logo dan favicon** ada di `public/img/` dan `public/favicon.ico`.
