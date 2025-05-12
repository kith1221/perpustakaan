<?php
// Set page title
$pageTitle = 'Kelola Peminjaman Buku';

// Include header
require_once 'includes/header.php';

// Process return book action
if (isset($_GET['return']) && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Get loan details to check if already returned
    $query = "SELECT p.*, b.judul as buku_judul, b.jumlah_buku as current_stock, DATE_ADD(p.tanggal_pinjam, INTERVAL p.lama_pinjam DAY) as tanggal_jatuh_tempo, s.nama as siswa_nama 
              FROM peminjaman p
              JOIN buku b ON p.buku_id = b.id
              JOIN siswa s ON p.siswa_id = s.id 
              WHERE p.id = $id";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $loan = mysqli_fetch_assoc($result);
        
        // Check if the book is already returned
        if ($loan['status'] === 'dikembalikan') {
            $_SESSION['alert'] = 'Buku ini sudah dikembalikan sebelumnya';
            $_SESSION['alert_type'] = 'warning';
            redirect('peminjaman.php');
            exit;
        }
        
        // Get book ID from loan to update stock later
        $buku_id = $loan['buku_id'];
        $buku_judul = $loan['buku_judul'];
        $current_stock = $loan['current_stock'];
        $siswa_nama = $loan['siswa_nama'];
        
        // Check if overdue
        $is_late = false;
        $jatuh_tempo = new DateTime($loan['tanggal_jatuh_tempo']);
        $today = new DateTime(date('Y-m-d'));
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            if ($today > $jatuh_tempo) {
                $is_late = true;
                // Calculate days overdue
                $diff = $today->diff($jatuh_tempo);
                $days_late = $diff->days;
                
                // Update loan status to returned with late status
                $query = "UPDATE peminjaman SET 
                          status = 'dikembalikan', 
                          tanggal_kembali = CURDATE(), 
                          keterlambatan = $days_late 
                          WHERE id = $id";
                
                if (!mysqli_query($conn, $query)) {
                    throw new Exception(mysqli_error($conn));
                }
                
                // Restore book stock
                $query_restore_stock = "UPDATE buku SET jumlah_buku = jumlah_buku + 1 WHERE id = $buku_id";
                if (!mysqli_query($conn, $query_restore_stock)) {
                    throw new Exception(mysqli_error($conn));
                }
                
                // Log the stock change
                $admin_id = $_SESSION['user_id'] ?? 0;
                $log_message = "Stok buku '$buku_judul' ditambahkan (1) karena dikembalikan oleh $siswa_nama dengan status TERLAMBAT ($days_late hari). Stok sebelumnya: $current_stock, stok terbaru: " . ($current_stock + 1);
                $query_log = "INSERT INTO activity_log (user_id, activity_type, details, created_at) 
                              VALUES ($admin_id, 'stock_update', '" . mysqli_real_escape_string($conn, $log_message) . "', NOW())";
                mysqli_query($conn, $query_log);
                
                // Commit transaction
                mysqli_commit($conn);
                
                $_SESSION['alert'] = "Buku berhasil dikembalikan dengan status: TERLAMBAT ($days_late hari)";
                $_SESSION['alert_type'] = 'warning';
            } else {
                // Not late - normal return
                $query = "UPDATE peminjaman SET 
                          status = 'dikembalikan', 
                          tanggal_kembali = CURDATE(),
                          keterlambatan = 0  
                          WHERE id = $id";
                
                if (!mysqli_query($conn, $query)) {
                    throw new Exception(mysqli_error($conn));
                }
                
                // Restore book stock
                $query_restore_stock = "UPDATE buku SET jumlah_buku = jumlah_buku + 1 WHERE id = $buku_id";
                if (!mysqli_query($conn, $query_restore_stock)) {
                    throw new Exception(mysqli_error($conn));
                }
                
                // Log the stock change
                $admin_id = $_SESSION['user_id'] ?? 0;
                $log_message = "Stok buku '$buku_judul' ditambahkan (1) karena dikembalikan tepat waktu oleh $siswa_nama. Stok sebelumnya: $current_stock, stok terbaru: " . ($current_stock + 1);
                $query_log = "INSERT INTO activity_log (user_id, activity_type, details, created_at) 
                              VALUES ($admin_id, 'stock_update', '" . mysqli_real_escape_string($conn, $log_message) . "', NOW())";
                mysqli_query($conn, $query_log);
                
                // Commit transaction
                mysqli_commit($conn);
                
                $_SESSION['alert'] = 'Buku berhasil dikembalikan tepat waktu';
                $_SESSION['alert_type'] = 'success';
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $_SESSION['alert'] = 'Gagal mengembalikan buku: ' . $e->getMessage();
            $_SESSION['alert_type'] = 'danger';
        }
    } else {
        $_SESSION['alert'] = 'Data peminjaman tidak ditemukan';
        $_SESSION['alert_type'] = 'danger';
    }
    
    // Redirect to remove the GET parameter
    redirect('peminjaman.php');
}

