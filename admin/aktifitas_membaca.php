<?php
// Set page title
$pageTitle = 'Aktifitas Membaca Siswa';

// Include header
require_once 'includes/header.php';

// Get filter parameters
$filterClass = '';
$filterMonth = '';
$filterYear = date('Y'); // Default to current year

if (isset($_GET['kelas']) && !empty($_GET['kelas'])) {
    $filterClass = cleanInput($_GET['kelas']);
}

if (isset($_GET['bulan']) && !empty($_GET['bulan'])) {
    $filterMonth = (int)$_GET['bulan'];
}

if (isset($_GET['tahun']) && !empty($_GET['tahun'])) {
    $filterYear = (int)$_GET['tahun'];
}

// Get all classes for filter
$query = "SELECT DISTINCT kelas FROM siswa ORDER BY kelas ASC";
$result = mysqli_query($conn, $query);
$classes = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $classes[] = $row['kelas'];
    }
}

// Prepare date conditions for filtering
$dateCondition = '';
if (!empty($filterMonth)) {
    $dateCondition .= " AND MONTH(p.tanggal_pinjam) = $filterMonth";
}
if (!empty($filterYear)) {
    $dateCondition .= " AND YEAR(p.tanggal_pinjam) = $filterYear";
}

// Prepare class condition for filtering
$classCondition = '';
if (!empty($filterClass)) {
    $classCondition = " AND s.kelas = '$filterClass'";
}

// Get reading activities data by day
$queryDaily = "
    SELECT 
        DATE(p.tanggal_pinjam) as tanggal,
        COUNT(p.id) as jumlah_peminjaman,
        COUNT(DISTINCT s.id) as jumlah_siswa
    FROM peminjaman p
    JOIN siswa s ON p.siswa_id = s.id
    WHERE 1=1 $dateCondition $classCondition
    GROUP BY DATE(p.tanggal_pinjam)
    ORDER BY tanggal DESC
    LIMIT 31
";

$resultDaily = mysqli_query($conn, $queryDaily);
$dailyActivities = [];

if ($resultDaily) {
    while ($row = mysqli_fetch_assoc($resultDaily)) {
        $dailyActivities[] = $row;
    }
}

// Get top readers of the month/period
$queryTopReaders = "
    SELECT 
        s.id,
        s.nisn,
        s.nama,
        s.kelas,
        COUNT(DISTINCT p.buku_id) as jumlah_buku_dibaca
    FROM siswa s
    JOIN peminjaman p ON s.id = p.siswa_id
    WHERE 1=1 $dateCondition $classCondition
    GROUP BY s.id
    ORDER BY jumlah_buku_dibaca DESC
    LIMIT 10
";

$resultTopReaders = mysqli_query($conn, $queryTopReaders);
$topReaders = [];

if ($resultTopReaders) {
    while ($row = mysqli_fetch_assoc($resultTopReaders)) {
        $topReaders[] = $row;
    }
}

// Get reading statistics by class
$queryClassStats = "
    SELECT 
        s.kelas,
        COUNT(DISTINCT p.id) as total_peminjaman,
        COUNT(DISTINCT s.id) as jumlah_siswa_aktif,
        COUNT(DISTINCT p.buku_id) as jumlah_buku_dibaca
    FROM siswa s
    LEFT JOIN peminjaman p ON s.id = p.siswa_id AND 1=1 $dateCondition
    GROUP BY s.kelas
    ORDER BY s.kelas ASC
";

$resultClassStats = mysqli_query($conn, $queryClassStats);
$classStats = [];

if ($resultClassStats) {
    while ($row = mysqli_fetch_assoc($resultClassStats)) {
        $classStats[] = $row;
    }
}

// Get total active students in selected period
$queryTotalActive = "
    SELECT COUNT(DISTINCT s.id) as total_siswa_aktif
    FROM siswa s
    JOIN peminjaman p ON s.id = p.siswa_id
    WHERE 1=1 $dateCondition $classCondition
";

$resultTotalActive = mysqli_query($conn, $queryTotalActive);
$totalActiveStudents = 0;

if ($resultTotalActive) {
    $row = mysqli_fetch_assoc($resultTotalActive);
    $totalActiveStudents = $row['total_siswa_aktif'];
}

