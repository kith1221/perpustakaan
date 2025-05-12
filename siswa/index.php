<?php
// Set page title
$pageTitle = 'Dashboard Siswa';

// Include header
require_once 'includes/header.php';

// Get active loans
$activeLoans = getActiveLoans($_SESSION['user_id']);

// Get loan history
$loanHistory = getLoanHistory($_SESSION['user_id']);
$totalBooks = count($loanHistory);

// Get student's overdue books
$overdueBooks = getStudentOverdueBooks($_SESSION['user_id']);
$overdueCount = count($overdueBooks);

// Get total books in library for reading interest calculation
$query = "SELECT COUNT(*) as total FROM buku";
$result = mysqli_query($conn, $query);
$totalLibraryBooks = 0;
if ($result) {
    $data = mysqli_fetch_assoc($result);
    $totalLibraryBooks = $data['total'] ?: 0;
}

// Calculate reading interest percentage
$readingInterestPercentage = 0;
$readingInterestLevel = 'Pemula';
$readingPoints = 0;

if ($totalLibraryBooks > 0) {
    // Calculate unique books read
    $uniqueBooks = [];
    foreach ($loanHistory as $history) {
        if (!in_array($history['buku_id'], $uniqueBooks)) {
            $uniqueBooks[] = $history['buku_id'];
        }
    }
    
    $uniqueBookCount = count($uniqueBooks);
    $readingInterestPercentage = min(round(($uniqueBookCount / $totalLibraryBooks) * 100), 100);
    $readingPoints = $uniqueBookCount * 10; // 10 points per book

    // Determine reading level based on points
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

    // Default to the lowest level
    $currentLevel = $readingLevels[0];
    
    // Find the correct level based on points
    foreach ($readingLevels as $level) {
        if ($readingPoints >= $level['min_points']) {
            $currentLevel = $level;
        } else {
            break;
        }
    }
    
    $readingInterestLevel = $currentLevel['level'];
    $levelIcon = $currentLevel['icon'];
    $levelColor = $currentLevel['color'];
    $levelDescription = $currentLevel['description'];
    
    // Calculate next level information
    $nextLevelIndex = array_search($currentLevel, $readingLevels) + 1;
    $hasNextLevel = $nextLevelIndex < count($readingLevels);
    $nextLevel = $hasNextLevel ? $readingLevels[$nextLevelIndex] : null;
}

            // Get search parameters
            $search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
            $kategori = (isset($_GET['kategori']) && $_GET['kategori'] !== '' && $_GET['kategori'] !== '0' && $_GET['kategori'] !== 'all') ? (int)$_GET['kategori'] : null;

            // Cek apakah user melakukan pencarian (isi search atau pilih kategori, termasuk semua kategori)
            $searchFilled = isset($_GET['search']) && strlen(trim($_GET['search'])) > 0;
            $kategoriFilled = isset($_GET['kategori']) && $_GET['kategori'] !== '' && $_GET['kategori'] !== '0' && $_GET['kategori'] !== 'all';
            $showSearchResults = $searchFilled || isset($_GET['kategori']);
            $books = [];
            if ($showSearchResults) {
            // Build query conditions
            $where_conditions = [];
            $params = [];
            if ($searchFilled) {
                $where_conditions[] = "(b.judul LIKE ? OR b.pengarang LIKE ? OR b.isbn LIKE ?)";
                $search_param = "%$search%";
                $params[] = $search_param;
                $params[] = $search_param;
                $params[] = $search_param;
            }
            if ($kategoriFilled) {
                $where_conditions[] = "b.kategori_id = ?";
                $params[] = $kategori;
            }
            $where_conditions[] = "b.jumlah_buku > 0";
            $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
            // Get total count for pagination
            $count_query = "SELECT COUNT(*) as total FROM buku b LEFT JOIN kategori_buku k ON b.kategori_id = k.id $where_clause";
            $count_stmt = mysqli_prepare($conn, $count_query);
            if (!empty($params)) {
                $types = str_repeat('s', count($params));
                mysqli_stmt_bind_param($count_stmt, $types, ...$params);
            }
            mysqli_stmt_execute($count_stmt);
            $total_result = mysqli_stmt_get_result($count_stmt);
            $total_books = mysqli_fetch_assoc($total_result)['total'];
            // Get books with filters (TANPA LIMIT)
            $query = "SELECT b.*, k.nama_kategori 
                      FROM buku b 
                      LEFT JOIN kategori_buku k ON b.kategori_id = k.id 
                      $where_clause 
                      ORDER BY b.judul ASC";
            $stmt = mysqli_prepare($conn, $query);
            if (!empty($params)) {
                $types = str_repeat('s', count($params));
                mysqli_stmt_bind_param($stmt, $types, ...$params);
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $books[] = $row;
            }
            }
?>

