<?php
// Set page title
$pageTitle = 'Rekap Minat Baca Siswa';

// Include header
require_once 'includes/header.php';

// Get filter by class
$filterClass = '';
if (isset($_GET['kelas']) && !empty($_GET['kelas'])) {
    $filterClass = cleanInput($_GET['kelas']);
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

// Prepare condition for filtering
$condition = '';
if (!empty($filterClass)) {
    $condition = "WHERE s.kelas = '$filterClass'";
}

// Get all books in library
$query = "SELECT COUNT(*) as total FROM buku";
$result = mysqli_query($conn, $query);
$totalLibraryBooks = 0;
if ($result) {
    $data = mysqli_fetch_assoc($result);
    $totalLibraryBooks = $data['total'] ?: 0;
}

// Get reading levels data
$readingLevels = [
    [
        'min_points' => 0,
        'level' => 'Pemula',
        'icon' => 'fa-seedling',
        'color' => '#78C2AD',
        'description' => 'Baru memulai perjalanan membaca. Teruslah berusaha!'
    ],
    [
        'min_points' => 50,
        'level' => 'Pembaca Aktif',
        'icon' => 'fa-book-reader',
        'color' => '#6CC3D5',
        'description' => 'Mulai menumbuhkan kebiasaan membaca. Bagus!'
    ],
    [
        'min_points' => 100,
        'level' => 'Pembaca Terampil',
        'icon' => 'fa-graduation-cap',
        'color' => '#3498DB',
        'description' => 'Keterampilan membaca semakin meningkat!'
    ],
    [
        'min_points' => 200,
        'level' => 'Pembaca Ahli',
        'icon' => 'fa-fire',
        'color' => '#F39C12',
        'description' => 'Membaca sudah menjadi kebiasaan. Luar biasa!'
    ],
    [
        'min_points' => 300,
        'level' => 'Pembaca Profesional',
        'icon' => 'fa-star',
        'color' => '#E74C3C',
        'description' => 'Pembaca berpengalaman dengan pengetahuan luas!'
    ],
    [
        'min_points' => 500,
        'level' => 'Pembaca Master',
        'icon' => 'fa-crown',
        'color' => '#9B59B6',
        'description' => 'Level tertinggi! Komitmen luar biasa pada membaca!'
    ]
];

// Get summary of all students' reading interest levels
$query = "
    SELECT 
        s.id, 
        s.nisn, 
        s.nama,
        s.kelas,
        COUNT(DISTINCT p.buku_id) as unique_books_read,
        COUNT(DISTINCT p.buku_id) * 10 as reading_points
    FROM siswa s
    LEFT JOIN peminjaman p ON s.id = p.siswa_id
    $condition
    GROUP BY s.id
    ORDER BY reading_points DESC, unique_books_read DESC, s.nama ASC
    LIMIT 10
";

$result = mysqli_query($conn, $query);
$students = [];
$levelStats = [];

// Initialize level statistics
foreach ($readingLevels as $level) {
    $levelStats[$level['level']] = 0;
}

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Calculate reading level for each student
        $readingPoints = $row['reading_points'];
        $currentLevel = $readingLevels[0]; // Default to lowest level
        
        // Find correct level based on points
        foreach ($readingLevels as $level) {
            if ($readingPoints >= $level['min_points']) {
                $currentLevel = $level;
            } else {
                break;
            }
        }
        
        // Add level info to student data
        $row['level'] = $currentLevel['level'];
        $row['level_icon'] = $currentLevel['icon'];
        $row['level_color'] = $currentLevel['color'];
        $row['level_description'] = $currentLevel['description'];
        
        // Calculate reading interest percentage
        $row['interest_percentage'] = $totalLibraryBooks > 0 ? 
            min(round(($row['unique_books_read'] / $totalLibraryBooks) * 100), 100) : 0;
        
        // Count students in each level
        $levelStats[$currentLevel['level']]++;
        
        $students[] = $row;
    }
}
?>

<!-- Filter Card -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-filter"></i> Filter Kelas</h2>
    </div>
    <div style="padding: 15px;">
        <form action="" method="get" class="filter-form">
            <div class="filter-row" style="display: flex; gap: 15px; align-items: flex-end;">
                <div class="filter-item" style="flex: 1; min-width: 200px;">
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
                
                <div class="button-container" style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn" style="margin-right: 10px;">Filter</button>
                    <a href="minat_baca.php" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Filter info -->
<?php if (!empty($filterClass)): ?>
    <div class="filter-info" style="margin-bottom: 15px; padding: 10px 15px; background-color: #e9f7ef; border-radius: 4px;">
        <p style="margin: 0; font-size: 0.9rem;">
            <i class="fas fa-info-circle"></i> 
            Menampilkan data minat baca untuk siswa kelas <strong><?php echo htmlspecialchars($filterClass); ?></strong>
            (<?php echo count($students); ?> siswa)
        </p>
    </div>