// Get total students
$queryTotalStudents = "
    SELECT COUNT(*) as total
    FROM siswa s
    WHERE 1=1" . (!empty($filterClass) ? " AND s.kelas = '$filterClass'" : "");

$resultTotalStudents = mysqli_query($conn, $queryTotalStudents);
$totalStudents = 0;

if ($resultTotalStudents) {
    $row = mysqli_fetch_assoc($resultTotalStudents);
    $totalStudents = $row['total'];
}

// Calculate student participation rate
$participationRate = $totalStudents > 0 ? round(($totalActiveStudents / $totalStudents) * 100) : 0;

// Get most popular books in the period
$queryPopularBooks = "
    SELECT 
        b.id,
        b.judul,
        b.pengarang,
        COUNT(p.id) as total_peminjaman
    FROM buku b
    JOIN peminjaman p ON b.id = p.buku_id
    JOIN siswa s ON p.siswa_id = s.id
    WHERE 1=1 $dateCondition $classCondition
    GROUP BY b.id
    ORDER BY total_peminjaman DESC
    LIMIT 5
";

$resultPopularBooks = mysqli_query($conn, $queryPopularBooks);
$popularBooks = [];

if ($resultPopularBooks) {
    while ($row = mysqli_fetch_assoc($resultPopularBooks)) {
        $popularBooks[] = $row;
    }
}
?>

<!-- Filter Card -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-filter"></i> Filter Aktifitas</h2>
    </div>
    <div style="padding: 15px;">
        <form action="" method="get" class="filter-form">
            <div class="filter-row" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
                <div class="filter-item" style="flex: 1; min-width: 150px;">
                    <label for="kelas" style="display: block; margin-bottom: 5px; font-weight: 500;">Kelas:</label>
                    <select name="kelas" id="kelas" class="form-control" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class; ?>" <?php echo $filterClass == $class ? 'selected' : ''; ?>>
                                Kelas <?php echo $class; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-item" style="flex: 1; min-width: 150px;">
                    <label for="bulan" style="display: block; margin-bottom: 5px; font-weight: 500;">Bulan:</label>
                    <select name="bulan" id="bulan" class="form-control" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                        <option value="">Semua Bulan</option>
                        <?php
                        $bulan = array(
                            1 => 'Januari', 
                            2 => 'Februari', 
                            3 => 'Maret', 
                            4 => 'April', 
                            5 => 'Mei', 
                            6 => 'Juni', 
                            7 => 'Juli', 
                            8 => 'Agustus', 
                            9 => 'September', 
                            10 => 'Oktober', 
                            11 => 'November', 
                            12 => 'Desember'
                        );
                        
                        foreach ($bulan as $key => $value): ?>
                            <option value="<?php echo $key; ?>" <?php echo $filterMonth == $key ? 'selected' : ''; ?>>
                                <?php echo $value; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-item" style="flex: 1; min-width: 150px;">
                    <label for="tahun" style="display: block; margin-bottom: 5px; font-weight: 500;">Tahun:</label>
                    <select name="tahun" id="tahun" class="form-control" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                        <?php 
                        $currentYear = date('Y');
                        for ($year = $currentYear; $year >= $currentYear - 5; $year--): ?>
                            <option value="<?php echo $year; ?>" <?php echo $filterYear == $year ? 'selected' : ''; ?>>
                                <?php echo $year; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="button-container" style="display: flex; align-items: flex-end; gap: 10px;">
                    <button type="submit" class="btn">Filter</button>
                    <a href="aktifitas_membaca.php" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Filter info -->
<div class="filter-info" style="margin-bottom: 15px; padding: 10px 15px; background-color: #e9f7ef; border-radius: 4px;">
    <p style="margin: 0; font-size: 0.9rem;">
        <i class="fas fa-info-circle"></i> 
        Menampilkan data aktifitas membaca 
        <?php if (!empty($filterClass)): ?>
            untuk siswa kelas <strong><?php echo htmlspecialchars($filterClass); ?></strong>
        <?php endif; ?>
        <?php if (!empty($filterMonth)): ?>
            pada bulan <strong><?php echo $bulan[$filterMonth]; ?></strong>
        <?php endif; ?>
        <strong><?php echo $filterYear; ?></strong>
    </p>
