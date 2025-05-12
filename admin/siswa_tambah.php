<?php
// Set page title
$pageTitle = 'Tambah Siswa';

// Include header
require_once 'includes/header.php';

// Initialize variables
$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if it's a regular form submission or CSV import
    if (isset($_POST['submit_regular'])) {
        // Get form data
        $nisn = cleanInput($_POST['nisn']);
        $nama = cleanInput($_POST['nama']);
        $kelas = cleanInput($_POST['kelas']);
        $password = cleanInput($_POST['password']);
        
        // Validate form data
        if (empty($nisn) || empty($nama) || empty($kelas) || empty($password)) {
            $error = 'Harap isi semua field';
        } elseif (strlen($password) < 6) {
            $error = 'Password minimal 6 karakter';
        } else {
            // Check if NISN already exists
            $query = "SELECT COUNT(*) as count FROM siswa WHERE nisn = '$nisn'";
            $result = mysqli_query($conn, $query);
            $row = mysqli_fetch_assoc($result);
            
            if ($row['count'] > 0) {
                $error = 'NISN sudah terdaftar';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new student
                $query = "INSERT INTO siswa (nisn, password, nama, kelas) 
                          VALUES ('$nisn', '$hashed_password', '$nama', '$kelas')";
                
                if (mysqli_query($conn, $query)) {
                    // Set success message
                    $_SESSION['alert'] = "Siswa berhasil ditambahkan";
                    $_SESSION['alert_type'] = 'success';
                    
                    // Redirect to students page
                    redirect('siswa.php');
                } else {
                    $error = 'Gagal menambahkan siswa: ' . mysqli_error($conn);
                }
            }
        }
    } elseif (isset($_POST['submit_csv'])) {
        // Handle CSV import
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            // Check file extension
            $file_extension = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
            if ($file_extension !== 'csv') {
                $error = 'File harus berformat CSV (.csv)';
            } else {
                // Create upload directory if it doesn't exist
                $upload_dir = '../assets/uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                // Generate a secure random filename to prevent path traversal
                $file_name = 'import_siswa_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_extension;
                $file_path = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES['csv_file']['tmp_name'], $file_path)) {
                    // Process the file
                    $success_count = 0;
                    $failed_count = 0;
                    $error_messages = [];

                    // Open the file with proper UTF-8 handling
                    $content = file_get_contents($file_path);
                    
                    // Remove UTF-8 BOM if present
                    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
                        $content = substr($content, 3);
                    }
                    
                    // Write the content back to the file
                    file_put_contents($file_path, $content);
                    
                    try {
                        if (($handle = fopen($file_path, "r")) !== FALSE) {
                            // Skip header row
                            $header = fgetcsv($handle, 1000, ",");
                            
                            // Row counter for error messages
                            $row_number = 1;
                            
                            // Process data rows
                            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                                $row_number++;
                                
                                // Skip empty rows
                                if (empty($data[0]) && empty($data[1]) && empty($data[2])) {
                                    continue;
                                }
                                
                                // Check if we have enough columns
                                if (count($data) >= 3) {
                                    $nisn = cleanInput($data[0]);
                                    $nama = cleanInput($data[1]);
                                    $kelas = cleanInput($data[2]);
                                    
                                    // Validate data
                                    if (empty($nisn)) {
                                        $failed_count++;
                                        $error_messages[] = "Baris $row_number: NISN tidak boleh kosong";
                                        continue;
                                    }
                                    
                                    if (empty($nama)) {
                                        $failed_count++;
                                        $error_messages[] = "Baris $row_number: Nama tidak boleh kosong";
                                        continue;
                                    }
                                    
                                    if (empty($kelas) || !is_numeric($kelas) || $kelas < 1 || $kelas > 6) {
                                        $failed_count++;
                                        $error_messages[] = "Baris $row_number: Kelas harus berupa angka 1-6";
                                        continue;
                                    }
                                    
                                    // Check if NISN is numeric
                                    if (!is_numeric($nisn)) {
                                        $failed_count++;
                                        $error_messages[] = "Baris $row_number: NISN harus berupa angka";
                                        continue;
                                    }
                                    
                                    // Check if NISN already exists - using prepared statement to prevent SQL injection
                                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM siswa WHERE nisn = ?");
                                    $stmt->bind_param("s", $nisn);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $row = $result->fetch_assoc();
                                    $stmt->close();
                                    
                                    if ($row['count'] > 0) {
                                        $failed_count++;
                                        $error_messages[] = "Baris $row_number: NISN '$nisn' sudah terdaftar";
                                    } else {
                                        // Generate password from last 3 digits of NISN
                                        $last3Digits = substr($nisn, -3);
                                        $password = "noefal#" . $last3Digits; // This ensures at least 7 characters (noefal# + 3 digits)
                                        
                                        // Hash password
                                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                                        
                                        // Insert data using prepared statement
                                        $stmt = $conn->prepare("INSERT INTO siswa (nisn, password, nama, kelas) VALUES (?, ?, ?, ?)");
                                        $stmt->bind_param("sssi", $nisn, $hashed_password, $nama, $kelas);
                                        
                                        if ($stmt->execute()) {
                                            $success_count++;
                                        } else {
                                            $failed_count++;
                                            $error_messages[] = "Baris $row_number: " . $stmt->error;
                                        }
                                        $stmt->close();
                                    }
                                } else {
                                    $failed_count++;
                                    $error_messages[] = "Baris $row_number: Format data tidak sesuai (kurang dari 3 kolom)";
                                }
                            }
                            fclose($handle);
                        } else {
                            $error = 'Gagal membuka file CSV';
                        }
                    } catch (Exception $e) {
                        $error = 'Terjadi kesalahan: ' . $e->getMessage();
                    } finally {
                        // Always delete the temporary file
                        if (file_exists($file_path)) {
                            unlink($file_path);
                        }
                    }

                    if ($success_count > 0) {
                        $_SESSION['alert'] = "Berhasil mengimpor $success_count siswa" . ($failed_count > 0 ? " dan gagal mengimpor $failed_count siswa" : "");
                        $_SESSION['alert_type'] = ($failed_count > 0) ? 'warning' : 'success';
                        
                        if (!empty($error_messages)) {
                            $_SESSION['error_details'] = $error_messages;
                        }
                        
                        redirect('siswa.php');
                    } else {
                        $error = 'Gagal mengimpor siswa. Tidak ada data yang valid dalam file.';
                        if (!empty($error_messages)) {
                            $error .= '<div class="error-details" style="margin-top: 10px; max-height: 200px; overflow-y: auto; padding: 10px; background: #fff1f1; border-radius: 4px;">';
                            $error .= '<h4 style="margin-top: 0;">Detail Kesalahan:</h4>';
                            $error .= '<ul style="margin-bottom: 0;">';
                            foreach ($error_messages as $msg) {
                                $error .= '<li>' . htmlspecialchars($msg) . '</li>';
                            }
                            $error .= '</ul></div>';
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

<!-- Add Student Form -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-user-plus"></i> Tambah Siswa</h2>
    </div>
    
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
                    <label for="nisn">NISN <span style="color: red;">*</span></label>
                    <input type="text" id="nisn" name="nisn" required value="<?php echo isset($_POST['nisn']) ? htmlspecialchars($_POST['nisn']) : ''; ?>">
                    <small style="display: block; margin-top: 5px; color: #666;">NISN harus unik dan berupa angka</small>
                </div>
                
                <div class="form-group">
                    <label for="nama">Nama Siswa <span style="color: red;">*</span></label>
                    <input type="text" id="nama" name="nama" required value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="kelas">Kelas <span style="color: red;">*</span></label>
                    <select id="kelas" name="kelas" required>
                        <option value="">Pilih Kelas</option>
                        <?php for ($i = 1; $i <= 6; $i++) : ?>
                            <option value="<?php echo $i; ?>" <?php echo (isset($_POST['kelas']) && $_POST['kelas'] == $i) ? 'selected' : ''; ?>>
                                Kelas <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="password">Password <span style="color: red;">*</span></label>
                    <input type="password" id="password" name="password" required minlength="6">
                    <small style="display: block; margin-top: 5px; color: #666;">Password minimal 6 karakter</small>
                </div>
                
                <div class="form-group" style="display: flex; gap: 10px;">
                    <button type="submit" name="submit_regular" class="btn">Simpan</button>
                    <a href="siswa.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
        
        <!-- CSV Import Form -->
        <div id="tab-import" class="tab-pane">
            <div class="import-instructions">
                <h3>Petunjuk Import CSV</h3>
                <p>Untuk mengimpor data siswa dari file CSV, harap mengikuti format yang telah ditentukan:</p>
                <ol>
                    <li>Gunakan file CSV (Comma Separated Values)</li>
                    <li>Pastikan encoding file adalah UTF-8 untuk mendukung karakter khusus</li>
                    <li>Baris pertama adalah header (NISN, Nama Siswa, Kelas)</li>
                    <li>Data siswa dimulai dari baris kedua</li>
                    <li>Kolom harus urut sesuai format: NISN, Nama Siswa, Kelas</li>
                    <li>NISN harus berupa angka dan unik (belum terdaftar)</li>
                    <li>Kelas harus angka 1-6</li>
                    <li>Password akan dibuat otomatis: noefal#{3 digit terakhir NISN}</li>
                </ol>
                
                <div class="sample-format">
                    <h4>Contoh Format CSV:</h4>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>NISN</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1234567890</td>
                                <td>Ahmad Santoso</td>
                                <td>1</td>
                            </tr>
                            <tr>
                                <td>0987654321</td>
                                <td>Budi Setiawan</td>
                                <td>2</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="download-template" style="text-align: center; margin: 20px 0; padding: 15px; background-color: #f8f9fa; border-radius: 8px; border: 2px dashed #dee2e6;">
                        <a href="generate_template_siswa.php" class="btn btn-primary" download style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; font-size: 1.1em; background-color: #0d6efd; color: white; text-decoration: none; border-radius: 6px; transition: all 0.3s ease;">
                            <i class="fas fa-download"></i>
                            Download Template CSV
                        </a>
                        <p style="margin: 10px 0 0; color: #666; font-size: 0.9em;">
                            <i class="fas fa-info-circle"></i> Klik tombol di atas untuk mengunduh template
                        </p>
                    </div>
                </div>
                
                <div class="alert alert-info" style="margin-top: 20px;">
                    <h4 style="margin-bottom: 10px;">Cara Import Data Siswa dengan Google Sheets:</h4>
                    <ol>
                        <li><strong>Download Template</strong>
                            <ul>
                                <li>Klik tombol "Download Template CSV" di atas</li>
                                <li>Tunggu hingga file template terunduh</li>
                            </ul>
                        </li>
                        
                        <li><strong>Buka Google Sheets</strong>
                            <ul>
                                <li>Buka browser dan kunjungi <a href="https://sheets.google.com" target="_blank">sheets.google.com</a></li>
                                <li>Klik tombol "+" untuk membuat spreadsheet baru</li>
                                <li>Klik menu "File" > "Import"</li>
                                <li>Pilih tab "Upload"</li>
                                <li>Klik "Browse" atau drag & drop file template yang sudah diunduh</li>
                                <li>Pilih "Replace current sheet" pada opsi "Import location"</li>
                                <li>Klik "Import data"</li>
                            </ul>
                        </li>
                        
                        <li><strong>Edit Data</strong>
                            <ul>
                                <li>Hapus baris contoh (baris 2-3)</li>
                                <li>Isi data siswa di bawah baris header</li>
                                <li>Kolom NISN: isi dengan angka (contoh: 1234567890)</li>
                                <li>Kolom Nama Siswa: isi dengan nama lengkap</li>
                                <li>Kolom Kelas: isi dengan angka 1-6</li>
                            </ul>
                        </li>
                        
                        <li><strong>Download sebagai CSV</strong>
                            <ul>
                                <li>Klik menu "File" > "Download"</li>
                                <li>Pilih "Comma-separated values (.csv)"</li>
                                <li>Tunggu hingga file terunduh</li>
                            </ul>
                        </li>
                        
                        <li><strong>Upload File</strong>
                            <ul>
                                <li>Klik tombol "Pilih File" di bawah</li>
                                <li>Pilih file CSV yang baru saja diunduh</li>
                                <li>Klik tombol "Import CSV"</li>
                            </ul>
                        </li>
                    </ol>

                    <div class="tips-box" style="margin-top: 20px; padding: 15px; background-color: #e8f4f8; border-radius: 4px;">
                        <h5 style="margin-top: 0; color: #0c5460;"><i class="fas fa-lightbulb"></i> Tips Penting:</h5>
                        <ul style="margin-bottom: 0;">
                            <li>Jangan ubah nama kolom di baris header</li>
                            <li>Jangan biarkan ada baris kosong di antara data</li>
                            <li>NISN harus unik (tidak boleh sama dengan siswa lain)</li>
                            <li>Password akan dibuat otomatis: noefal# + 3 digit terakhir NISN</li>
                            <li>Contoh password untuk NISN 1234567890: noefal#890</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <form action="" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="csv_file">File CSV <span style="color: red;">*</span></label>
                    <input type="file" id="csv_file" name="csv_file" required accept=".csv">
                    <small style="display: block; margin-top: 5px; color: #666;">Hanya file CSV (.csv) yang didukung. Download template untuk format yang benar.</small>
                </div>
                
                <div class="form-group" style="display: flex; gap: 10px;">
                    <button type="submit" name="submit_csv" class="btn">Import CSV</button>
                    <a href="siswa.php" class="btn btn-secondary">Batal</a>
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

<style>
/* Card styles */
.card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.card-header {
    padding: 15px 20px;
    border-bottom: 1px solid #dee2e6;
}

.card-title {
    margin: 0;
    font-size: 1.25rem;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Tab styles */
.tabs {
    margin-bottom: 20px;
    border-bottom: 1px solid #dee2e6;
    padding: 0 20px;
}

.tabs ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
}

.tabs li {
    margin-right: 5px;
}

.tabs a {
    display: block;
    padding: 10px 20px;
    text-decoration: none;
    color: #495057;
    border: 1px solid transparent;
    border-bottom: none;
    border-radius: 4px 4px 0 0;
}

.tabs li.active a {
    color: var(--primary-color);
    background-color: #fff;
    border-color: #dee2e6;
    border-bottom-color: #fff;
}

.tab-pane {
    display: none;
    padding: 20px;
}

.tab-pane.active {
    display: block;
}

/* Form styles */
.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-group input:focus,
.form-group select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(13, 110, 55, 0.1);
    outline: none;
}

/* Button styles */
.btn {
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
    background-color: var(--primary-color);
    color: white;
    cursor: pointer;
    font-size: 14px;
}

.btn-secondary {
    background-color: #f8f9fa;
    color: #495057;
}

.btn:hover {
    opacity: 0.9;
}

.btn-secondary:hover {
    background-color: #e9ecef;
    border-color: #dee2e6;
}

/* Alert styles */
.alert {
    padding: 12px 20px;
    margin: 20px;
    border-radius: 4px;
    border: 1px solid transparent;
}

.alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}

.alert-success {
    color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
}

/* Table styles */
.table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1rem;
}

.table th,
.table td {
    padding: 12px;
    border: 1px solid #dee2e6;
}

.table th {
    background-color: #f8f9fa;
    font-weight: 500;
}

/* Import instructions styles */
.import-instructions {
    margin-bottom: 20px;
}

.sample-format {
    margin: 20px 0;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 4px;
}

.sample-format h4 {
    margin-bottom: 15px;
    color: #333;
}

/* Tips box styles */
.tips-box {
    margin-top: 20px;
    padding: 15px;
    background-color: #e8f4f8;
    border-radius: 4px;
    border: 1px solid #bee5eb;
}

.tips-box h5 {
    margin-top: 0;
    color: #0c5460;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tips-box ul {
    margin-bottom: 0;
    padding-left: 20px;
}

.tips-box li {
    margin-bottom: 5px;
}
</style>

<?php
// Include footer
require_once 'includes/footer.php';
?> 