<div class="dashboard-container">
    <!-- Search Toggle Button -->
    <div class="search-toggle-container">
        <button id="searchToggleBtn" class="search-toggle-btn">
            <i class="fas fa-search"></i>
            <span>Cari Buku</span>
        </button>
    </div>

    <!-- Collapsible Search Section -->
    <div id="searchSection" class="search-section" style="display: none;">
        <div class="search-card">
            <div class="search-header">
                <h3><i class="fas fa-book-open"></i> Cek Ketersediaan Buku</h3>
            </div>
            <form action="" method="get" class="search-filter-form">
                <div class="form-group">
                    <div class="search-input-wrapper">
                        <input type="text" id="search" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" placeholder="Cari judul, pengarang, atau ISBN...">
                    </div>
                    <div class="filter-group">
                        <div class="filter-item">
                            <label for="kategori"><i class="fas fa-tags"></i> Kategori</label>
                            <select id="kategori" name="kategori">
                                <option value="" disabled <?php echo !isset($_GET['kategori']) || $_GET['kategori'] === '' ? 'selected' : ''; ?>>Pilih Jenis Kategori</option>
                                <option value="all" <?php echo (isset($_GET['kategori']) && $_GET['kategori'] === 'all') ? 'selected' : ''; ?>>Semua Kategori</option>
                                <?php
                                $query = "SELECT * FROM kategori_buku ORDER BY nama_kategori";
                                $result = mysqli_query($conn, $query);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $selected = (isset($_GET['kategori']) && $_GET['kategori'] == $row['id']) ? 'selected' : '';
                                    echo "<option value='{$row['id']}' {$selected}>{$row['nama_kategori']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="search-actions">
                        <button type="submit" class="btn btn-search">
                            <i class="fas fa-search"></i>
                            <span>Cari</span>
                        </button>
                        <a href="index.php" class="btn btn-reset">
                            <i class="fas fa-redo"></i>
                            <span>Reset</span>
                        </a>
                    </div>
                </div>
            </form>

            <!-- Search Results -->
            <?php if ($showSearchResults && !empty($books)): ?>
                <div class="search-results">
                    <h4>Hasil Pencarian</h4>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Judul</th>
                                    <th>Kategori</th>
                                    <th>Pengarang</th>
                                    <th>Stok</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($books as $book): ?>
                                    <tr class="<?php echo $book['jumlah_buku'] <= 3 ? 'stock-low' : 'stock-good'; ?>">
                                        <td data-label="Judul"><?php echo htmlspecialchars($book['judul']); ?></td>
                                        <td data-label="Kategori"><?php echo htmlspecialchars($book['nama_kategori']); ?></td>
                                        <td data-label="Pengarang"><?php echo htmlspecialchars($book['pengarang']); ?></td>
                                        <td data-label="Stok" class="stock-number"><?php echo $book['jumlah_buku']; ?></td>
                                        <td data-label="Aksi">
                                            <button onclick="showBorrowNotification('<?php echo htmlspecialchars($book['judul']); ?>')" class="btn btn-primary" style="padding: 5px 10px; font-size: 0.8rem;">Pinjam</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($total_books > 0): ?>
                        <div class="show-more-container">
                            <button onclick="loadMoreBooks(<?php echo $total_books; ?>)" class="btn btn-secondary show-more-btn">
                                <i class="fas fa-list"></i> Lihat Selengkapnya
                            </button>
                            <span class="results-count">Menampilkan <?php echo count($books); ?> dari <?php echo $total_books; ?> buku</span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif ($showSearchResults): ?>
                <div class="search-results">
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <p>Tidak ada buku yang ditemukan</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Borrow Notification Modal -->
    <div id="borrowModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-info-circle"></i> Informasi Peminjaman</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div class="notification-icon">
                    <i class="fas fa-book-reader"></i>
                </div>
                <p class="book-title"></p>
                <p class="notification-message">
                    Untuk meminjam buku ini, silakan datang ke admin perpustakaan dengan membawa kartu pelajar.
                </p>
                <div class="notification-details">
                    <div class="detail-item">
                        <i class="fas fa-clock"></i>
                        <span>Jam Operasional: Senin - Jumat, 07:00 - 16:00</span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-id-card"></i>
                        <span>Jangan lupa membawa kartu pelajar</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary close-modal">Tutup</button>
            </div>
        </div>
    </div>

    <!-- Level Info Modal -->
    <div id="levelInfoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-award"></i> Informasi Level Akun</h3>
                <span class="close-modal-level">&times;</span>
            </div>
            <div class="modal-body">
                <div class="notification-icon">
                    <i class="fas <?php echo $levelIcon; ?>" style="color: <?php echo $levelColor; ?>;"></i>
                </div>
                <p class="level-title"><strong>Level Anda: <?php echo $readingInterestLevel; ?></strong></p>
                <p class="level-description"><?php echo $levelDescription; ?></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary close-modal-level">Tutup</button>
            </div>
        </div>
    </div>

    <?php if ($overdueCount > 0): ?>
    <!-- Late Return Alert -->
    <div class="alert-card">
        <div class="alert-icon">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <div class="alert-content">
            <h3>Buku Terlambat Dikembalikan</h3>
            <p>Anda memiliki <?php echo $overdueCount; ?> buku yang melebihi batas waktu peminjaman.</p>
            <div class="overdue-books">
                <?php foreach($overdueBooks as $book): ?>
                    <div class="overdue-item">
                        <div class="overdue-info">
                            <i class="fas fa-book"></i> 
                            <strong><?php echo htmlspecialchars($book['judul']); ?></strong>
                        </div>
                        <div class="overdue-days">
                            Terlambat <?php echo $book['days_overdue']; ?> hari
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <a href="peminjaman.php" class="btn btn-warning">Lihat Detail</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Welcome Card -->
    <div class="dashboard-welcome-card">
        <div class="welcome-container">
            <div class="welcome-info">
                <h2 class="welcome-title">Selamat Datang, <?php echo htmlspecialchars($siswaNama); ?></h2>
                <p class="student-class"><i class="fas fa-graduation-cap"></i> Kelas <?php echo htmlspecialchars($siswaKelas); ?></p>
            </div>
            <div class="student-stats">
                <div class="stat-item">
                    <i class="fas fa-book"></i>
                    <strong><?php echo count($activeLoans); ?></strong>
                    <span>Buku Dipinjam</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-history"></i>
                    <strong><?php echo $totalBooks; ?></strong>
                    <span>Total Dibaca</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Reading Interest Card -->
    <div class="reading-interest-card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-chart-line"></i> Minat Baca</h2>
        </div>
        <div class="reading-interest-content">
            <div class="reading-level">
                <div class="level-info-container">
                    <div class="level-badge" style="background-color: <?php echo $levelColor; ?>;">
                        <i class="fas <?php echo $levelIcon; ?>"></i>
                        <span><?php echo $readingInterestLevel; ?></span>
                    </div>
                    <div class="points-container">
                        <div class="points-header">
                            <span class="points-label">Poin Baca</span>
                            <span class="points-value"><?php echo $readingPoints; ?> Poin</span>
                        </div>
                        <small class="points-info">10 poin per buku</small>
                    </div>
                </div>
            </div>
            
            <div class="progress-container">
                <div class="progress-stats">
                    <div class="progress-info">
                        <span class="progress-percent"><?php echo $readingInterestPercentage; ?>%</span>
                        <span class="progress-label">Minat Baca</span>
                    </div>
                    <div class="progress-counts">
                        <span><?php echo count($uniqueBooks ?? []); ?> dari <?php echo $totalLibraryBooks; ?> buku</span>
                    </div>
                </div>
                
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: <?php echo $readingInterestPercentage; ?>%;"></div>
                </div>
                
                <?php if ($hasNextLevel): ?>
                <div class="next-level-progress">
                    <div class="next-level-info">
                        <span>Level Berikutnya: <strong><?php echo $nextLevel['level']; ?></strong></span>
                        <span>
                            <?php 
                            $pointsForNextLevel = $nextLevel['min_points'] - $readingPoints;
                            $booksNeeded = ceil($pointsForNextLevel / 10);
                            echo $pointsForNextLevel > 0 ? "Butuh $pointsForNextLevel poin lagi ($booksNeeded buku)" : "Sudah mencapai level ini!"; 
                            ?>
                        </span>
                    </div>
                    <div class="progress-bar-container">
                        <?php 
                        $currentLevelPoints = $currentLevel['min_points'];
                        $nextLevelPoints = $nextLevel['min_points'];
                        $pointRange = $nextLevelPoints - $currentLevelPoints;
                        $currentProgress = $readingPoints - $currentLevelPoints;
                        $progressPercentage = min(100, ($currentProgress / $pointRange) * 100);
                        ?>
                        <div class="progress-bar" style="width: <?php echo $progressPercentage; ?>%;"></div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="level-info" style="margin-top: 20px; padding: 15px; background-color: var(--light-color); border-radius: 8px;">
                    <h4 style="margin: 0 0 10px 0; color: var(--primary-color);">Informasi Level</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                        <div class="level-info-item">
                            <i class="fas fa-seedling" style="color: #78C2AD;"></i>
                            <span>Pemula (0 poin)</span>
                        </div>
                        <div class="level-info-item">
                            <i class="fas fa-book-reader" style="color: #6CC3D5;"></i>
                            <span>Pembaca Aktif (50 poin)</span>
                        </div>
                        <div class="level-info-item">
                            <i class="fas fa-graduation-cap" style="color: #3498DB;"></i>
                            <span>Pembaca Terampil (100 poin)</span>
                        </div>
                        <div class="level-info-item">
                            <i class="fas fa-fire" style="color: #F39C12;"></i>
                            <span>Pembaca Ahli (200 poin)</span>
                        </div>
                        <div class="level-info-item">
                            <i class="fas fa-star" style="color: #E74C3C;"></i>
                            <span>Pembaca Profesional (300 poin)</span>
                        </div>
                        <div class="level-info-item">
                            <i class="fas fa-crown" style="color: #9B59B6;"></i>
                            <span>Pembaca Master (500 poin)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reading Levels Information -->
    <div class="reading-levels-info">
        <div class="levels-toggle">
            <button id="toggleLevelsBtn" class="btn btn-sm">
                <i class="fas fa-info-circle"></i> Lihat Semua Level
            </button>
        </div>
        
        <div id="allLevelsContainer" class="levels-container">
            <div class="levels-grid">
                <?php foreach ($readingLevels as $level): ?>
                <div class="level-item" style="border-left-color: <?php echo $level['color']; ?>;">
                    <div class="level-header">
                        <div class="level-icon">
                            <i class="fas <?php echo $level['icon']; ?>" style="color: <?php echo $level['color']; ?>;"></i>
                        </div>
                        <h4><?php echo $level['level']; ?></h4>
                    </div>
                    <p class="level-description"><?php echo $level['description']; ?></p>
                    <div class="level-points">
                        <i class="fas fa-award"></i> Minimal <?php echo $level['min_points']; ?> poin
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
:root {
    --app-bg: #f0f5f1;
    --app-card: #fff;
    --app-shadow: 0 4px 24px rgba(13,110,55,0.10), 0 1.5px 8px rgba(0,0,0,0.08);
    --app-radius: 14px;
    --app-accent: #D4AF37;
    --app-primary: #0D6E37;
    --app-secondary: #1A8C4C;
    --app-transition: 0.25s cubic-bezier(.23,1.01,.32,1);
}
body {
    background: var(--app-bg);
}
.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 18px 8px 32px 8px;
}
.card, .dashboard-welcome-card, .reading-interest-card, .alert-card {
    background: var(--app-card);
    border-radius: var(--app-radius);
    box-shadow: var(--app-shadow);
    margin-bottom: 20px;
    padding: 22px 20px;
    transition: box-shadow var(--app-transition), transform var(--app-transition);
    animation: fadeInUp 0.7s both;
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px);}
    to { opacity: 1; transform: translateY(0);}
}
.card:hover, .dashboard-welcome-card:hover, .reading-interest-card:hover {
    box-shadow: 0 12px 32px rgba(13,110,55,0.18), 0 2px 8px rgba(0,0,0,0.10);
    transform: translateY(-4px) scale(1.012);
}
.dashboard-welcome-card {
    background: linear-gradient(135deg, rgba(13,110,55,0.96), rgba(26,140,76,0.96)), url('https://images.unsplash.com/photo-1464983953574-0892a716854b?auto=format&fit=crop&w=800&q=80') center/cover no-repeat;
    color: #fff !important;
    border-left: 6px solid var(--app-accent);
    position: relative;
    overflow: hidden;
}
.dashboard-welcome-card .welcome-title {
    font-size: 2.1rem;
    font-weight: 800;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
    text-shadow: 0 3px 16px rgba(0,0,0,0.25), 0 1px 0 #222;
    line-height: 1.2;
}
.dashboard-welcome-card .student-class {
    font-size: 1.15rem;
    font-weight: 500;
    text-shadow: 0 2px 8px rgba(0,0,0,0.18);
    margin-bottom: 0;
    opacity: 0.95;
    display: flex;
    align-items: center;
    gap: 6px;
}
.dashboard-welcome-card .student-class i {
    color: var(--app-accent);
    margin-right: 5px;
    font-size: 1.2em;
}
.stat-item i, .level-icon i {
    font-size: 2.1rem !important;
    color: var(--app-accent) !important;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.10));
}
.btn, .btn-secondary {
    font-size: 1.08rem !important;
    border-radius: 8px !important;
    box-shadow: 0 2px 8px rgba(13,110,55,0.08);
    font-weight: 600;
    letter-spacing: 0.02em;
    position: relative;
    overflow: hidden;
    transition: background var(--app-transition), color var(--app-transition), box-shadow var(--app-transition);
}
.btn:active::after {
    content: '';
    position: absolute;
    left: 50%; top: 50%;
    width: 200px; height: 200px;
    background: rgba(255,255,255,0.18);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    pointer-events: none;
    animation: ripple 0.5s;
}
@keyframes ripple {
    from { opacity: 1; }
    to { opacity: 0; }
}
.btn i, .btn-secondary i {
    font-size: 1.1em !important;
    margin-right: 4px;
}
.btn-secondary {
    background: #f8f9fa !important;
    color: var(--text-dark) !important;
    border: 1px solid #dee2e6 !important;
}
.btn-secondary:hover {
    background: #e9ecef !important;
    color: var(--text-dark) !important;
}
.progress-bar-container {
    height: 18px !important;
    border-radius: 10px !important;
    background: #e6f4ea !important;
}
.progress-bar {
    height: 100% !important;
    border-radius: 10px !important;
    background: linear-gradient(90deg, var(--app-primary), var(--app-accent));
    box-shadow: 0 2px 8px rgba(13,110,55,0.10);
    transition: width 1s cubic-bezier(.23,1.01,.32,1);
}
.levels-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 18px;
    margin-top: 10px;
}
.level-item {
    box-shadow: 0 4px 18px rgba(13,110,55,0.10), 0 1px 4px rgba(0,0,0,0.06);
    border-left: 5px solid var(--app-accent) !important;
    transition: box-shadow 0.3s, transform 0.3s;
    background: #fff;
    border-radius: 10px;
    padding: 14px 10px;
    min-width: 0;
}
.level-item:hover {
    box-shadow: 0 10px 32px rgba(13,110,55,0.18), 0 2px 8px rgba(0,0,0,0.10);
    transform: translateY(-3px) scale(1.01);
}
.level-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}
.level-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff !important;
    box-shadow: 0 2px 8px rgba(13,110,55,0.10), 0 1px 4px rgba(0,0,0,0.06);
    border: 1.5px solid rgba(13,110,55,0.10);
    transition: background 0.3s, box-shadow 0.3s;
}
.level-item:hover .level-icon {
    background: #f8f9fa !important;
    box-shadow: 0 4px 16px rgba(13,110,55,0.18);
}
.level-icon i {
    font-size: 1.3em;
}
.level-description {
    color: var(--text-secondary);
    font-size: 0.95em;
    margin-bottom: 8px;
}
.level-points {
    color: var(--app-primary);
    font-size: 0.95em;
    display: flex;
    align-items: center;
    gap: 6px;
}
.toast-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
}
.toast {
    background: #fff;
    padding: 12px 20px;
    border-radius: var(--app-radius);
    box-shadow: var(--app-shadow);
    margin-top: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: fadeInUp 0.4s;
}
.skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
    border-radius: 8px;
    min-height: 24px;
    }