<?php endif; ?>

<!-- Reading Interest Level Statistics -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-chart-pie"></i> Statistik Level Minat Baca</h2>
    </div>
    <div class="card-body" style="padding: 20px;">
        <div class="level-stats-container" style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: space-between;">
            <?php foreach ($readingLevels as $level): 
                $levelCount = $levelStats[$level['level']] ?? 0;
                $percentage = count($students) > 0 ? round(($levelCount / count($students)) * 100) : 0;
            ?>
                <div class="level-stat-item" style="flex: 1; min-width: 180px; background-color: white; border-radius: 8px; padding: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-left: 4px solid <?php echo $level['color']; ?>;">
                    <div style="display: flex; align-items: center; margin-bottom: 10px;">
                        <div style="width: 30px; height: 30px; background-color: <?php echo $level['color']; ?>; border-radius: 50%; display: flex; justify-content: center; align-items: center; margin-right: 10px;">
                            <i class="fas <?php echo $level['icon']; ?>" style="color: white; font-size: 0.9rem;"></i>
                        </div>
                        <h4 style="margin: 0; font-size: 1rem;"><?php echo $level['level']; ?></h4>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 1.8rem; font-weight: bold; color: <?php echo $level['color']; ?>;"><?php echo $levelCount; ?></div>
                        <div style="font-size: 0.9rem; color: #666;">Siswa (<?php echo $percentage; ?>%)</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Students Reading Interest Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-trophy"></i> 10 Pembaca Terbaik</h2>
    </div>
    
    <?php if (empty($students)): ?>
        <div class="empty-state" style="padding: 30px; text-align: center;">
            <i class="fas fa-book-reader" style="font-size: 3rem; color: #6c757d; margin-bottom: 15px;"></i>
            <p>Tidak ada data siswa<?php echo (!empty($filterClass)) ? ' yang sesuai dengan filter' : ''; ?>.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 50px;">Peringkat</th>
                        <th>NISN</th>
                        <th>Nama</th>
                        <th>Kelas</th>
                        <th>Jumlah Buku Dibaca</th>
                        <th>Poin Baca</th>
                        <th>Level Minat Baca</th>
                        <th>Persentase</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $index => $student): ?>
                        <tr>
                            <td class="text-center">
                                <?php if ($index < 3): ?>
                                    <span class="ranking-badge" style="display: inline-block; width: 30px; height: 30px; line-height: 30px; text-align: center; border-radius: 50%; background-color: <?php 
                                        echo $index === 0 ? '#FFD700' : ($index === 1 ? '#C0C0C0' : '#CD7F32'); 
                                    ?>; color: <?php echo $index === 0 ? '#000' : '#FFF'; ?>; font-weight: bold;">
                                        <?php echo $index + 1; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="ranking-number" style="font-weight: 500;"><?php echo $index + 1; ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($student['nisn']); ?></td>
                            <td><?php echo htmlspecialchars($student['nama']); ?></td>
                            <td>Kelas <?php echo htmlspecialchars($student['kelas']); ?></td>
                            <td><?php echo $student['unique_books_read']; ?> buku</td>
                            <td><?php echo $student['reading_points']; ?> poin</td>
                            <td>
                                <div style="display: flex; align-items: center;">
                                    <div style="width: 24px; height: 24px; background-color: <?php echo $student['level_color']; ?>; border-radius: 50%; display: flex; justify-content: center; align-items: center; margin-right: 8px;">
                                        <i class="fas <?php echo $student['level_icon']; ?>" style="color: white; font-size: 0.8rem;"></i>
                                    </div>
                                    <?php echo $student['level']; ?>
                                </div>
                            </td>
                            <td>
                                <div class="progress-bar-container" style="height: 10px; background-color: #eee; border-radius: 5px; overflow: hidden;">
                                    <div class="progress-bar" style="width: <?php echo $student['interest_percentage']; ?>%; height: 100%; background-color: <?php echo $student['level_color']; ?>;"></div>
                                </div>
                                <div style="font-size: 0.8rem; margin-top: 5px; text-align: right;">
                                    <?php echo $student['interest_percentage']; ?>%
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
/* Add styles for ranking badges */
.ranking-badge {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}

.ranking-badge:hover {
    transform: scale(1.1);
}

.ranking-number {
    color: #666;
}

/* Responsive adjustments for the table */
@media (max-width: 768px) {
    .ranking-badge {
        width: 25px !important;
        height: 25px !important;
        line-height: 25px !important;
        font-size: 0.9rem;
    }
}
</style>

<?php
// Include footer
require_once 'includes/footer.php';
?> 