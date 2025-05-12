<?php
// Set page title
$pageTitle = 'Riwayat Peminjaman';

// Include header
require_once 'includes/header.php';

// Get loan history
$loanHistory = getLoanHistory($_SESSION['user_id']);

// Count statistics
$totalBooks = 0;
$returnedBooks = 0;
$activeBooks = 0;

if (!empty($loanHistory)) {
    $totalBooks = count($loanHistory);
    foreach ($loanHistory as $loan) {
        if ($loan['status'] == 'dikembalikan') {
            $returnedBooks++;
        } else {
            $activeBooks++;
        }
    }
}
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-history"></i> Riwayat Peminjaman Buku</h2>
        </div>
        
        <?php if (empty($loanHistory)): ?>
            <div class="empty-state">
                <i class="fas fa-history empty-icon"></i>
                <p>Belum ada riwayat peminjaman buku.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Judul Buku</th>
                            <th>Tanggal Pinjam</th>
                            <th>Lama Peminjaman</th>
                            <th>Tanggal Kembali</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loanHistory as $loan): ?>
                            <tr>
                                <td data-label="Judul Buku">
                                    <i class="fas fa-book text-primary"></i> 
                                    <?php echo htmlspecialchars($loan['judul']); ?>
                                </td>
                                <td data-label="Tanggal Pinjam">
                                    <i class="fas fa-calendar"></i> 
                                    <?php echo formatDate($loan['tanggal_pinjam']); ?>
                                </td>
                                <td data-label="Lama Peminjaman">
                                    <i class="fas fa-clock"></i> 
                                    <?php echo $loan['lama_pinjam']; ?> hari
                                </td>
                                <td data-label="Tanggal Kembali">
                                    <i class="fas fa-calendar-day"></i> 
                                    <?php
                                    if ($loan['tanggal_kembali']) {
                                        echo formatDate($loan['tanggal_kembali']);
                                    } else {
                                        $returnDate = date('Y-m-d', strtotime($loan['tanggal_pinjam'] . ' + ' . $loan['lama_pinjam'] . ' days'));
                                        echo formatDate($returnDate) . ' (Estimasi)';
                                    }
                                    ?>
                                </td>
                                <td data-label="Status">
                                    <?php if ($loan['status'] === 'dikembalikan'): ?>
                                        <span class="status-badge active">
                                            <i class="fas fa-check-circle"></i> Dikembalikan
                                        </span>
                                    <?php elseif ($loan['status'] === 'dipinjam'): ?>
                                        <span class="status-badge overdue">
                                            <i class="fas fa-book-reader"></i> Dipinjam
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Statistics Card -->
<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-chart-pie"></i> Statistik Peminjaman</h2>
    </div>
    <div class="card-body">
        <div class="stats-container">
            <div class="stat-item">
                <div class="stat-details">
                    <h3><?php echo $totalBooks; ?></h3>
                    <p>Total Buku Dipinjam</p>
                    <small style="color: #666; display: block; margin-top: 5px;">Total keseluruhan buku yang pernah Anda pinjam</small>
                </div>
            </div>
            
            <div class="stat-item">
                <div class="stat-details">
                    <h3><?php echo $returnedBooks; ?></h3>
                    <p>Buku Dikembalikan</p>
                    <small style="color: #666; display: block; margin-top: 5px;">Buku yang sudah Anda kembalikan ke perpustakaan</small>
                </div>
            </div>
            
            <div class="stat-item">
                <div class="stat-details">
                    <h3><?php echo $activeBooks; ?></h3>
                    <p>Buku Aktif</p>
                    <small style="color: #666; display: block; margin-top: 5px;">Buku yang sedang Anda pinjam dan belum dikembalikan</small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
}

.card-header {
    padding: 15px 20px;
    border-bottom: 1px solid #e9ecef;
}

.card-title {
    margin: 0;
    font-size: 1.1rem;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 8px;
}

.card-title i {
    color: var(--primary-color);
}

.table-responsive {
    width: 100%;
    margin: 0;
    padding: 0;
    overflow-x: hidden;
}

.table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
}

.table th,
.table td {
    padding: 12px 20px;
    text-align: left;
    border-bottom: 1px solid #e9ecef;
    word-break: break-word;
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: var(--text-primary);
    white-space: nowrap;
}

.table td i {
    margin-right: 8px;
    width: 16px;
    text-align: center;
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 500;
    white-space: nowrap;
}

.status-badge.overdue {
    background-color: #fff3f3;
    color: #dc3545;
    border: 1px solid #ffcdd2;
}

.status-badge.active {
    background-color: #f0fff0;
    color: #28a745;
    border: 1px solid #c3e6cb;
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
    color: var(--primary-color);
    opacity: 0.5;
    margin-bottom: 15px;
}

.empty-state p {
    color: var(--text-secondary);
    margin: 0;
}

/* Mobile Styles */
@media (max-width: 768px) {
    .container {
        padding: 15px;
    }

    .card {
        border-radius: 0;
        margin: 0 -15px;
    }

    .card-header {
        padding: 12px 15px;
    }

    .card-title {
        font-size: 1rem;
    }

    .table, 
    .table tbody, 
    .table tr, 
    .table td {
        display: block;
        width: 100%;
    }

    .table thead {
        display: none;
    }

    .table tr {
        margin-bottom: 15px;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        background-color: #fff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 15px;
        border-bottom: 1px solid #e9ecef;
        text-align: right;
        min-height: 44px;
    }

    .table td:last-child {
        border-bottom: none;
    }

    .table td:before {
        content: attr(data-label);
        font-weight: 600;
        color: var(--text-primary);
        text-align: left;
        padding-right: 10px;
        min-width: 120px;
        flex-shrink: 0;
    }

    .table td[data-label="Judul Buku"] {
        font-weight: 600;
        color: var(--primary-color);
        border-bottom: 2px solid #e9ecef;
    }

    .table td[data-label="Status"] {
        justify-content: flex-end;
    }

    .table td[data-label="Tanggal Pinjam"],
    .table td[data-label="Lama Peminjaman"],
    .table td[data-label="Tanggal Kembali"] {
        color: var(--text-secondary);
    }

    .table td > * {
        max-width: calc(100% - 130px);
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .status-badge {
        white-space: nowrap;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
    }
}

/* Statistics Card Styles */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    padding: 20px;
}

.stat-item {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.stat-item:hover {
    transform: translateY(-2px);
}

.stat-details h3 {
    font-size: 2rem;
    margin: 0 0 10px 0;
    color: var(--primary-color);
}

.stat-details p {
    font-size: 1.1rem;
    margin: 0;
    color: var(--text-primary);
}

@media (max-width: 768px) {
    .stats-container {
        grid-template-columns: 1fr;
        gap: 15px;
        padding: 15px;
    }

    .stat-item {
        padding: 15px;
    }

    .stat-details h3 {
        font-size: 1.5rem;
    }

    .stat-details p {
        font-size: 1rem;
    }
}
</style>

<?php include_once 'includes/footer.php'; ?> 