@keyframes loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
input:focus, select:focus, textarea:focus, button:focus {
    outline: 2px solid var(--app-primary);
    outline-offset: 2px;
}
@media (max-width: 768px) {
    .dashboard-container {
        padding: 8px 2px 20px 2px;
    }
    .card {
        margin-bottom: 12px !important;
        padding: 12px 6px !important;
    }
    .search-toggle-btn {
        width: 100%;
        justify-content: center;
    }
    .search-filter-form .form-group {
        flex-direction: column;
    gap: 8px;
    }
    .dashboard-welcome-card .welcome-title {
        font-size: 1.25rem !important;
}
    .dashboard-welcome-card .student-class {
        font-size: 1rem !important;
    }
}

/* Search section styles */
.search-section {
    display: none;
    transition: all 0.4s cubic-bezier(.23,1.01,.32,1);
    opacity: 0;
    margin-bottom: 0 !important;
    max-height: 0;
    overflow: hidden;
}

.search-section.show {
    display: block;
    opacity: 1;
    margin-bottom: 18px !important;
    max-height: 800px;
}

.search-section.hide {
    display: none;
    opacity: 0;
    margin-bottom: 0 !important;
    max-height: 0;
    }

/* Input dengan ikon dan efek focus */
.search-filter-form input[type='text'] {
    padding-left: 36px;
    background: #f8f9fa url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/icons/search.svg') 10px 50% no-repeat;
    background-size: 18px 18px;
    border: 1.5px solid #e1e8ed;
    transition: border-color 0.2s, box-shadow 0.2s;
    }