// Get search parameters
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$searchType = isset($_GET['search_type']) ? $_GET['search_type'] : 'nama';

// Determine filter condition
$filterCondition = '';
if (isset($_GET['filter']) && $_GET['filter'] === 'active') {
    $filterCondition = "WHERE p.status = 'dipinjam'";
    
    // Additional filter for late books only
    if (isset($_GET['late']) && $_GET['late'] === 'true') {
        $filterCondition .= " AND DATE_ADD(p.tanggal_pinjam, INTERVAL p.lama_pinjam DAY) < CURDATE()";
    }
} elseif (isset($_GET['filter']) && $_GET['filter'] === 'returned') {
    $filterCondition = "WHERE p.status = 'dikembalikan'";
}

// Add search condition if search is provided
$searchCondition = '';
if (!empty($search)) {
    // If we already have a WHERE clause, add AND
    $connector = empty($filterCondition) ? 'WHERE' : 'AND';
    
    switch ($searchType) {
        case 'nama':
            $searchCondition = "$connector s.nama LIKE '%$search%'";
            break;
        case 'nisn':
            $searchCondition = "$connector s.nisn LIKE '%$search%'";
            break;
        case 'kelas':
            $searchCondition = "$connector s.kelas LIKE '%$search%'";
            break;
        default:
            $searchCondition = "$connector s.nama LIKE '%$search%'";
    }
}

// Get all loans with filter and search
$query = "SELECT p.*, s.nama as siswa_nama, s.kelas as siswa_kelas, s.nisn as siswa_nisn, b.judul as buku_judul 
          FROM peminjaman p 
          JOIN siswa s ON p.siswa_id = s.id 
          JOIN buku b ON p.buku_id = b.id 
          $filterCondition
          $searchCondition
          ORDER BY p.created_at DESC";
$result = mysqli_query($conn, $query);
$loans = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $loans[] = $row;
    }
}


?><!-- Filter and Add Button -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-filter"></i> Filter Peminjaman</h2>
    </div>
    
    <div class="filter-container" style="padding: 1rem; background-color: #f9f9f9; border-radius: 4px; margin-bottom: 1rem;">
        <div class="filter-row" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
            <div>
                <a href="peminjaman.php" class="btn <?php echo !isset($_GET['filter']) ? 'btn-secondary' : ''; ?>" style="margin-right: 5px;">Semua</a>
                <a href="peminjaman.php?filter=active<?php echo !empty($search) ? '&search=' . urlencode($search) . '&search_type=' . $searchType : ''; ?>" 
                   class="btn <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'active' && !isset($_GET['late'])) ? 'btn-secondary' : ''; ?>" 
                   style="margin-right: 5px;">Dipinjam</a>
                <a href="peminjaman.php?filter=active&late=true<?php echo !empty($search) ? '&search=' . urlencode($search) . '&search_type=' . $searchType : ''; ?>" 
                   class="btn <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'active' && isset($_GET['late']) && $_GET['late'] === 'true') ? 'btn-secondary' : ''; ?>" 
                   style="margin-right: 5px; background-color: <?php echo (isset($_GET['late']) && $_GET['late'] === 'true') ? '#DC3545' : ''; ?>">
                   <i class="fas fa-exclamation-triangle"></i> Terlambat
                </a>
                <a href="peminjaman.php?filter=returned<?php echo !empty($search) ? '&search=' . urlencode($search) . '&search_type=' . $searchType : ''; ?>" 
                   class="btn <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'returned') ? 'btn-secondary' : ''; ?>">Dikembalikan</a>
            </div>
            <div style="margin-left: auto;">
                <a href="peminjaman_tambah.php" class="btn">Tambah Peminjaman</a>
            </div>
        </div>
    </div>
