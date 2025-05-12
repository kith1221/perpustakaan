<?php
// Set page title
$pageTitle = 'Buku yang Sedang Dipinjam';

// Include header
require_once 'includes/header.php';

// Get active loans
$activeLoans = getActiveLoans($_SESSION['user_id']);
?>

<style>
:root {
    --primary-color: #0D6E37; /* Deep madrasah green */
    --secondary-color: #1A8C4C; /* Lighter green */
    --accent-color: #D4AF37; /* Gold accent */
    --text-dark: #333333;
    --text-light: #FFFFFF;
    --white-color: #FFFFFF;
    --light-color: #F0F5F1;
    --grey-color: #757575;
    --text-primary: #333333;
    --text-secondary: #666666;
    --text-muted: #757575;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-badge.dipinjam {
    background-color: var(--primary-color);
    color: var(--white-color);
}

.status-badge.terlambat {
    background-color: var(--danger-color);
    color: var(--white-color);
}

/* Table Styles */
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

/* Mobile Table Styles */
@media (max-width: 768px) {
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

    /* Specific column styles */
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

    /* Content width adjustments */
    .table td > * {
        max-width: calc(100% - 130px);
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Status badge adjustments */
    .status-badge {
        white-space: nowrap;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
    }
}

/* Empty State Styles */
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

/* Additional styles for icons in table */
.table td i {
    margin-right: 8px;
    width: 16px;
    text-align: center;
}

.table td i.text-primary {
    color: var(--primary-color);
}

/* Status badge with icon */
.status-badge i {
    margin-right: 4px;
}

/* Mobile adjustments for icons */
@media (max-width: 768px) {
    .table td i {
        width: 20px;
        margin-right: 10px;
    }
    
    .table td:before {
        display: flex;
        align-items: center;
    }
    
    .table td:before i {
        margin-right: 8px;
    }
}
</style>

<!-- Active Loans -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-book-open"></i> Buku yang Sedang Dipinjam</h2>
    </div>
    
    <?php if (empty($activeLoans)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox empty-icon"></i>
            <p>Anda tidak memiliki buku yang sedang dipinjam.</p>
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
                    <?php foreach ($activeLoans as $loan): 
                        // Calculate return date
                        $returnDate = date('Y-m-d', strtotime($loan['tanggal_pinjam'] . ' + ' . $loan['lama_pinjam'] . ' days'));
                        
                        // Calculate days left
                        $today = new DateTime('now');
                        $returnDateTime = new DateTime($returnDate);
                        $diff = $today->diff($returnDateTime);
                        $daysLeft = $diff->format('%R%a');
                    ?>
                        <tr>
                            <td data-label="Judul Buku"><?php echo htmlspecialchars($loan['judul']); ?></td>
                            <td data-label="Tanggal Pinjam"><?php echo formatDate($loan['tanggal_pinjam']); ?></td>
                            <td data-label="Lama Peminjaman"><?php echo $loan['lama_pinjam']; ?> hari</td>
                            <td data-label="Tanggal Kembali"><?php echo formatDate($returnDate); ?></td>
                            <td data-label="Status">
                                <?php if ($daysLeft < 0): ?>
                                    <span class="status-badge overdue">Terlambat <?php echo abs($daysLeft); ?> hari</span>
                                <?php else: ?>
                                    <span class="status-badge active"><?php echo $daysLeft; ?> hari lagi</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Information Card -->
<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-info-circle"></i> Informasi Peminjaman</h2>
    </div>
    
    <div style="padding: 20px;">
        <p><strong>Peraturan Peminjaman:</strong></p>
        <ul style="margin-left: 20px; line-height: 1.6;">
            <li>Siswa maksimal boleh meminjam 3 buku secara bersamaan</li>
            <li>Peminjaman buku maksimal sesuai dengan durasi yang telah ditentukan</li>
            <li>Siswa harus mengembalikan buku tepat waktu</li>
            <li>Kerusakan atau kehilangan buku menjadi tanggung jawab peminjam</li>
            <li>Jika terlambat mengembalikan, siswa akan mendapat peringatan</li>
        </ul>
        
        <p style="margin-top: 15px;"><strong>Jam Layanan Perpustakaan:</strong></p>
        <ul style="margin-left: 20px; line-height: 1.6;">
            <li><i class="fas fa-clock text-primary"></i> Senin - Kamis: 08.00 - 14.00</li>
            <li><i class="fas fa-clock text-primary"></i> Jumat: 08.00 - 11.00</li>
            <li><i class="fas fa-clock text-primary"></i> Sabtu - Minggu: Tutup</li>
        </ul>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?> 