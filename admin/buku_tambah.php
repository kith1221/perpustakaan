<?php
// Set page title
$pageTitle = 'Tambah Buku';

// Include header
require_once 'includes/header.php';

// Initialize variables
$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if it's a regular form submission or Excel import
    if (isset($_POST['submit_regular'])) {
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
            // Insert new book
            $query = "INSERT INTO buku (judul, pengarang, penerbit, tahun_terbit, isbn, jumlah_buku, kategori_id) 
                      VALUES ('$judul', '$pengarang', '$penerbit', $tahun_terbit, '$isbn', $jumlah_buku, $kategori_id)";
            
            if (mysqli_query($conn, $query)) {
                // Set success message
                $_SESSION['alert'] = 'Buku berhasil ditambahkan';
                $_SESSION['alert_type'] = 'success';
                
                // Redirect to book page
                redirect('buku.php');
            } else {
                $error = 'Gagal menambahkan buku: ' . mysqli_error($conn);
            }
        }
    } elseif (isset($_POST['submit_excel'])) {
        // Handle Excel import
        if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
            // Check file extension
            $file_extension = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));
            if ($file_extension !== 'csv' && $file_extension !== 'xlsx' && $file_extension !== 'xls') {
                $error = 'File harus berformat Excel (.xlsx, .xls) atau CSV (.csv)';
            } else {
                // Create upload directory if it doesn't exist
                $upload_dir = '../assets/uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                // Move uploaded file to upload directory
                $temp_file = $_FILES['excel_file']['tmp_name'];
                $file_name = 'import_buku_' . time() . '.' . $file_extension;
                $file_path = $upload_dir . $file_name;

                if (move_uploaded_file($temp_file, $file_path)) {
                    // Process the file
                    $success_count = 0;
                    $failed_count = 0;
                    $error_messages = [];

                    // CSV processing
                    if ($file_extension === 'csv') {
                        // Open the file with proper UTF-8 handling
                        $content = file_get_contents($file_path);
                        
                        // Remove UTF-8 BOM if present
                        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
                            $content = substr($content, 3);
                        }
                        
                        // Write the content back to the file
                        file_put_contents($file_path, $content);
                        
                        if (($handle = fopen($file_path, "r")) !== FALSE) {
                            // Skip header row
                            $header = fgetcsv($handle, 1000, ",");
                            
                            // Row counter for error messages
                            $row_number = 1;
                            
                            // Process data rows
                            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                                $row_number++;
                                
                                // Check if we have enough columns
                                if (count($data) >= 7) {
                                    $judul = cleanInput($data[0]);
                                    $pengarang = cleanInput($data[1]);
                                    $penerbit = cleanInput($data[2]);
                                    $tahun_terbit = (int)$data[3];
                                    $isbn = cleanInput($data[4]);
                                    $jumlah_buku = (int)$data[5];
                                    $kategori_nama = cleanInput($data[6]);
                                    
                                    // Get category ID
                                    $query = "SELECT id FROM kategori_buku WHERE nama_kategori = '$kategori_nama'";
                                    $result = mysqli_query($conn, $query);
                                    
                                    if (mysqli_num_rows($result) === 0) {
                                        $failed_count++;
                                        $error_messages[] = "Baris $row_number: Kategori '$kategori_nama' tidak ditemukan";
                                        continue;
                                    }
                                    
                                    $kategori = mysqli_fetch_assoc($result);
                                    $kategori_id = $kategori['id'];
                                    
                                    // Validate data
                                    if (!empty($judul) && !empty($pengarang) && !empty($penerbit) && $tahun_terbit > 0 && $jumlah_buku >= 0) {
                                        // Insert data
                                        $query = "INSERT INTO buku (judul, pengarang, penerbit, tahun_terbit, isbn, jumlah_buku, kategori_id) 
                                                  VALUES ('$judul', '$pengarang', '$penerbit', $tahun_terbit, '$isbn', $jumlah_buku, $kategori_id)";
                                        
                                        if (mysqli_query($conn, $query)) {
                                            $success_count++;
                                        } else {
                                            $failed_count++;
                                            $error_messages[] = "Baris $row_number: " . mysqli_error($conn);
                                        }
                                    } else {
                                        $failed_count++;
                                        $error_messages[] = "Baris $row_number: Data tidak lengkap atau tidak valid";
                                    }
                                } else {
                                    $failed_count++;
                                    $error_messages[] = "Baris $row_number: Format data tidak sesuai (kurang dari 7 kolom)";
                                }
                            }
                            fclose($handle);
                        } else {
                            $error = 'Gagal membuka file CSV';
                        }
                    } else {
                        // Excel files (.xlsx, .xls) not supported directly without library
                        $error = 'Format file Excel (.xlsx, .xls) tidak didukung. Silakan gunakan file CSV (.csv).';
                    }

                    // Delete uploaded file
                    unlink($file_path);

                    if ($success_count > 0) {
                        $_SESSION['alert'] = "Berhasil mengimpor $success_count buku" . ($failed_count > 0 ? " dan gagal mengimpor $failed_count buku" : "");
                        $_SESSION['alert_type'] = ($failed_count > 0) ? 'warning' : 'success';
                        
                        if (!empty($error_messages)) {
                            $_SESSION['error_details'] = $error_messages;
                        }
                        
                        redirect('buku.php');
                    } else {
                        $error = 'Gagal mengimpor buku. Tidak ada data yang valid dalam file.';
                        if (!empty($error_messages)) {
                            $error .= '<br>' . implode('<br>', $error_messages);
                        }
                    }
                } else {
                    $error = 'Gagal mengupload file';
                }
            }
        } else {
            $error = 'Harap pilih file untuk diimpor';
        }
    }
}
?>