</div>

<!-- Summary Statistics -->
<div class="stats-row" style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;">
    <!-- Participation Rate -->
    <div class="stat-card" style="flex: 1; min-width: 220px; background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0; font-size: 1.1rem;">Tingkat Partisipasi</h3>
            <div style="width: 36px; height: 36px; background-color: #4CAF50; border-radius: 50%; display: flex; justify-content: center; align-items: center;">
                <i class="fas fa-users" style="color: white;"></i>
            </div>
        </div>
        <div style="font-size: 1.8rem; font-weight: bold; margin-bottom: 5px;"><?php echo $participationRate; ?>%</div>
        <p style="margin: 0; color: #666; font-size: 0.9rem;"><?php echo $totalActiveStudents; ?> dari <?php echo $totalStudents; ?> siswa aktif membaca</p>
        
        <div class="progress-bar-container" style="height: 8px; background-color: #eee; border-radius: 4px; margin-top: 15px; overflow: hidden;">
            <div class="progress-bar" style="width: <?php echo $participationRate; ?>%; height: 100%; background-color: #4CAF50;"></div>
        </div>
    </div>
    
    <!-- Total Loans -->
    <?php 
    $totalLoans = 0;
    foreach($dailyActivities as $activity) {
        $totalLoans += $activity['jumlah_peminjaman'];
    } 
    ?>
    <div class="stat-card" style="flex: 1; min-width: 220px; background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0; font-size: 1.1rem;">Total Peminjaman</h3>
            <div style="width: 36px; height: 36px; background-color: #2196F3; border-radius: 50%; display: flex; justify-content: center; align-items: center;">
                <i class="fas fa-book" style="color: white;"></i>
            </div>
        </div>
        <div style="font-size: 1.8rem; font-weight: bold; margin-bottom: 5px;"><?php echo $totalLoans; ?></div>
        <p style="margin: 0; color: #666; font-size: 0.9rem;">Buku dipinjam pada periode ini</p>
    </div>
    
    <!-- Active Students -->
    <div class="stat-card" style="flex: 1; min-width: 220px; background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0; font-size: 1.1rem;">Siswa Aktif Membaca</h3>
            <div style="width: 36px; height: 36px; background-color: #FF9800; border-radius: 50%; display: flex; justify-content: center; align-items: center;">
                <i class="fas fa-user-graduate" style="color: white;"></i>
            </div>
        </div>
        <div style="font-size: 1.8rem; font-weight: bold; margin-bottom: 5px;"><?php echo $totalActiveStudents; ?></div>
        <p style="margin: 0; color: #666; font-size: 0.9rem;">Siswa yang meminjam buku</p>
    </div>
</div>

