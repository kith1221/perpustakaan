# Sistem Informasi Perpustakaan Madrasah Nurul Falah

Sistem ini dirancang untuk mempermudah proses peminjaman buku oleh siswa serta pengelolaan data peminjaman oleh admin perpustakaan di Madrasah Nurul Falah.

## Fitur Sistem

### Fitur Admin
- Login dan logout akun
- Input data peminjaman siswa (nama siswa, buku, tanggal pinjam, lama pinjaman)
- Mengelola data buku (tambah, edit, hapus)
- Mengelola data siswa (tambah, edit, hapus)
- Melihat dan mengubah status peminjaman (dipinjam/dikembalikan)
- Dashboard dengan statistik peminjaman

### Fitur Siswa
- Login dan logout akun
- Melihat daftar buku yang sedang dipinjam
- Melihat riwayat peminjaman buku
- Melihat status dan tenggat waktu pengembalian

## Teknologi yang Digunakan

- Frontend: HTML, CSS
- Backend: PHP
- Database: MySQL

## Persyaratan Sistem

- PHP 7.0 atau lebih tinggi
- MySQL 5.6 atau lebih tinggi
- Web server (Apache/Nginx)

## Instalasi

1. **Siapkan server web lokal**
   - Install XAMPP, WAMP, atau server web lainnya
   - Pastikan PHP dan MySQL sudah terpasang dan berjalan

2. **Clone/Download Repository**
   - Letakkan folder perpustakaan ke dalam direktori htdocs (untuk XAMPP) atau www (untuk WAMP)

3. **Buat Database**
   - Buka phpMyAdmin (http://localhost/phpmyadmin)
   - Buat database baru dengan nama `perpustakaan_db`
   - Import file `database.sql` ke dalam database yang baru dibuat

4. **Konfigurasi Database**
   - Buka file `includes/config.php`
   - Sesuaikan pengaturan database jika diperlukan (username, password, nama database)

5. **Akses Aplikasi**
   - Buka browser dan akses: http://localhost/perpustakaan

## Akun Default

### Admin
- Username: admin
- Password: password

### Siswa
- NISN: 1001 (untuk siswa kelas 1)
- NISN: 1002 (untuk siswa kelas 2)
- NISN: 1003 (untuk siswa kelas 3)
- NISN: 1004 (untuk siswa kelas 4)
- NISN: 1005 (untuk siswa kelas 5)
- NISN: 1006 (untuk siswa kelas 6)
- Password: password (sama untuk semua siswa)

## Catatan Penting

- Sistem ini khusus untuk Madrasah Nurul Falah kelas 1 sampai 6
- Pastikan semua field yang memiliki tanda (*) diisi saat menambah/mengedit data
- Jika ada kendala akses, periksa kembali konfigurasi database pada file config.php 