.search-filter-form input[type='text']:focus {
    border-color: var(--app-primary);
    box-shadow: 0 0 0 3px rgba(13,110,55,0.10);
    background-color: #fff;
    }

/* Tombol ripple */
.btn:active::after {
    content: '';
    position: absolute;
    left: 50%; top: 50%;
    width: 200px; height: 200px;
    background: rgba(255,255,255,0.18);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    pointer-events: none;
    animation: ripple 0.5s;
    }
@keyframes ripple {
    from { opacity: 1; }
    to { opacity: 0; }
}

/* Skeleton loading */
.skeleton-row {
    height: 38px;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
    border-radius: 6px;
    margin-bottom: 8px;
    }
@keyframes loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
    }

/* Highlight baris hasil */
.table tr {
    transition: background 0.2s;
    }
.table tr:hover {
    background: #e6f4ea !important;
}

/* Transisi hasil pencarian */
.search-results {
    animation: fadeInUp 0.5s;
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px);}
    to { opacity: 1; transform: translateY(0);}
}

/* Modern Search Toggle Button */
.search-toggle-container {
    margin-bottom: 20px;
    }

.search-toggle-btn {
    background: linear-gradient(135deg, var(--app-primary), var(--app-secondary));
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(13,110,55,0.15);
}

.search-toggle-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(13,110,55,0.2);
}

