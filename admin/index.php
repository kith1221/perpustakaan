<?php
// Set page title
$pageTitle = 'Dashboard Admin';

// Include header
require_once 'includes/header.php';

// Get statistics from database
$stats = [
    'total_books' => 0,
    'total_students' => 0,
    'active_loans' => 0,
    'returned_books' => 0,
    'total_titles' => 0,
    'available_books' => 0
];

// Get total books and titles
$query = "SELECT COUNT(*) as total_titles, SUM(jumlah_buku) as total_copies FROM buku";
$result = mysqli_query($conn, $query);
if ($result) {
    $data = mysqli_fetch_assoc($result);
    $stats['total_books'] = $data['total_copies'] ?: 0;
    $stats['total_titles'] = $data['total_titles'] ?: 0;
}

// Get total students
$query = "SELECT COUNT(*) as total FROM siswa";
$result = mysqli_query($conn, $query);
if ($result) {
    $data = mysqli_fetch_assoc($result);
    $stats['total_students'] = $data['total'] ?: 0;
}

// Get active loans
$query = "SELECT COUNT(*) as total FROM peminjaman WHERE status = 'dipinjam'";
$result = mysqli_query($conn, $query);
if ($result) {
    $data = mysqli_fetch_assoc($result);
    $stats['active_loans'] = $data['total'] ?: 0;
}

// Calculate available books
$stats['available_books'] = $stats['total_books'] - $stats['active_loans'];

// Get returned books
$query = "SELECT COUNT(*) as total FROM peminjaman WHERE status = 'dikembalikan'";
$result = mysqli_query($conn, $query);
if ($result) {
    $data = mysqli_fetch_assoc($result);
    $stats['returned_books'] = $data['total'] ?: 0;
}

// Get books with low stock (less than 2 copies)
$query = "SELECT COUNT(*) as low_stock FROM buku WHERE jumlah_buku < 2";
$result = mysqli_query($conn, $query);
if ($result) {
    $data = mysqli_fetch_assoc($result);
    $stats['low_stock'] = $data['low_stock'] ?: 0;
}

// Get recent loans
$query = "SELECT p.*, s.nama as siswa_nama, s.kelas as siswa_kelas, b.judul as buku_judul 
          FROM peminjaman p 
          JOIN siswa s ON p.siswa_id = s.id 
          JOIN buku b ON p.buku_id = b.id 
          ORDER BY p.created_at DESC 
          LIMIT 5";
$result = mysqli_query($conn, $query);
$recentLoans = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $recentLoans[] = $row;
    }
}
?>

<!-- Welcome Message -->
<div class="card" style="margin-bottom: 20px;">
    <div style="display: flex; align-items: center; justify-content: space-between; padding: 15px;">
        <div style="display: flex; align-items: center;">
            <h2 style="margin: 0;">Selamat Datang, <?php echo htmlspecialchars($adminNama); ?> <span style="font-size: 0.8em; color: #666;">(Admin)</span></h2>
            <!-- Notification Bell -->
            <div class="notification-bell" id="notificationBell" style="margin-left: 15px; position: relative;">
                <i class="fas fa-bell" style="font-size: 1.5rem; color: #D4AF37;"></i>
                <?php if ($overdueCount > 0): ?>
                <span class="notification-badge" style="top: -8px; right: -8px; font-size: 0.7rem; min-width: 16px; height: 16px;"><?php echo $overdueCount; ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div style="text-align: right; font-size: 0.9em; color: #666;">
            <?php echo date('d F Y'); ?>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-container">
    <div class="stat-card">
        <div class="stat-value"><?php echo $stats['total_titles']; ?></div>
        <div class="stat-label">Judul Buku</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value"><?php echo $stats['total_books']; ?></div>
        <div class="stat-label">Jumlah Buku</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value"><?php echo $stats['active_loans']; ?></div>
        <div class="stat-label">Buku Dipinjam</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value"><?php echo $stats['available_books']; ?></div>
        <div class="stat-label">Buku Tersedia</div>
    </div>
</div>

<!-- Additional Stats -->
<div class="stats-container">
    <div class="stat-card">
        <div class="stat-value"><?php echo $stats['total_students']; ?></div>
        <div class="stat-label">Total Siswa</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value"><?php echo $stats['returned_books']; ?></div>
        <div class="stat-label">Buku Dikembalikan</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value"><?php echo $stats['low_stock']; ?></div>
        <div class="stat-label">Stok Menipis</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value"><?php echo $overdueCount; ?></div>
        <div class="stat-label">Keterlambatan</div>
    </div>
</div>

<!-- Recent Loans -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Peminjaman Terbaru</h2>
    </div>
    
    <?php if (empty($recentLoans)): ?>
        <p>Tidak ada data peminjaman.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Siswa</th>
                        <th>Kelas</th>
                        <th>Buku</th>
                        <th>Tanggal Pinjam</th>
                        <th>Status</th>
                        <th>Tanggal Kembali</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentLoans as $loan): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($loan['siswa_nama']); ?></td>
                            <td><?php echo htmlspecialchars($loan['siswa_kelas']); ?></td>
                            <td><?php echo htmlspecialchars($loan['buku_judul']); ?></td>
                            <td><?php echo formatDate($loan['tanggal_pinjam']); ?></td>
                            <td>
                                <?php if ($loan['status'] == 'dipinjam'): ?>
                                    <span style="color: #ff9800; font-weight: bold;">Dipinjam</span>
                                <?php else: ?>
                                    <span style="color: #4CAF50; font-weight: bold;">Dikembalikan</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    if (!empty($loan['tanggal_kembali'])) {
                                        echo formatDate($loan['tanggal_kembali']);
                                    } else {
                                        echo '-';
                                    }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    
    <div class="card-footer">
        <a href="peminjaman.php" class="btn btn-primary"><i class="fas fa-list"></i> Lihat Semua</a>
    </div>
</div>

<!-- Quick Links -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Akses Cepat</h2>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
        <a href="peminjaman_tambah.php" class="btn" style="text-align: center;">Tambah Peminjaman</a>
        <a href="buku_tambah.php" class="btn" style="text-align: center;">Tambah Buku</a>
        <a href="siswa_tambah.php" class="btn" style="text-align: center;">Tambah Siswa</a>
    </div>
</div>

<!-- Recent History -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Riwayat Peminjaman</h2>
    </div>
    
    <?php if (empty($recentLoans)): ?>
        <p>Tidak ada data peminjaman.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Siswa</th>
                        <th>Kelas</th>
                        <th>Buku</th>
                        <th>Tanggal Pinjam</th>
                        <th>Status</th>
                        <th>Tanggal Kembali</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentLoans as $loan): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($loan['siswa_nama']); ?></td>
                            <td><?php echo htmlspecialchars($loan['siswa_kelas']); ?></td>
                            <td><?php echo htmlspecialchars($loan['buku_judul']); ?></td>
                            <td><?php echo formatDate($loan['tanggal_pinjam']); ?></td>
                            <td>
                                <?php if ($loan['status'] == 'dipinjam'): ?>
                                    <span style="color: #ff9800; font-weight: bold;">Dipinjam</span>
                                <?php else: ?>
                                    <span style="color: #4CAF50; font-weight: bold;">Dikembalikan</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    if (!empty($loan['tanggal_kembali'])) {
                                        echo formatDate($loan['tanggal_kembali']);
                                    } else {
                                        echo '-';
                                    }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    
    <div class="card-footer">
        <a href="riwayat.php" class="btn btn-primary"><i class="fas fa-list"></i> Lihat Semua Riwayat</a>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?> 