</div>

<!-- Search Form -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-search"></i> Pencarian</h2>
    </div>
    
    <div class="filter-container" style="padding: 1rem; background-color: #f9f9f9; border-radius: 4px; margin-bottom: 1rem;">
        <form action="" method="get" class="filter-form">
            <div class="filter-row" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
                <!-- Preserve existing filter if any -->
                <?php if (isset($_GET['filter'])): ?>
                    <input type="hidden" name="filter" value="<?php echo $_GET['filter']; ?>">
                <?php endif; ?>
                
                <div class="filter-item" style="flex: 1;">
                    <label for="search" style="display: block; margin-bottom: 5px; font-weight: 500;">Cari:</label>
                    <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Cari siswa..." style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                </div>
                
                <div class="filter-item">
                    <label for="search_type" style="display: block; margin-bottom: 5px; font-weight: 500;">Berdasarkan:</label>
                    <select name="search_type" id="search_type" style="padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                        <option value="nama" <?php echo $searchType === 'nama' ? 'selected' : ''; ?>>Nama Siswa</option>
                        <option value="nisn" <?php echo $searchType === 'nisn' ? 'selected' : ''; ?>>NISN</option>
                        <option value="kelas" <?php echo $searchType === 'kelas' ? 'selected' : ''; ?>>Kelas</option>
                    </select>
                </div>
                
                <div class="button-container">
                    <button type="submit" class="btn" style="margin-right: 5px;">Cari</button>
                    <a href="peminjaman.php<?php echo isset($_GET['filter']) ? '?filter=' . $_GET['filter'] : ''; ?>" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Search Results Info -->
<?php if (!empty($search)): ?>
    <div class="filter-info" style="padding: 0.5rem 1rem; background-color: #e9f7ef; border-radius: 4px; margin-bottom: 1rem;">
        <p style="margin: 0; font-size: 0.9rem;">
            <i class="fas fa-search"></i> Hasil pencarian untuk 
            <strong><?php echo htmlspecialchars($search); ?></strong> 
            pada 
            <strong>
                <?php 
                switch ($searchType) {
                    case 'nama': echo 'Nama Siswa'; break;
                    case 'nisn': echo 'NISN'; break;
                    case 'kelas': echo 'Kelas'; break;
                    default: echo 'Nama Siswa';
                }
                ?>
            </strong>: 
            ditemukan <?php echo count($loans); ?> hasil
        </p>
    </div>
<?php endif; ?>