<!-- Add Book Form -->
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
    
    <!-- Tab Navigation -->
    <div class="tabs">
        <ul>
            <li class="active"><a href="#tab-form" data-toggle="tab">Input Manual</a></li>
            <li><a href="#tab-import" data-toggle="tab">Import CSV</a></li>
        </ul>
    </div>
    
    <!-- Tab Content -->
    <div class="tab-content">
        <!-- Manual Input Form -->
        <div id="tab-form" class="tab-pane active">
            <form action="" method="post">
                <div class="form-group">
                    <label for="judul">Judul Buku <span style="color: red;">*</span></label>
                    <input type="text" id="judul" name="judul" required value="<?php echo isset($_POST['judul']) ? htmlspecialchars($_POST['judul']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="kategori_id">Kategori <span style="color: red;">*</span></label>
                    <select id="kategori_id" name="kategori_id" required>
                        <option value="">Pilih Kategori</option>
                        <?php
                        $query = "SELECT * FROM kategori_buku ORDER BY nama_kategori";
                        $result = mysqli_query($conn, $query);
                        while ($row = mysqli_fetch_assoc($result)) {
                            $selected = (isset($_POST['kategori_id']) && $_POST['kategori_id'] == $row['id']) ? 'selected' : '';
                            echo "<option value='{$row['id']}' {$selected}>{$row['nama_kategori']}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="pengarang">Pengarang <span style="color: red;">*</span></label>
                    <input type="text" id="pengarang" name="pengarang" required value="<?php echo isset($_POST['pengarang']) ? htmlspecialchars($_POST['pengarang']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="penerbit">Penerbit <span style="color: red;">*</span></label>
                    <input type="text" id="penerbit" name="penerbit" required value="<?php echo isset($_POST['penerbit']) ? htmlspecialchars($_POST['penerbit']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="tahun_terbit">Tahun Terbit <span style="color: red;">*</span></label>
                    <input type="number" id="tahun_terbit" name="tahun_terbit" min="1900" max="<?php echo date('Y'); ?>" required value="<?php echo isset($_POST['tahun_terbit']) ? htmlspecialchars($_POST['tahun_terbit']) : date('Y'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="isbn">ISBN</label>
                    <input type="text" id="isbn" name="isbn" value="<?php echo isset($_POST['isbn']) ? htmlspecialchars($_POST['isbn']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="jumlah_buku">Jumlah Buku <span style="color: red;">*</span></label>
                    <input type="number" id="jumlah_buku" name="jumlah_buku" min="0" required value="<?php echo isset($_POST['jumlah_buku']) ? htmlspecialchars($_POST['jumlah_buku']) : 0; ?>">
                </div>
                
                <div class="form-group" style="display: flex; gap: 10px;">
                    <button type="submit" name="submit_regular" class="btn">Simpan</button>
                    <a href="buku.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
        
        <!-- Excel Import Form -->
        <div id="tab-import" class="tab-pane">
            <div class="import-instructions">
                <h3>Petunjuk Import CSV</h3>
                <p>Untuk mengimpor data buku dari file CSV, harap mengikuti format yang telah ditentukan:</p>
                <ol>
                    <li>Gunakan file CSV (Comma Separated Values)</li>
                    <li>Baris pertama adalah header (Judul, Pengarang, Penerbit, Tahun Terbit, ISBN, Jumlah, Kategori)</li>
                    <li>Data buku dimulai dari baris kedua</li>
                    <li>Kolom harus urut sesuai format: Judul, Pengarang, Penerbit, Tahun Terbit, ISBN, Jumlah, Kategori</li>
                    <li>Gunakan template yang disediakan untuk memastikan format yang benar</li>
                </ol>
                
                <div class="sample-format">
                    <h4>Contoh Format CSV:</h4>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Pengarang</th>
                                <th>Penerbit</th>
                                <th>Tahun Terbit</th>
                                <th>ISBN</th>
                                <th>Jumlah</th>
                                <th>Kategori</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Harry Potter</td>
                                <td>J.K Rowling</td>
                                <td>Gramedia</td>
                                <td>2001</td>
                                <td>978-602-03-0408-4</td>
                                <td>10</td>
                                <td>Umum</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <a href="generate_template.php" class="btn btn-secondary" download>Download Template CSV</a>
                </div>
                
                <div class="alert alert-info" style="margin-top: 20px;">
                    <h4 style="margin-bottom: 10px;">Cara Menggunakan Template CSV:</h4>
                    <ol>
                        <li>Download template melalui tombol di atas</li>
                        <li>Buka file dengan aplikasi spreadsheet</li>
                        <li>Isi data buku pada baris yang tersedia</li>
                        <li>Pastikan kategori yang diisi sesuai dengan kategori yang tersedia di sistem</li>
                        <li>Simpan sebagai file CSV</li>
                        <li>Upload file tersebut menggunakan form di bawah ini</li>
                    </ol>
                    <p><strong>Tips:</strong> Jangan mengubah struktur kolom dan baris header pada template.</p>
                </div>
            </div>
            
            <form action="" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="excel_file">File CSV <span style="color: red;">*</span></label>
                    <input type="file" id="excel_file" name="excel_file" required accept=".csv">
                    <small style="display: block; margin-top: 5px; color: #666;">Hanya file CSV (.csv) yang didukung. Download template untuk format yang benar.</small>
                </div>
                
                <div class="form-group" style="display: flex; gap: 10px;">
                    <button type="submit" name="submit_excel" class="btn">Import CSV</button>
                    <a href="buku.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add JavaScript for tab functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get all tab links
    var tabLinks = document.querySelectorAll('.tabs a');
    
    // Add click event to each tab link
    tabLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get the target tab pane ID
            var targetId = this.getAttribute('href');
            
            // Hide all tab panes
            document.querySelectorAll('.tab-pane').forEach(function(pane) {
                pane.classList.remove('active');
            });
            
            // Show the target tab pane
            document.querySelector(targetId).classList.add('active');
            
            // Update active tab
            document.querySelectorAll('.tabs li').forEach(function(item) {
                item.classList.remove('active');
            });
            this.parentNode.classList.add('active');
        });
    });
});
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?> 