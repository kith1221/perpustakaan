<?php
// Set page title
$pageTitle = 'Riwayat Peminjaman Buku';

// Include header
require_once 'includes/header.php';

// Get filter values from form submission or URL parameters
$month = isset($_GET['month']) && $_GET['month'] !== '' ? (int)$_GET['month'] : null;
$year = isset($_GET['year']) && $_GET['year'] !== '' ? (int)$_GET['year'] : null;

// Validate month value
if ($month !== null && (!is_int($month) || $month < 1 || $month > 12)) {
    $month = null;
}

// Get loan history with filters
$loanHistory = getAllLoanHistory($month, $year);

// Count statistics
$totalBooks = 0;
$returnedBooks = 0;
$activeBooks = 0;

// Get statistics based on filters
$query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'dikembalikan' THEN 1 ELSE 0 END) as returned,
    SUM(CASE WHEN status = 'dipinjam' THEN 1 ELSE 0 END) as active
    FROM peminjaman
    WHERE 1=1";

// Add month filter if selected
if ($month !== null) {
    $query .= " AND MONTH(tanggal_pinjam) = " . $month;
}

// Add year filter if selected
if ($year !== null) {
    $query .= " AND YEAR(tanggal_pinjam) = " . $year;
}

$result = mysqli_query($conn, $query);

if ($result) {
    $stats = mysqli_fetch_assoc($result);
    $totalBooks = $stats['total'] ?: 0;
    $returnedBooks = $stats['returned'] ?: 0;
    $activeBooks = $stats['active'] ?: 0;
}

// Generate month and year options for filter
$currentYear = date('Y');
$yearOptions = [];
// Include past 5 years and future 5 years
for ($i = $currentYear - 5; $i <= $currentYear + 5; $i++) {
    $yearOptions[] = $i;
}

$monthNames = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
?>

