<?php
// Set page title
$pageTitle = 'Kelola Buku';

// Include header
require_once 'includes/header.php';

// Process delete action
if (isset($_GET['delete']) && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Check if book is being borrowed
    $query = "SELECT COUNT(*) as count FROM peminjaman WHERE buku_id = $id AND status = 'dipinjam'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['count'] > 0) {
        $_SESSION['alert'] = 'Buku tidak dapat dihapus karena sedang dipinjam';
        $_SESSION['alert_type'] = 'danger';
    } else {
        // Delete book
        $query = "DELETE FROM buku WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            $_SESSION['alert'] = 'Buku berhasil dihapus';
            $_SESSION['alert_type'] = 'success';
        } else {
            $_SESSION['alert'] = 'Gagal menghapus buku: ' . mysqli_error($conn);
            $_SESSION['alert_type'] = 'danger';
        }
    }
    
    // Redirect to remove the GET parameter
    redirect('buku.php');
}

// Get search query and category filter
$search = '';
$category_id = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = cleanInput($_GET['search']);
}
if (isset($_GET['category_id']) && !empty($_GET['category_id'])) {
    $category_id = (int)$_GET['category_id'];
}

// Prepare search and filter conditions
$conditions = [];
if (!empty($search)) {
    $conditions[] = "(judul LIKE '%$search%' OR pengarang LIKE '%$search%' OR penerbit LIKE '%$search%' OR isbn LIKE '%$search%')";
}
if (!empty($category_id)) {
    $conditions[] = "kategori_id = $category_id";
}

$searchCondition = '';
if (!empty($conditions)) {
    $searchCondition = "WHERE " . implode(' AND ', $conditions);
}

// Get all books with search and category filter
$query = "SELECT b.*, k.nama_kategori 
          FROM buku b 
          LEFT JOIN kategori_buku k ON b.kategori_id = k.id 
          $searchCondition 
          ORDER BY b.judul ASC";
$result = mysqli_query($conn, $query);
$books = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $books[] = $row;
    }
}

// Get all categories for filter dropdown
$categories_query = "SELECT * FROM kategori_buku ORDER BY nama_kategori";
$categories_result = mysqli_query($conn, $categories_query);
$categories = [];
if ($categories_result) {
    while ($row = mysqli_fetch_assoc($categories_result)) {
        $categories[] = $row;
    }
}
?>

<!-- Search, Filter and Add Button -->
<div style="display: flex; justify-content: space-between; margin-bottom: 15px; flex-wrap: wrap;">
    <div>
        <form action="" method="get" style="display: flex; gap: 10px;">
            <input type="text" name="search" placeholder="Cari buku..." value="<?php echo htmlspecialchars($search); ?>" style="width: 250px;">
            <select name="category_id" style="width: 200px;">
                <option value="">Semua Kategori</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['nama_kategori']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn">Cari</button>
            <?php if (!empty($search) || !empty($category_id)): ?>
                <a href="buku.php" class="btn btn-secondary">Reset</a>
            <?php endif; ?>
        </form>
    </div>
    <div>
        <a href="buku_tambah.php" class="btn">Tambah Buku</a>
    </div>
</div>

<!-- Display Alerts -->
<?php if (isset($_SESSION['alert'])): ?>
    <div class="alert alert-<?php echo $_SESSION['alert_type']; ?>">
        <?php echo $_SESSION['alert']; ?>
    </div>
    
    <?php if (isset($_SESSION['error_details']) && !empty($_SESSION['error_details'])): ?>
        <div class="card" style="margin-bottom: 20px;">
            <h3 style="margin-bottom: 10px; color: #721c24;">Detail Kesalahan Import:</h3>
            <ul style="list-style-type: disc; margin-left: 20px;">
                <?php foreach ($_SESSION['error_details'] as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['error_details']); ?>
    <?php endif; ?>
    
    <?php unset($_SESSION['alert']); ?>
    <?php unset($_SESSION['alert_type']); ?>
<?php endif; ?>

