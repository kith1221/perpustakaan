-- Create the database
CREATE DATABASE IF NOT EXISTS perpustakaan_db;
USE perpustakaan_db;

-- Create kategori_buku table
CREATE TABLE IF NOT EXISTS kategori_buku (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(50) NOT NULL UNIQUE,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create admin table
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create siswa table (for students)
CREATE TABLE IF NOT EXISTS siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nisn VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    kelas VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create buku table (for books)
CREATE TABLE IF NOT EXISTS buku (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    pengarang VARCHAR(100) NOT NULL,
    penerbit VARCHAR(100) NOT NULL,
    tahun_terbit YEAR NOT NULL,
    isbn VARCHAR(20),
    jumlah_buku INT NOT NULL DEFAULT 1,
    kategori_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori_buku(id)
);

-- Create peminjaman table (for loans)
CREATE TABLE IF NOT EXISTS peminjaman (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL,
    buku_id INT NOT NULL,
    tanggal_pinjam DATE NOT NULL,
    lama_pinjam INT NOT NULL DEFAULT 7, -- Duration in days
    tanggal_kembali DATE,
    status ENUM('dipinjam', 'dikembalikan') NOT NULL DEFAULT 'dipinjam',
    keterlambatan INT DEFAULT 0, -- Days of lateness
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
    FOREIGN KEY (buku_id) REFERENCES buku(id) ON DELETE CASCADE
);

-- Create activity_log table (for tracking actions)
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    activity_type VARCHAR(50) NOT NULL,
    details TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES admin(id) ON DELETE SET NULL
);

-- Insert default admin account
INSERT INTO admin (username, password, nama) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator'); -- Password: 'password'

-- Insert default book categories
INSERT INTO kategori_buku (nama_kategori, deskripsi) VALUES 
('Umum', 'Buku-buku umum untuk semua kalangan'),
('Pendidikan', 'Buku-buku terkait pendidikan dan pembelajaran'),
('Agama', 'Buku-buku terkait keagamaan dan spiritual');

-- Insert sample data for testing (optional)
-- Insert sample students
INSERT INTO siswa (nisn, password, nama, kelas) VALUES 
('1001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ahmad', '1'), -- Password: 'password'
('1002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Budi', '2'), -- Password: 'password'
('1003', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Citra', '3'), -- Password: 'password'
('1004', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dina', '4'), -- Password: 'password'
('1005', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Eko', '5'), -- Password: 'password'
('1006', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Fira', '6'); -- Password: 'password'

-- Modify sample books to include categories
INSERT INTO buku (judul, pengarang, penerbit, tahun_terbit, isbn, jumlah_buku, kategori_id) VALUES 
('Matematika Kelas 1', 'Dr. Susilo', 'Penerbit Pendidikan', 2022, '978-1234567890', 5, 2),
('Bahasa Indonesia Kelas 2', 'Dra. Wati', 'Penerbit Pendidikan', 2022, '978-1234567891', 5, 2),
('IPA Kelas 3', 'Prof. Handoko', 'Penerbit Sains', 2022, '978-1234567892', 5, 2),
('IPS Kelas 4', 'Dr. Budiman', 'Penerbit Sosial', 2022, '978-1234567893', 5, 2),
('Akidah Akhlak Kelas 5', 'Ust. Abdullah', 'Penerbit Islam', 2022, '978-1234567894', 5, 3),
('Al-Quran Hadits Kelas 6', 'Ust. Ibrahim', 'Penerbit Islam', 2022, '978-1234567895', 5, 3);

-- Insert sample loans
INSERT INTO peminjaman (siswa_id, buku_id, tanggal_pinjam, lama_pinjam, status) VALUES 
(1, 1, CURDATE() - INTERVAL 5 DAY, 7, 'dipinjam'),
(2, 2, CURDATE() - INTERVAL 10 DAY, 7, 'dikembalikan'),
(3, 3, CURDATE() - INTERVAL 3 DAY, 7, 'dipinjam'),
(4, 4, CURDATE() - INTERVAL 15 DAY, 7, 'dikembalikan'),
(5, 5, CURDATE() - INTERVAL 2 DAY, 7, 'dipinjam'),
(6, 6, CURDATE() - INTERVAL 1 DAY, 7, 'dipinjam');

-- Set tanggal_kembali for returned books
UPDATE peminjaman SET tanggal_kembali = tanggal_pinjam + INTERVAL lama_pinjam DAY WHERE status = 'dikembalikan'; 

-- Add keterlambatan column if not exists
ALTER TABLE peminjaman ADD COLUMN IF NOT EXISTS keterlambatan INT DEFAULT 0 COMMENT 'Days of lateness';

-- Update keterlambatan for existing returned books
UPDATE peminjaman 
SET keterlambatan = DATEDIFF(tanggal_kembali, tanggal_pinjam + INTERVAL lama_pinjam DAY)
WHERE status = 'dikembalikan' AND DATEDIFF(tanggal_kembali, tanggal_pinjam + INTERVAL lama_pinjam DAY) > 0; 