<!-- Two Column Layout for Charts -->
<div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;">
    <!-- Left Column - Daily Activity -->
    <div style="flex: 1; min-width: 300px;">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-chart-line"></i> Aktifitas Membaca Harian</h2>
            </div>
            
            <?php if (empty($dailyActivities)): ?>
                <div class="empty-state" style="padding: 30px; text-align: center;">
                    <i class="fas fa-chart-line" style="font-size: 3rem; color: #6c757d; margin-bottom: 15px;"></i>
                    <p>Tidak ada data aktifitas membaca pada periode ini.</p>
                </div>
            <?php else: ?>
                <div style="padding: 15px;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Jumlah Peminjaman</th>
                                <th>Siswa Aktif</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dailyActivities as $activity): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($activity['tanggal'])); ?></td>
                                    <td><?php echo $activity['jumlah_peminjaman']; ?> buku</td>
                                    <td><?php echo $activity['jumlah_siswa']; ?> siswa</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Right Column - Top Readers -->
    <div style="flex: 1; min-width: 300px;">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-trophy"></i> Pembaca Terbaik</h2>
            </div>
            
            <?php if (empty($topReaders)): ?>
                <div class="empty-state" style="padding: 30px; text-align: center;">
                    <i class="fas fa-trophy" style="font-size: 3rem; color: #6c757d; margin-bottom: 15px;"></i>
                    <p>Tidak ada data pembaca pada periode ini.</p>
                </div>
            <?php else: ?>
                <div style="padding: 15px;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Jumlah Buku</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topReaders as $index => $reader): ?>
                                <tr>
                                    <td>
                                        <?php if ($index < 3): ?>
                                            <div style="width: 24px; height: 24px; background-color: <?php echo $index == 0 ? '#FFD700' : ($index == 1 ? '#C0C0C0' : '#CD7F32'); ?>; border-radius: 50%; display: flex; justify-content: center; align-items: center; color: white; font-weight: bold;">
                                                <?php echo $index + 1; ?>
                                            </div>
                                        <?php else: ?>
                                            <?php echo $index + 1; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($reader['nama']); ?></td>
                                    <td>Kelas <?php echo htmlspecialchars($reader['kelas']); ?></td>
                                    <td><?php echo $reader['jumlah_buku_dibaca']; ?> buku</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Popular Books Section -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-star"></i> Buku Terpopuler</h2>
    </div>
    
    <?php if (empty($popularBooks)): ?>
        <div class="empty-state" style="padding: 30px; text-align: center;">
            <i class="fas fa-book" style="font-size: 3rem; color: #6c757d; margin-bottom: 15px;"></i>
            <p>Tidak ada data buku terpopuler pada periode ini.</p>
        </div>
    <?php else: ?>
        <div style="padding: 15px; display: flex; flex-wrap: wrap; gap: 20px;">
            <?php foreach ($popularBooks as $index => $book): ?>
                <div class="popular-book" style="flex: 1; min-width: 200px; background: #f8f9fa; border-radius: 8px; padding: 15px; position: relative;">
                    <?php if ($index == 0): ?>
                        <div style="position: absolute; top: -10px; right: -10px; background: #FFD700; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; justify-content: center; align-items: center;">
                            <i class="fas fa-crown"></i>
                        </div>
                    <?php endif; ?>
                    <h3 style="margin-top: 0; font-size: 1rem;"><?php echo htmlspecialchars($book['judul']); ?></h3>
                    <p style="margin: 5px 0; color: #666; font-size: 0.9rem;">Oleh: <?php echo htmlspecialchars($book['pengarang']); ?></p>
                    <div style="margin-top: 10px; font-weight: bold;">
                        <i class="fas fa-users" style="color: #2196F3;"></i> <?php echo $book['total_peminjaman']; ?> kali dipinjam
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Reading Statistics by Class -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-chart-bar"></i> Statistik Membaca per Kelas</h2>
    </div>
    
    <?php if (empty($classStats)): ?>
        <div class="empty-state" style="padding: 30px; text-align: center;">
            <i class="fas fa-chart-bar" style="font-size: 3rem; color: #6c757d; margin-bottom: 15px;"></i>
            <p>Tidak ada data statistik kelas.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kelas</th>
                        <th>Total Peminjaman</th>
                        <th>Siswa Aktif</th>
                        <th>Jumlah Buku Dibaca</th>
                        <th>Rata-rata per Siswa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classStats as $stat): ?>
                        <tr>
                            <td>Kelas <?php echo htmlspecialchars($stat['kelas']); ?></td>
                            <td><?php echo $stat['total_peminjaman']; ?></td>
                            <td><?php echo $stat['jumlah_siswa_aktif']; ?> siswa</td>
                            <td><?php echo $stat['jumlah_buku_dibaca']; ?> buku</td>
                            <td>
                                <?php 
                                    $average = $stat['jumlah_siswa_aktif'] > 0 ? 
                                        round($stat['jumlah_buku_dibaca'] / $stat['jumlah_siswa_aktif'], 1) : 0;
                                    echo $average . ' buku/siswa';
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
/* Responsive styles */
@media (max-width: 768px) {
    .filter-row {
        flex-direction: column;
    }
    
    .button-container {
        margin-top: 10px;
        width: 100%;
        justify-content: space-between;
    }
    
    .button-container .btn {
        flex: 1;
        text-align: center;
    }
    
    .stats-row {
        flex-direction: column;
    }
    
    .stat-card {
        width: 100%;
    }
}
</style>

<?php
// Include footer
require_once 'includes/footer.php';
?> 