<!-- Loan History -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-history"></i> Riwayat Peminjaman Buku</h2>
    </div>
    
    <!-- Filter Form -->
    <div class="filter-container" style="padding: 1rem; background-color: #f9f9f9; border-radius: 4px; margin-bottom: 1rem;">
        <form action="" method="get" class="filter-form">
            <div class="filter-row" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
                <div class="filter-item">
                    <label for="month" style="display: block; margin-bottom: 5px; font-weight: 500;">Bulan:</label>
                    <select name="month" id="month" style="padding: 8px; border-radius: 4px; border: 1px solid #ddd; width: 100%;">
                        <option value="">Semua Bulan</option>
                        <?php foreach ($monthNames as $num => $name): ?>
                            <option value="<?php echo $num; ?>" <?php echo $month == $num ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label for="year" style="display: block; margin-bottom: 5px; font-weight: 500;">Tahun:</label>
                    <select name="year" id="year" style="padding: 8px; border-radius: 4px; border: 1px solid #ddd; width: 100%;">
                        <option value="">Semua Tahun</option>
                        <?php foreach ($yearOptions as $yearOption): ?>
                            <option value="<?php echo $yearOption; ?>" <?php echo $year == $yearOption ? 'selected' : ''; ?>>
                                <?php echo $yearOption; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="button-container">
                    <button type="submit" class="btn" style="margin-right: 5px;">Filter</button>
                    <a href="riwayat.php" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Filter info -->
    <?php if ($month !== null || $year !== null): ?>
        <div class="filter-info" style="padding: 0.5rem 1rem; background-color: #e9f7ef; border-radius: 4px; margin-bottom: 1rem;">
            <p style="margin: 0; font-size: 0.9rem;">
                <i class="fas fa-filter"></i> 
                Menampilkan hasil filter: 
                <?php 
                $filterText = '';
                if ($month !== null && $year !== null) {
                    $filterText = $monthNames[$month] . ' ' . $year;
                } elseif ($month !== null) {
                    $filterText = 'Bulan ' . $monthNames[$month];
                } elseif ($year !== null) {
                    $filterText = 'Tahun ' . $year;
                }
                echo $filterText;
                ?>
                (<?php echo count($loanHistory); ?> hasil)
            </p>
        </div>
    <?php endif; ?>
    
    <?php if (empty($loanHistory)): ?>
        <div style="padding: 30px; text-align: center;">
            <i class="fas fa-history" style="font-size: 3rem; color: #6c757d; margin-bottom: 15px; display: block;"></i>
            <p style="margin: 0; color: #6c757d;">
                <?php if ($month !== null || $year !== null): ?>
                    Tidak ada riwayat peminjaman untuk <?php echo strtolower($filterText); ?>.
                <?php else: ?>
                    Belum ada riwayat peminjaman buku.
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Judul Buku</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Tanggal Pinjam</th>
                        <th>Lama Peminjaman</th>
                        <th>Status</th>
                        <th>Tanggal Kembali</th>
                        <th>Keterlambatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loanHistory as $loan): ?>
                        <?php 
                            // Check if book is currently overdue but not yet returned
                            $is_currently_overdue = false;
                            if ($loan['status'] == 'dipinjam') {
                                $jatuh_tempo = date('Y-m-d', strtotime($loan['tanggal_pinjam'] . ' + ' . $loan['lama_pinjam'] . ' days'));
                                $today = date('Y-m-d');
                                $is_currently_overdue = ($today > $jatuh_tempo);
                            }
                        ?>
                        <tr <?php echo $is_currently_overdue ? 'style="background-color: #fff8e1;"' : ''; ?>>
                            <td><i class="fas fa-book text-primary"></i> <?php echo htmlspecialchars($loan['judul']); ?></td>
                            <td><i class="fas fa-user"></i> <?php echo htmlspecialchars($loan['nama_siswa']); ?></td>
                            <td><i class="fas fa-school"></i> <?php echo htmlspecialchars($loan['kelas']); ?></td>
                            <td><i class="fas fa-calendar"></i> <?php echo formatDate($loan['tanggal_pinjam']); ?></td>
                            <td><i class="fas fa-clock"></i> <?php echo $loan['lama_pinjam']; ?> hari</td>
                            <td>
                                <?php if ($loan['status'] == 'dikembalikan'): ?>
                                    <?php if (isset($loan['keterlambatan']) && $loan['keterlambatan'] > 0): ?>
                                        <span class="status-badge" style="background-color: #e53935;">
                                            <i class="fas fa-exclamation-circle"></i> Terlambat
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge" style="background-color: var(--success-color);">
                                            <i class="fas fa-check-circle"></i> Dikembalikan
                                        </span>
                                    <?php endif; ?>
                                <?php elseif ($is_currently_overdue): ?>
                                    <span class="status-badge" style="background-color: #ff9800;">
                                        <i class="fas fa-exclamation-triangle"></i> Terlambat
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge" style="background-color: var(--primary-color); color: white;">
                                        <i class="fas fa-book-reader"></i> Dipinjam
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><i class="fas fa-calendar-day"></i> 
                                <?php
                                if ($loan['tanggal_kembali']) {
                                    echo formatDate($loan['tanggal_kembali']);
                                } else {
                                    $returnDate = date('Y-m-d', strtotime($loan['tanggal_pinjam'] . ' + ' . $loan['lama_pinjam'] . ' days'));
                                    echo formatDate($returnDate) . ' (Estimasi)';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($loan['status'] == 'dipinjam'): ?>
                                    <?php if ($is_currently_overdue): ?>
                                        <?php 
                                            $today = new DateTime(date('Y-m-d'));
                                            $jatuh_tempo = new DateTime(date('Y-m-d', strtotime($loan['tanggal_pinjam'] . ' + ' . $loan['lama_pinjam'] . ' days')));
                                            $diff = $today->diff($jatuh_tempo);
                                            echo '<span style="color: #e53935; font-weight: bold;">' . $diff->days . ' hari</span>';
                                        ?>
                                    <?php else: ?>
                                        <span style="color: #4CAF50;">-</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if (isset($loan['keterlambatan']) && $loan['keterlambatan'] > 0): ?>
                                        <span style="color: #e53935; font-weight: bold;"><?php echo $loan['keterlambatan']; ?> hari</span>
                                    <?php else: ?>
                                        <span style="color: #4CAF50; font-weight: bold;">Tepat waktu</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Statistics Card -->
<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h3 class="card-title">Statistik</h3>
        <div class="card-tools">
            <small class="text-muted">Statistik ini hanya menampilkan data untuk filter yang dipilih</small>
        </div>
    </div>
    <div class="card-body">
        <div class="stats-container">
            <div class="stat-item">
                <div class="stat-icon" style="background: linear-gradient(135deg, #2196F3, #1976D2);">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $totalBooks; ?></h3>
                    <p>Total Buku Dipinjam</p>
                    <div class="stat-trend">
                        <i class="fas fa-chart-line"></i>
                        <span>Total keseluruhan</span>
                    </div>
                </div>
            </div>
            
            <div class="stat-item">
                <div class="stat-icon" style="background: linear-gradient(135deg, #4CAF50, #388E3C);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $returnedBooks; ?></h3>
                    <p>Buku Dikembalikan</p>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up"></i>
                        <span>Sudah kembali</span>
                    </div>
                </div>
            </div>
            
            <div class="stat-item">
                <div class="stat-icon" style="background: linear-gradient(135deg, #FF9800, #F57C00);">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $activeBooks; ?></h3>
                    <p>Buku Aktif</p>
                    <div class="stat-trend">
                        <i class="fas fa-book-reader"></i>
                        <span>Sedang dipinjam</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recap Card -->
<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-clipboard-list"></i> Rekap Peminjaman</h2>
        <?php if ($month !== null || $year !== null): ?>
            <p style="margin: 5px 0 0; font-size: 0.9rem; color: #666;">
                <i class="fas fa-info-circle"></i> 
                Rekap ini menampilkan ringkasan data untuk filter yang dipilih.
            </p>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php
        // Calculate recap statistics
        $totalLoans = count($loanHistory);
        $overdueCount = 0;
        $onTimeCount = 0;
        $mostBorrowedBook = '';
        $mostBorrowedCount = 0;
        $bookCounts = [];
        $classCounts = [];
        $totalLateDays = 0;
        $minLateDays = PHP_INT_MAX; // Untuk mencari keterlambatan minimum
        $maxLateDays = 0; // Untuk mencari keterlambatan maksimum

        foreach ($loanHistory as $loan) {
            // Count overdue vs on-time returns
            if ($loan['status'] == 'dikembalikan') {
                if (isset($loan['keterlambatan']) && $loan['keterlambatan'] > 0) {
                    $overdueCount++;
                    $totalLateDays += $loan['keterlambatan'];
                    // Update min dan max keterlambatan
                    $minLateDays = min($minLateDays, $loan['keterlambatan']);
                    $maxLateDays = max($maxLateDays, $loan['keterlambatan']);
                } else {
                    $onTimeCount++;
                }
            }

            // Count book popularity
            if (!isset($bookCounts[$loan['judul']])) {
                $bookCounts[$loan['judul']] = 0;
            }
            $bookCounts[$loan['judul']]++;
            if ($bookCounts[$loan['judul']] > $mostBorrowedCount) {
                $mostBorrowedCount = $bookCounts[$loan['judul']];
                $mostBorrowedBook = $loan['judul'];
            }

            // Count class distribution
            if (!isset($classCounts[$loan['kelas']])) {
                $classCounts[$loan['kelas']] = 0;
            }
            $classCounts[$loan['kelas']]++;
        }

        // Reset minLateDays jika tidak ada keterlambatan
        if ($minLateDays == PHP_INT_MAX) {
            $minLateDays = 0;
        }

        // Sort classes by name (not by count)
        ksort($classCounts); // Menggunakan ksort untuk mengurutkan berdasarkan key (nama kelas)
        $topClasses = $classCounts;
        ?>

        <div class="recap-container">
            <div class="recap-section">
                <h3><i class="fas fa-chart-bar"></i> Ringkasan Umum</h3>
                <div class="recap-grid">
                    <div class="recap-item">
                        <div class="recap-icon" style="background: linear-gradient(135deg, #2196F3, #1976D2);">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="recap-details">
                            <h4><?php echo $totalLoans; ?></h4>
                            <p>Total Peminjaman</p>
                        </div>
                    </div>
                    
                    <div class="recap-item">
                        <div class="recap-icon" style="background: linear-gradient(135deg, #4CAF50, #388E3C);">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="recap-details">
                            <h4><?php echo $onTimeCount; ?></h4>
                            <p>Dikembalikan Tepat Waktu</p>
                        </div>
                    </div>
                    
                    <div class="recap-item">
                        <div class="recap-icon" style="background: linear-gradient(135deg, #FF9800, #F57C00);">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="recap-details">
                            <h4><?php echo $overdueCount; ?></h4>
                            <p>Keterlambatan</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="recap-section">
                <h3><i class="fas fa-star"></i> Buku Terpopuler</h3>
                <?php if ($mostBorrowedCount > 0): ?>
                    <div class="recap-item highlight">
                        <div class="recap-icon" style="background: linear-gradient(135deg, #9C27B0, #7B1FA2);">
                            <i class="fas fa-crown"></i>
                        </div>
                        <div class="recap-details">
                            <h4><?php echo htmlspecialchars($mostBorrowedBook); ?></h4>
                            <p>Dipinjam <?php echo $mostBorrowedCount; ?> kali</p>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="no-data">Belum ada data peminjaman</p>
                <?php endif; ?>
            </div>

            <div class="recap-section">
                <h3><i class="fas fa-users"></i> Distribusi Kelas</h3>
                <?php if (!empty($topClasses)): ?>
                    <div class="class-distribution">
                        <?php 
                        // Hitung total untuk persentase
                        $totalLoansForClasses = array_sum($topClasses);
                        foreach ($topClasses as $class => $count): 
                        ?>
                            <div class="class-item">
                                <span class="class-name"><?php echo htmlspecialchars($class); ?></span>
                                <div class="class-bar">
                                    <div class="class-progress" style="width: <?php echo ($count / $totalLoansForClasses * 100); ?>%"></div>
                                </div>
                                <span class="class-count"><?php echo $count; ?> (<?php echo round(($count / $totalLoansForClasses * 100), 1); ?>%)</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-data">Belum ada data peminjaman</p>
                <?php endif; ?>
            </div>

            <?php if ($overdueCount > 0): ?>
            <div class="recap-section">
                <h3><i class="fas fa-clock"></i> Rentang Keterlambatan</h3>
                <div class="recap-item highlight">
                    <div class="recap-icon" style="background: linear-gradient(135deg, #F44336, #D32F2F);">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="recap-details">
                        <h4><?php 
                        if ($minLateDays == $maxLateDays) {
                            echo "$minLateDays hari";
                        } else {
                            echo "$minLateDays-$maxLateDays hari";
                        }
                        ?></h4>
                        <p>Rentang keterlambatan pengembalian</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Styles for loan history page */
.filter-container {
    margin-bottom: 20px;
}

.filter-form {
    width: 100%;
}

.filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: flex-end;
}

