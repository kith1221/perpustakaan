<?php
// Set page title
$pageTitle = 'Kelola Siswa';

// Include header
require_once 'includes/header.php';

// Process delete action
if (isset($_GET['delete']) && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Check if student has active loans
    $query = "SELECT COUNT(*) as count FROM peminjaman WHERE siswa_id = $id AND status = 'dipinjam'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['count'] > 0) {
        $_SESSION['alert'] = 'Siswa tidak dapat dihapus karena memiliki peminjaman aktif';
        $_SESSION['alert_type'] = 'danger';
    } else {
        // Delete student
        $query = "DELETE FROM siswa WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            $_SESSION['alert'] = 'Siswa berhasil dihapus';
            $_SESSION['alert_type'] = 'success';
        } else {
            $_SESSION['alert'] = 'Gagal menghapus siswa: ' . mysqli_error($conn);
            $_SESSION['alert_type'] = 'danger';
        }
    }
    
    // Redirect to remove the GET parameter
    redirect('siswa.php');
}

// Get search query
$search = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = cleanInput($_GET['search']);
}

// Get filter by class
$filterClass = '';
if (isset($_GET['kelas']) && !empty($_GET['kelas'])) {
    $filterClass = cleanInput($_GET['kelas']);
}

// Prepare search and filter condition
$condition = '';
if (!empty($search) && !empty($filterClass)) {
    $condition = "WHERE (nama LIKE '%$search%' OR nisn LIKE '%$search%') AND kelas = '$filterClass'";
} elseif (!empty($search)) {
    $condition = "WHERE nama LIKE '%$search%' OR nisn LIKE '%$search%'";
} elseif (!empty($filterClass)) {
    $condition = "WHERE kelas = '$filterClass'";
}

// Get all students with search and filter
$query = "SELECT * FROM siswa $condition ORDER BY kelas ASC, nama ASC";
$result = mysqli_query($conn, $query);
$students = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
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
?>

<!-- Search and Filter Card -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-search"></i> Cari dan Filter</h2>
    </div>
    <div class="filter-container" style="padding: 1rem; background-color: #f9f9f9; border-radius: 4px; margin-bottom: 1rem;">
        <form action="" method="get" class="filter-form">
            <div class="filter-row" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
                <div class="filter-item" style="flex: 1;">
                    <label for="search" style="display: block; margin-bottom: 5px; font-weight: 500;">Cari Siswa:</label>
                    <input type="text" id="search" name="search" placeholder="Nama atau NISN..." 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                </div>
                
                <div class="filter-item">
                    <label for="kelas" style="display: block; margin-bottom: 5px; font-weight: 500;">Filter Kelas:</label>
                    <select name="kelas" id="kelas" style="padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class; ?>" <?php echo $filterClass == $class ? 'selected' : ''; ?>>
                                Kelas <?php echo $class; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="button-container">
                    <button type="submit" class="btn" style="margin-right: 5px;">Cari</button>
                    <a href="siswa.php" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
        
        <!-- Add Student Button -->
        <div style="margin-top: 15px; text-align: right;">
            <a href="siswa_tambah.php" class="btn">
                <i class="fas fa-plus"></i> Tambah Siswa
            </a>
        </div>
    </div>
</div>