<!-- Search and Filter Form -->
<div class="search-filter-container" style="margin-bottom: 20px;">
    <form action="" method="get" class="search-filter-form">
        <div class="form-group" style="display: flex; gap: 10px; align-items: flex-end;">
            <div style="flex: 1;">
                <label for="search">Cari Buku:</label>
                <input type="text" id="search" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" placeholder="Judul, pengarang, atau ISBN...">
            </div>
            
            <div>
                <label for="kategori">Kategori:</label>
                <select id="kategori" name="kategori">
                    <option value="">Semua Kategori</option>
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
            
            <div>
                <label for="stok">Stok:</label>
                <select id="stok" name="stok">
                    <option value="">Semua Stok</option>
                    <option value="0" <?php echo (isset($_GET['stok']) && $_GET['stok'] === '0') ? 'selected' : ''; ?>>Stok Habis</option>
                    <option value="1-3" <?php echo (isset($_GET['stok']) && $_GET['stok'] === '1-3') ? 'selected' : ''; ?>>Stok Sedikit (1-3)</option>
                    <option value="4+" <?php echo (isset($_GET['stok']) && $_GET['stok'] === '4+') ? 'selected' : ''; ?>>Stok Banyak (4+)</option>
                </select>
            </div>
            
            <div>
                <button type="submit" class="btn">Cari</button>
                <a href="buku.php" class="btn btn-secondary">Reset</a>
            </div>
        </div>
    </form>
</div>

<!-- Books Table -->
<div class="card">
    <?php if (empty($books)): ?>
        <p>Tidak ada data buku.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Pengarang</th>
                        <th>Penerbit</th>
                        <th>Tahun Terbit</th>
                        <th>ISBN</th>
                        <th>Stok</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($books as $book): ?>
                        <tr class="<?php echo $book['jumlah_buku'] == 0 ? 'out-of-stock' : ''; ?>">
                            <td><?php echo $book['id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($book['judul']); ?>
                                <?php if ($book['jumlah_buku'] == 0): ?>
                                    <span class="badge" style="background-color: #dc3545; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.8rem; margin-left: 5px;">Stok Habis</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($book['nama_kategori']); ?></td>
                            <td><?php echo htmlspecialchars($book['pengarang']); ?></td>
                            <td><?php echo htmlspecialchars($book['penerbit']); ?></td>
                            <td><?php echo $book['tahun_terbit']; ?></td>
                            <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                            <td>
                                <span class="<?php echo $book['jumlah_buku'] == 0 ? 'text-danger' : ($book['jumlah_buku'] <= 3 ? 'text-warning' : 'text-success'); ?>">
                                    <?php echo $book['jumlah_buku']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="buku_edit.php?id=<?php echo $book['id']; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem; margin-right: 5px;">Edit</a>
                                <a href="buku.php?delete=1&id=<?php echo $book['id']; ?>" 
                                   class="btn btn-danger" 
                                   onclick="return confirm('Apakah Anda yakin ingin menghapus buku ini?');"
                                   style="padding: 5px 10px; font-size: 0.8rem;">
                                    Hapus
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
.out-of-stock {
    background-color: #fff5f5;
}
.text-danger {
    color: #dc3545;
    font-weight: bold;
}
.text-warning {
    color: #ffc107;
    font-weight: bold;
}
.text-success {
    color: #28a745;
    font-weight: bold;
}
.stock-empty {
    background-color: #fff5f5;
}
.stock-low {
    background-color: #fff8e6;
}
.stock-good {
    background-color: #f0fff0;
}
.stock-number {
    font-weight: bold;
}
.stock-empty .stock-number {
    color: #dc3545;
}
.stock-low .stock-number {
    color: #ffc107;
}
.stock-good .stock-number {
    color: #28a745;
}
.badge {
    padding: 3px 6px;
    border-radius: 3px;
    font-size: 0.8em;
    margin-left: 5px;
}
.badge-danger {
    background-color: #dc3545;
    color: white;
}
.badge-warning {
    background-color: #ffc107;
    color: #000;
}
.search-filter-form {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}
.search-filter-form .form-group {
    margin-bottom: 0;
}
</style>

<?php
// Include footer
require_once 'includes/footer.php';
?> 