.search-toggle-btn i {
    font-size: 1.2em;
    transition: transform 0.3s ease;
}

.search-toggle-btn:hover i {
    transform: scale(1.1);
}

/* Modern Search Card */
.search-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 8px 30px rgba(13,110,55,0.1);
    overflow: hidden;
    transition: all 0.3s ease;
}

.search-header {
    background: linear-gradient(135deg, var(--app-primary), var(--app-secondary));
    color: white;
    padding: 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    }

.search-header h3 {
    margin: 0;
    font-size: 1.4rem;
    display: flex;
    align-items: center;
    gap: 10px;
    }

.search-header h3 i {
    font-size: 1.2em;
}

/* Search Form Styling */
.search-filter-form {
    padding: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.search-input-wrapper {
    position: relative;
    flex: 1;
}

.search-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--app-primary);
    font-size: 1.2em;
}

.search-input-wrapper input {
        width: 100%;
    padding: 16px;
    border: 2px solid #e1e8ed;
    border-radius: 12px;
        font-size: 1.1rem;
    transition: all 0.3s ease;
    }

.search-input-wrapper input:focus {
    border-color: var(--app-primary);
    box-shadow: 0 0 0 4px rgba(13,110,55,0.1);
}

.search-actions {
    display: flex;
    gap: 12px;
    margin-top: 10px;
}