.filter-item {
    min-width: 150px;
    flex: 1;
}

.filter-form select {
    width: 100%;
}

.filter-form select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(13, 110, 55, 0.1);
    outline: none;
}

.button-container {
    display: flex;
    align-items: center;
    gap: 10px;
    height: 100%;
}

.button-container .btn {
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #dee2e6;
    background-color: var(--primary-color);
    color: white;
    margin-right: 5px;
}

.button-container .btn-secondary {
    background-color: #f8f9fa;
    color: #495057;
    border: 1px solid #dee2e6;
    margin-right: 0;
}

.button-container .btn-secondary:hover {
    background-color: #e9ecef;
    border-color: #dee2e6;
}

.button-container .btn:hover {
    opacity: 0.9;
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    color: white;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 40px 20px;
    text-align: center;
}

.empty-icon {
    font-size: 48px;
    color: var(--secondary-color);
    opacity: 0.5;
    margin-bottom: 15px;
}

/* Responsive styles */
@media (max-width: 768px) {
    .filter-row {
        flex-direction: column;
        gap: 10px;
    }
    
    .filter-item {
        width: 100%;
    }
    
    .button-container {
        width: 100%;
        justify-content: space-between;
    }
    
    .button-container .btn {
        flex: 1;
        text-align: center;
        margin-right: 5px;
    }
    
    .button-container .btn:last-child {
        margin-right: 0;
    }
}