<!-- Loans Table -->
<div class="card">
    <?php if (empty($loans)): ?>
        <div style="padding: 20px; text-align: center;">
            <i class="fas fa-info-circle" style="font-size: 24px; color: #6c757d; margin-bottom: 10px;"></i>
            <p>Tidak ada data peminjaman<?php echo !empty($search) ? ' untuk pencarian yang dilakukan' : ''; ?>.</p>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Siswa</th>
                        <th>NISN</th>
                        <th>Kelas</th>
                        <th>Buku</th>
                        <th>Tanggal Pinjam</th>
                        <th>Lama Pinjam</th>
                        <th>Status</th>
                        <th>Tanggal Kembali</th>
                        <th>Keterlambatan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loans as $loan): ?>
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
                            <td><?php echo $loan['id']; ?></td>
                            <td><?php echo htmlspecialchars($loan['siswa_nama']); ?></td>
                            <td><?php echo htmlspecialchars($loan['siswa_nisn']); ?></td>
                            <td><?php echo htmlspecialchars($loan['siswa_kelas']); ?></td>
                            <td><?php echo htmlspecialchars($loan['buku_judul']); ?></td>
                            <td><?php echo formatDate($loan['tanggal_pinjam']); ?></td>
                            <td><?php echo $loan['lama_pinjam']; ?> hari</td>
                            <td>
                                <?php if ($loan['status'] == 'dipinjam'): ?>
                                    <?php if ($is_currently_overdue): ?>
                                        <span style="color: #e53935; font-weight: bold;">Terlambat</span>
                                    <?php else: ?>
                                        <span style="color: #ff9800; font-weight: bold;">Dipinjam</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #4CAF50; font-weight: bold;">Dikembalikan</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    if (!empty($loan['tanggal_kembali'])) {
                                        echo formatDate($loan['tanggal_kembali']);
                                    } else {
                                        // Show expected return date for books not returned yet
                                        echo '<small>Estimasi: ' . formatDate(date('Y-m-d', strtotime($loan['tanggal_pinjam'] . ' + ' . $loan['lama_pinjam'] . ' days'))) . '</small>';
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
                                        -
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if (isset($loan['keterlambatan']) && $loan['keterlambatan'] > 0): ?>
                                        <span style="color: #e53935; font-weight: bold;"><?php echo $loan['keterlambatan']; ?> hari</span>
                                    <?php else: ?>
                                        <span style="color: #4CAF50; font-weight: bold;">Tepat waktu</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($loan['status'] == 'dipinjam'): ?>
                                    <a href="peminjaman.php?return=1&id=<?php echo $loan['id']; ?>" 
                                       class="btn btn-success btn-sm return-book"
                                       onclick="return confirmReturn(event)">
                                        Kembalikan
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
    /* Filter and Search styles */
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

    .filter-form select,
    .filter-form input[type="text"] {
        width: 100%;
    }

    .filter-form select:focus,
    .filter-form input[type="text"]:focus {
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
    }

    .button-container .btn-secondary {
        background-color: #f8f9fa;
        color: #495057;
        border: 1px solid #dee2e6;
    }

    .button-container .btn:hover {
        opacity: 0.9;
    }

    .button-container .btn-secondary:hover {
        background-color: #e9ecef;
        border-color: #dee2e6;
    }

    /* Responsive adjustments */
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
</style>

<script>
function confirmReturn(event) {
    event.preventDefault();
    
    Swal.fire({
        title: 'Konfirmasi Pengembalian',
        html: '<div style="text-align: left; margin-bottom: 15px;">' +
              '<div style="background-color: #fff3cd; border: 1px solid #ffeeba; border-radius: 4px; padding: 10px; margin-bottom: 15px;">' +
              '<p style="color: #856404; margin: 0;"><i class="fas fa-info-circle"></i> Harap cek kondisi buku terlebih dahulu sebelum mengkonfirmasi pengembalian. Centang semua kondisi di bawah untuk melanjutkan.</p>' +
              '</div>' +
              '<p><i class="fas fa-exclamation-triangle" style="color: #f1c40f;"></i> Kondisi buku yang harus diperiksa:</p>' +
              '<ul style="list-style-type: none; padding-left: 20px;">' +
              '<li><input type="checkbox" id="check1" class="condition-check" style="margin-right: 8px;">Tidak ada halaman yang rusak</li>' +
              '<li><input type="checkbox" id="check2" class="condition-check" style="margin-right: 8px;">Tidak ada coretan</li>' +
              '<li><input type="checkbox" id="check3" class="condition-check" style="margin-right: 8px;">Sampul dalam kondisi baik</li>' +
              '</ul>' +
              '</div>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Kembalikan',
        cancelButtonText: 'Batal',
        allowOutsideClick: false,
        didOpen: () => {
            // Disable confirm button initially
            Swal.getConfirmButton().disabled = true;
            
            // Add event listeners to checkboxes
            document.querySelectorAll('.condition-check').forEach(checkbox => {
                checkbox.addEventListener('change', () => {
                    const allChecked = Array.from(document.querySelectorAll('.condition-check')).every(cb => cb.checked);
                    Swal.getConfirmButton().disabled = !allChecked;
                });
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Show success notification before redirecting
            Swal.fire({
                title: 'Berhasil!',
                text: 'Buku telah berhasil dikembalikan',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = event.target.href;
            });
        }
    });
    
    return false;
}
</script>

<?php
// Include footer
require_once 'includes/footer.php';

require_once 'includes/footer.php';