.btn-search {
    background: linear-gradient(135deg, var(--app-primary), var(--app-secondary));
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-search:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(13,110,55,0.2);
}

.btn-reset {
    background: #f8f9fa;
    color: var(--text-dark);
    border: 2px solid #e1e8ed;
    padding: 12px 24px;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-reset:hover {
    background: #e9ecef;
    transform: translateY(-2px);
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .search-toggle-btn {
        width: 100%;
        justify-content: center;
    }
    
    .search-actions {
        flex-direction: column;
    }
    
    .btn-search, .btn-reset {
        width: 100%;
        justify-content: center;
    }
}

/* Search Results Styling */
.search-results {
    margin-top: 20px;
    padding: 20px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.search-results h4 {
    color: var(--app-primary);
    margin-bottom: 15px;
    font-size: 1.2rem;
}

.table-responsive {
    width: 100%;
    overflow-x: auto;
    overflow-y: auto;
    max-height: 90vh;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(13,110,55,0.06);
}
@media (min-width: 992px) {
    .table-responsive {
        max-height: none;
}
}
.table th {
    position: sticky;
    top: 0;
    z-index: 2;
    }
    .table td[data-label="Aksi"] {
        white-space: nowrap;
}
.stock-low {
    background-color: #fff3f3;
    }
.stock-good {
    background-color: #f0fff4;
    }
.show-more-container {
    margin-top: 20px;
    text-align: center;
    }
.show-more-btn {
    background: #f8f9fa;
    border: 1px solid #e1e8ed;
    padding: 8px 16px;
    border-radius: 8px;
    color: var(--text-dark);
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
}
.show-more-btn:hover {
    background: #e9ecef;
    }
.results-count {
    display: block;
    margin-top: 10px;
    color: var(--text-secondary);
    font-size: 0.9rem;
    }
.no-results {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-secondary);
}
.no-results i {
    font-size: 3rem;
    color: #e1e8ed;
    margin-bottom: 15px;
}
.no-results p {
    font-size: 1.1rem;
    margin: 0;
}
@media (max-width: 768px) {
    .table-responsive {
        overflow-x: auto;
    }
    
    .table td {
        white-space: nowrap;
    }

    .table td[data-label] {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 12px;
    }

    .table td[data-label]::before {
        content: attr(data-label);
        font-weight: 600;
        margin-right: 10px;
    }
}

/* Modal Animation */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    animation: fadeIn 0.3s ease;
}
.modal-content {
    background-color: #fff;
    margin: 10% auto;
    padding: 0;
    border-radius: 16px;
    max-width: 500px;
    width: 90%;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    animation: slideIn 0.3s ease;
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes slideIn {
    from { transform: translateY(-20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
.modal-header {
    background: linear-gradient(135deg, var(--app-primary), var(--app-secondary));
    color: white;
    padding: 20px;
    border-radius: 16px 16px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-header h3 {
    margin: 0;
    font-size: 1.4rem;
    display: flex;
    align-items: center;
    gap: 10px;
}
.close-modal {
    color: white;
    font-size: 1.8rem;
    font-weight: bold;
    cursor: pointer;
    transition: transform 0.3s ease;
}
.close-modal:hover {
    transform: scale(1.1);
}
.modal-body {
    padding: 30px 20px;
    text-align: center;
}
.notification-icon {
    font-size: 3rem;
    color: var(--app-primary);
    margin-bottom: 20px;
}
.book-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 15px;
}
.notification-message {
    color: var(--text-secondary);
    margin-bottom: 25px;
    line-height: 1.5;
}
.notification-details {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 15px;
    margin-top: 20px;
}
.detail-item {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    color: var(--text-dark);
}
.detail-item:last-child {
    margin-bottom: 0;
}
.detail-item i {
    color: var(--app-primary);
    font-size: 1.2em;
}
.modal-footer {
    padding: 15px 20px;
    text-align: right;
    border-top: 1px solid #e1e8ed;
    }
.modal-footer .btn {
    min-width: 100px;
    }
@media (max-width: 768px) {
    .modal-content {
        margin: 20% auto;
        width: 95%;
    }
    .modal-header h3 {
        font-size: 1.2rem;
    }
    .notification-icon {
        font-size: 2.5rem;
    }
    .book-title {
    font-size: 1.1rem;
    }
    }

.filter-group {
        display: flex;
    gap: 20px;
    flex-wrap: wrap;
}
.filter-item {
    flex: 1;
    min-width: 200px;
}
.filter-item label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    color: var(--text-dark);
    font-weight: 500;
}
.filter-item label i {
    color: var(--app-primary);
}
.filter-item select {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e1e8ed;
    border-radius: 12px;
    font-size: 1rem;
    background-color: white;
    cursor: pointer;
    transition: all 0.3s ease;
}
.filter-item select:focus {
    border-color: var(--app-primary);
    box-shadow: 0 0 0 4px rgba(13,110,55,0.1);
}
@media (max-width: 768px) {
    .filter-group {
        flex-direction: column;
    }
    .filter-item {
        width: 100%;
    }
}

/* Mobile-friendly stacked table */
@media (max-width: 768px) {
    .table thead {
        display: none;
    }
    .table, .table tbody, .table tr, .table td {
        display: block;
        width: 100%;
    }
    .table tr {
        margin-bottom: 16px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(13,110,55,0.06);
        background: #fff;
        padding: 8px 0;
    }
    .table td {
        padding: 8px 16px;
        text-align: left;
        position: relative;
    }
    .table td::before {
        content: attr(data-label);
        font-weight: bold;
        color: #0D6E37;
        display: block;
        margin-bottom: 2px;
    }
}
</style>

<script>
function showBorrowNotification(bookTitle) {
    const modal = document.getElementById('borrowModal');
    const bookTitleElement = modal.querySelector('.book-title');
    bookTitleElement.textContent = bookTitle;
    modal.style.display = 'block';
    }

function showLevelInfoModal() {
    const modal = document.getElementById('levelInfoModal');
    if (modal) {
        modal.style.display = 'block';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all elements
    const modal = document.getElementById('borrowModal');
    const toggleBtn = document.getElementById('searchToggleBtn');
    const searchSection = document.getElementById('searchSection');
    const levelsToggleBtn = document.getElementById('toggleLevelsBtn');
    const levelsContainer = document.getElementById('allLevelsContainer');
    const closeButtons = document.getElementsByClassName('close-modal');
    
    // Force search section to be closed initially
    searchSection.style.display = 'none';
    searchSection.classList.add('hide');
    searchSection.classList.remove('show');
    
    // Show borrow information popup ONLY once per session/tab
    if (modal && !sessionStorage.getItem('borrowModalShown')) {
        setTimeout(() => {
            modal.style.display = 'block';
            sessionStorage.setItem('borrowModalShown', '1');
        }, 500);
    }

    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(tooltip => {
        new Tooltip(tooltip);
    });
    
    // Search toggle functionality
    toggleBtn.addEventListener('click', function() {
        if (searchSection.classList.contains('show')) {
            searchSection.style.display = 'none';
            searchSection.classList.remove('show');
            searchSection.classList.add('hide');
            toggleBtn.innerHTML = '<i class="fas fa-search"></i> Cari Buku';
        } else {
            searchSection.style.display = 'block';
            searchSection.classList.remove('hide');
            searchSection.classList.add('show');
            toggleBtn.innerHTML = '<i class="fas fa-times"></i> Tutup Pencarian';
        }
    });

    // Reading levels toggle functionality
    levelsToggleBtn.addEventListener('click', function() {
        if (levelsContainer.style.display === 'none') {
            levelsContainer.style.display = 'block';
            setTimeout(() => {
                levelsContainer.style.opacity = '1';
                levelsContainer.style.transform = 'translateY(0)';
            }, 10);
            levelsToggleBtn.innerHTML = '<i class="fas fa-times-circle"></i> Sembunyikan Level';
        } else {
            levelsContainer.style.opacity = '0';
            levelsContainer.style.transform = 'translateY(-10px)';
            setTimeout(() => {
            levelsContainer.style.display = 'none';
            }, 300);
            levelsToggleBtn.innerHTML = '<i class="fas fa-info-circle"></i> Lihat Semua Level';
        }
    });

    // Set initial button state
    toggleBtn.innerHTML = '<i class="fas fa-search"></i> Cari Buku';

    // Close modal when clicking close buttons
    Array.from(closeButtons).forEach(button => {
        button.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Add new functionality
    // Toast notification system
    window.showToast = function(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'times-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;
        
        const container = document.querySelector('.toast-container') || (() => {
            const div = document.createElement('div');
            div.className = 'toast-container';
            document.body.appendChild(div);
            return div;
        })();
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };

    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                
                const inputs = this.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    if (!input.checkValidity()) {
                        input.classList.add('is-invalid');
                        showToast(input.validationMessage, 'error');
                    }
                });
            }
            this.classList.add('was-validated');
    });

    // Show level info popup ONLY after login (justLoggedIn flag)
    if (document.getElementById('levelInfoModal') && sessionStorage.getItem('justLoggedIn')) {
        setTimeout(() => {
            showLevelInfoModal();
            sessionStorage.removeItem('justLoggedIn');
        }, 800);
    }

    // Close level info modal
    const closeLevelBtns = document.querySelectorAll('.close-modal-level');
    const levelInfoModal = document.getElementById('levelInfoModal');
    closeLevelBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            levelInfoModal.style.display = 'none';
        });
    });
    window.addEventListener('click', function(event) {
        if (event.target === levelInfoModal) {
            levelInfoModal.style.display = 'none';
        }
    });
});

    // Reading level progress animation
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0';
        setTimeout(() => {
            bar.style.width = width;
        }, 100);
    });

    // Check if we should show search results
    const urlParams = new URLSearchParams(window.location.search);
    const searchValue = urlParams.get('search') || '';
    const kategoriValue = urlParams.get('kategori') || '';
    const searchResults = document.querySelector('.search-results');

    // Hanya buka form jika ada parameter pencarian YANG TIDAK KOSONG dan ada hasil
    if (
        ((searchValue && searchValue.trim() !== '') || (kategoriValue && kategoriValue !== '' && kategoriValue !== '0'))
        && searchResults
    ) {
        searchSection.style.display = 'block';
        searchSection.classList.remove('hide');
        searchSection.classList.add('show');
        toggleBtn.innerHTML = '<i class="fas fa-times"></i> Tutup Pencarian';
    }
});

