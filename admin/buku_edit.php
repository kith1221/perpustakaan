<?php
// Set page title
$pageTitle = 'Edit Buku';

// Include header
require_once 'includes/header.php';

// Initialize variables
$error = '';
$success = '';
$book = null;

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert'] = 'ID buku tidak valid';
    $_SESSION['alert_type'] = 'danger';
    redirect('buku.php');
}

// Get book ID
$id = (int)$_GET['id'];

// Get book data
$query = "SELECT * FROM buku WHERE id = $id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['alert'] = 'Buku tidak ditemukan';
    $_SESSION['alert_type'] = 'danger';
    redirect('buku.php');
}

$book = mysqli_fetch_assoc($result);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $judul = cleanInput($_POST['judul']);
    $pengarang = cleanInput($_POST['pengarang']);
    $penerbit = cleanInput($_POST['penerbit']);
    $tahun_terbit = (int)$_POST['tahun_terbit'];
    $isbn = cleanInput($_POST['isbn']);
    $jumlah_buku = (int)$_POST['jumlah_buku'];
    $kategori_id = (int)$_POST['kategori_id'];
    
    // Validate form data
    if (empty($judul) || empty($pengarang) || empty($penerbit) || empty($tahun_terbit) || empty($jumlah_buku) || empty($kategori_id)) {
        $error = 'Harap isi semua field yang wajib';
    } else {
        // Update book
        $query = "UPDATE buku 
                  SET judul = '$judul', 
                      pengarang = '$pengarang', 
                      penerbit = '$penerbit', 
                      tahun_terbit = $tahun_terbit, 
                      isbn = '$isbn', 
                      jumlah_buku = $jumlah_buku,
                      kategori_id = $kategori_id
                  WHERE id = $id";
        
        if (mysqli_query($conn, $query)) {
            // Set success message
            $_SESSION['alert'] = 'Buku berhasil diperbarui';
            $_SESSION['alert_type'] = 'success';
            
            // Redirect to book page
            redirect('buku.php');
        } else {
            $error = 'Gagal memperbarui buku: ' . mysqli_error($conn);
        }
    }
}

// Get all categories for dropdown
$categories_query = "SELECT * FROM kategori_buku ORDER BY nama_kategori";
$categories_result = mysqli_query($conn, $categories_query);
$categories = [];
if ($categories_result) {
    while ($row = mysqli_fetch_assoc($categories_result)) {
        $categories[] = $row;
    }
}
?>

<!-- Edit Book Form -->
<div class="card">
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <form action="" method="post">
        <div class="form-group">
            <label for="judul">Judul Buku <span style="color: red;">*</span></label>
            <input type="text" id="judul" name="judul" required value="<?php echo htmlspecialchars($book['judul']); ?>">
        </div>
        
        <div class="form-group">
            <label for="kategori_id">Kategori <span style="color: red;">*</span></label>
            <select id="kategori_id" name="kategori_id" required>
                <option value="">Pilih Kategori</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo $book['kategori_id'] == $category['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['nama_kategori']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="pengarang">Pengarang <span style="color: red;">*</span></label>
            <input type="text" id="pengarang" name="pengarang" required value="<?php echo htmlspecialchars($book['pengarang']); ?>">
        </div>
        
        <div class="form-group">
            <label for="penerbit">Penerbit <span style="color: red;">*</span></label>
            <input type="text" id="penerbit" name="penerbit" required value="<?php echo htmlspecialchars($book['penerbit']); ?>">
        </div>
        
        <div class="form-group">
            <label for="tahun_terbit">Tahun Terbit <span style="color: red;">*</span></label>
            <input type="number" id="tahun_terbit" name="tahun_terbit" min="1900" max="<?php echo date('Y'); ?>" required value="<?php echo $book['tahun_terbit']; ?>">
        </div>
        
        <div class="form-group">
            <label for="isbn">ISBN</label>
            <input type="text" id="isbn" name="isbn" value="<?php echo htmlspecialchars($book['isbn']); ?>">
        </div>
        
        <div class="form-group">
            <label for="jumlah_buku">Jumlah Buku <span style="color: red;">*</span></label>
            <input type="number" id="jumlah_buku" name="jumlah_buku" min="0" required value="<?php echo $book['jumlah_buku']; ?>">
        </div>
        
        <div class="form-group" style="display: flex; gap: 10px;">
            <button type="submit" class="btn">Simpan</button>
            <a href="buku.php" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?> 