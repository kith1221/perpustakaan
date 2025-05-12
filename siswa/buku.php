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
                        <tr class="<?php 
                            if ($book['jumlah_buku'] == 0) echo 'stock-empty';
                            elseif ($book['jumlah_buku'] <= 3) echo 'stock-low';
                            else echo 'stock-good';
                        ?>">
                            <td>
                                <?php echo htmlspecialchars($book['judul']); ?>
                                <?php if ($book['jumlah_buku'] == 0): ?>
                                    <span class="badge badge-danger">Stok Habis</span>
                                <?php elseif ($book['jumlah_buku'] <= 3): ?>
                                    <span class="badge badge-warning">Stok Sedikit</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($book['nama_kategori']); ?></td>
                            <td><?php echo htmlspecialchars($book['pengarang']); ?></td>
                            <td><?php echo htmlspecialchars($book['penerbit']); ?></td>
                            <td><?php echo $book['tahun_terbit']; ?></td>
                            <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                            <td class="stock-number"><?php echo $book['jumlah_buku']; ?></td>
                            <td>
                                <?php if ($book['jumlah_buku'] > 0): ?>
                                    <a href="pinjam.php?id=<?php echo $book['id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 0.8rem;">Pinjam</a>
                                <?php else: ?>
                                    <button class="btn btn-secondary" disabled style="padding: 5px 10px; font-size: 0.8rem;">Stok Habis</button>
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

// Update the query to include filters
$where_conditions = [];
$params = [];

if (!empty($_GET['search'])) {
    $search = cleanInput($_GET['search']);
    $where_conditions[] = "(b.judul LIKE ? OR b.pengarang LIKE ? OR b.isbn LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($_GET['kategori'])) {
    $kategori_id = (int)$_GET['kategori'];
    $where_conditions[] = "b.kategori_id = ?";
    $params[] = $kategori_id;
}

if (!empty($_GET['stok'])) {
    switch ($_GET['stok']) {
        case '0':
            $where_conditions[] = "b.jumlah_buku = 0";
            break;
        case '1-3':
            $where_conditions[] = "b.jumlah_buku BETWEEN 1 AND 3";
            break;
        case '4+':
            $where_conditions[] = "b.jumlah_buku >= 4";
            break;
    }
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get books with filters
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
$books = [];

while ($row = mysqli_fetch_assoc($result)) {
    $books[] = $row;
} 