// Tooltip class
class Tooltip {
    constructor(element) {
        this.element = element;
        this.tooltip = null;
        this.init();
    }

    init() {
        this.element.addEventListener('mouseenter', this.show.bind(this));
        this.element.addEventListener('mouseleave', this.hide.bind(this));
    }

    show() {
        const text = this.element.getAttribute('data-tooltip');
        this.tooltip = document.createElement('div');
        this.tooltip.className = 'tooltip';
        this.tooltip.textContent = text;
        document.body.appendChild(this.tooltip);
        
        const rect = this.element.getBoundingClientRect();
        this.tooltip.style.top = rect.bottom + 5 + 'px';
        this.tooltip.style.left = rect.left + (rect.width - this.tooltip.offsetWidth) / 2 + 'px';
        
        setTimeout(() => this.tooltip.classList.add('show'), 10);
    }

    hide() {
        if (this.tooltip) {
            this.tooltip.classList.remove('show');
            setTimeout(() => this.tooltip.remove(), 300);
        }
    }
}

function loadMoreBooks(newLimit) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('limit', newLimit);
    
    // Pastikan search, kategori, stok tetap ada walau kosong
    if (!urlParams.has('search')) urlParams.set('search', '');
    if (!urlParams.has('kategori')) urlParams.set('kategori', '');
    if (!urlParams.has('stok')) urlParams.set('stok', '');

    window.location.href = window.location.pathname + '?' + urlParams.toString();
}

// Skeleton loading saat submit pencarian
if (document.querySelector('.search-filter-form')) {
    document.querySelector('.search-filter-form').addEventListener('submit', function(e) {
        const results = document.querySelector('.search-results');
        if (results) {
            results.innerHTML = '';
            for (let i = 0; i < 3; i++) {
                const skel = document.createElement('div');
                skel.className = 'skeleton-row';
                results.appendChild(skel);
            }
        }
    });
}
// Toast feedback jika tidak ada hasil
if (document.querySelector('.search-results .no-results')) {
    setTimeout(() => {
        showToast('Tidak ada buku yang ditemukan.', 'info');
    }, 400);
}
</script>

<?php include_once 'includes/footer.php'; ?> 