<!-- Display Alerts -->
<?php if (isset($_SESSION['alert'])): ?>
    <div class="alert alert-<?php echo $_SESSION['alert_type']; ?>">
        <?php echo $_SESSION['alert']; ?>
    </div>
    
    <?php if (isset($_SESSION['error_details']) && !empty($_SESSION['error_details'])): ?>
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header" style="background-color: #f8d7da; color: #721c24;">
                <h3 style="margin: 0; font-size: 1.1rem;"><i class="fas fa-exclamation-triangle"></i> Detail Kesalahan Import</h3>
            </div>
            <div class="error-details" style="max-height: 250px; overflow-y: auto; padding: 15px;">
                <ul style="list-style-type: disc; margin-left: 20px; margin-bottom: 0;">
                    <?php foreach ($_SESSION['error_details'] as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php unset($_SESSION['error_details']); ?>
    <?php endif; ?>
    
    <?php unset($_SESSION['alert']); ?>
    <?php unset($_SESSION['alert_type']); ?>
<?php endif; ?>

<!-- Search Results Info -->
<?php if (!empty($search) || !empty($filterClass)): ?>
    <div class="filter-info" style="margin-bottom: 15px; padding: 10px 15px; background-color: #e9f7ef; border-radius: 4px;">
        <p style="margin: 0; font-size: 0.9rem;">
            <i class="fas fa-info-circle"></i> 
            Menampilkan hasil 
            <?php if (!empty($search)): ?>
                pencarian untuk <strong>"<?php echo htmlspecialchars($search); ?>"</strong>
            <?php endif; ?>
            
            <?php if (!empty($search) && !empty($filterClass)): ?>
                dan 
            <?php endif; ?>
            
            <?php if (!empty($filterClass)): ?>
                filter kelas <strong><?php echo htmlspecialchars($filterClass); ?></strong>
            <?php endif; ?>
            : ditemukan <strong><?php echo count($students); ?></strong> siswa
        </p>
    </div>
<?php endif; ?>

<!-- Students Table -->
<div class="card">
    <?php if (empty($students)): ?>
        <div class="empty-state" style="padding: 30px; text-align: center;">
            <i class="fas fa-user-slash" style="font-size: 3rem; color: #6c757d; margin-bottom: 15px;"></i>
            <p>Tidak ada data siswa<?php echo (!empty($search) || !empty($filterClass)) ? ' yang sesuai dengan pencarian' : ''; ?>.</p>
        </div>
    <?php else: ?>
        <form id="bulk-delete-form" action="siswa_delete.php" method="post">
            <div class="bulk-actions" style="padding: 15px; border-bottom: 1px solid #eee;">
                <button type="submit" class="btn btn-danger" id="bulk-delete-btn" disabled>
                    <i class="fas fa-trash"></i> Hapus Siswa Terpilih
                </button>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" id="select-all" style="cursor: pointer;">
                            </th>
                            <th>ID</th>
                            <th>NISN</th>
                            <th>Nama</th>
                            <th>Kelas</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="selected_students[]" value="<?php echo $student['id']; ?>" class="student-checkbox" style="cursor: pointer;">
                                </td>
                                <td data-label="ID"><?php echo $student['id']; ?></td>
                                <td data-label="NISN"><?php echo htmlspecialchars($student['nisn']); ?></td>
                                <td data-label="Nama"><?php echo htmlspecialchars($student['nama']); ?></td>
                                <td data-label="Kelas"><?php echo htmlspecialchars($student['kelas']); ?></td>
                                <td data-label="Aksi" class="action-buttons">
                                    <a href="siswa_edit.php?id=<?php echo $student['id']; ?>" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="siswa.php?delete=1&id=<?php echo $student['id']; ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus siswa ini?');">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
    <?php endif; ?>
</div>

<!-- Add JavaScript for checkbox functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select-all');
    const studentCheckboxes = document.getElementsByClassName('student-checkbox');
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
    const bulkDeleteForm = document.getElementById('bulk-delete-form');

    // Handle select all checkbox
    selectAllCheckbox.addEventListener('change', function() {
        const isChecked = this.checked;
        Array.from(studentCheckboxes).forEach(checkbox => {
            checkbox.checked = isChecked;
        });
        updateBulkDeleteButton();
    });

    // Handle individual checkboxes
    Array.from(studentCheckboxes).forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Update select all checkbox state
            const allChecked = Array.from(studentCheckboxes).every(cb => cb.checked);
            const someChecked = Array.from(studentCheckboxes).some(cb => cb.checked);
            
            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = someChecked && !allChecked;
            
            updateBulkDeleteButton();
        });
    });

    // Update bulk delete button state and count
    function updateBulkDeleteButton() {
        const checkedCount = Array.from(studentCheckboxes).filter(cb => cb.checked).length;
        bulkDeleteBtn.disabled = checkedCount === 0;
        
        // Update button text with count
        if (checkedCount > 0) {
            bulkDeleteBtn.innerHTML = `<i class="fas fa-trash"></i> Hapus ${checkedCount} Siswa Terpilih`;
        } else {
            bulkDeleteBtn.innerHTML = `<i class="fas fa-trash"></i> Hapus Siswa Terpilih`;
        }
    }

    // Handle form submission
    bulkDeleteForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const checkedCount = Array.from(studentCheckboxes).filter(cb => cb.checked).length;
        
        if (checkedCount > 0) {
            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: `Apakah Anda yakin ingin menghapus ${checkedCount} siswa yang dipilih?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        }
    });
});
</script>

<!-- Add styles for bulk actions -->
<style>
.bulk-actions {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    padding: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.bulk-actions .btn {
    padding: 8px 16px;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 5px;
}

.bulk-actions .btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.table th:first-child,
.table td:first-child {
    text-align: center;
    width: 40px;
}

.student-checkbox {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

#select-all {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

/* Add hover effect for checkboxes */
.student-checkbox:hover,
#select-all:hover {
    transform: scale(1.1);
    transition: transform 0.2s ease;
}

/* Add styles for indeterminate state */
#select-all:indeterminate {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

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

/* Table responsive styles */
.table-responsive {
    overflow-x: auto;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 0.8rem;
}

.action-buttons {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}
</style>

<?php
// Include footer
require_once 'includes/footer.php';
?> 