/* Enhanced Statistics Styles */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    padding: 10px;
}

.stat-item {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.stat-details {
    flex: 1;
}

.stat-details h3 {
    font-size: 28px;
    font-weight: bold;
    margin: 0 0 5px 0;
    color: #2c3e50;
}

.stat-details p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.stat-trend {
    margin-top: 8px;
    font-size: 12px;
    color: #666;
    display: flex;
    align-items: center;
    gap: 5px;
}

.stat-trend i {
    font-size: 14px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .stats-container {
        grid-template-columns: 1fr;
    }
    
    .stat-item {
        padding: 15px;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 20px;
    }
    
    .stat-details h3 {
        font-size: 24px;
    }
}

/* Recap Styles */
.recap-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.recap-section {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.recap-section h3 {
    margin: 0 0 15px 0;
    color: #2c3e50;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.recap-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.recap-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    transition: transform 0.2s;
}

.recap-item:hover {
    transform: translateY(-2px);
}

.recap-item.highlight {
    background: #fff;
    border: 1px solid #e9ecef;
}

.recap-icon {
    width: 45px;
    height: 45px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: white;
}

.recap-details h4 {
    margin: 0;
    font-size: 1.2rem;
    color: #2c3e50;
}

.recap-details p {
    margin: 5px 0 0 0;
    font-size: 0.9rem;
    color: #666;
}

.class-distribution {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.class-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.class-name {
    min-width: 100px;
    font-weight: 500;
}

.class-bar {
    flex: 1;
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

.class-progress {
    height: 100%;
    background: linear-gradient(90deg, #2196F3, #1976D2);
    border-radius: 4px;
    transition: width 0.3s ease;
}

.class-count {
    min-width: 40px;
    text-align: right;
    font-weight: 500;
    color: #666;
}

.no-data {
    color: #666;
    font-style: italic;
    text-align: center;
    padding: 20px;
}

@media (max-width: 768px) {
    .recap-grid {
        grid-template-columns: 1fr;
    }
    
    .recap-item {
        padding: 12px;
    }
    
    .recap-icon {
        width: 40px;
        height: 40px;
        font-size: 18px;
    }
    
    .class-name {
        min-width: 80px;
        font-size: 0.9rem;
    }
}
</style>

<?php
// Include footer
require_once 'includes/footer.php';
?> 