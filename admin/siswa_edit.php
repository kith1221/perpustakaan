<?php
// Set page title
$pageTitle = 'Edit Siswa';

// Include header
require_once 'includes/header.php';

// Initialize variables
$error = '';
$success = '';
$student = null;

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert'] = 'ID siswa tidak valid';
    $_SESSION['alert_type'] = 'danger';
    redirect('siswa.php');
}

// Get student ID
$id = (int)$_GET['id'];

// Get student data
$query = "SELECT * FROM siswa WHERE id = $id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['alert'] = 'Siswa tidak ditemukan';
    $_SESSION['alert_type'] = 'danger';
    redirect('siswa.php');
}

$student = mysqli_fetch_assoc($result);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $nisn = cleanInput($_POST['nisn']);
    $nama = cleanInput($_POST['nama']);
    $kelas = cleanInput($_POST['kelas']);
    $reset_password = isset($_POST['reset_password']) ? true : false;
    $custom_password = isset($_POST['custom_password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['custom_password']) ? $_POST['confirm_password'] : '';
    
    // Validate form data
    if (empty($nisn) || empty($nama) || empty($kelas)) {
        $error = 'Harap isi semua field yang wajib';
    } elseif (isset($_POST['custom_password']) && !empty($custom_password) && $custom_password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok';
    } else {
        // Check if NISN already exists for other students
        $query = "SELECT COUNT(*) as count FROM siswa WHERE nisn = '$nisn' AND id != $id";
        $result = mysqli_query($conn, $query);
        $row = mysqli_fetch_assoc($result);
        
        if ($row['count'] > 0) {
            $error = 'NISN sudah terdaftar untuk siswa lain';
        } else {
            // Prepare password update if needed
            $passwordUpdate = '';
            $passwordMessage = '';
            
            if ($reset_password) {
                // Generate default password from last 3 digits of NISN
                $last3Digits = substr($nisn, -3);
                $password = "noefal#" . $last3Digits;
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $passwordUpdate = ", password = '$hashed_password'";
                $passwordMessage = " dengan password direset menjadi: $password";
            } elseif (isset($_POST['custom_password']) && !empty($custom_password)) {
                // Use custom password
                $hashed_password = password_hash($custom_password, PASSWORD_DEFAULT);
                $passwordUpdate = ", password = '$hashed_password'";
                $passwordMessage = " dengan password telah diubah";
            }
            
            // Update student
            $query = "UPDATE siswa 
                      SET nisn = '$nisn', 
                          nama = '$nama', 
                          kelas = '$kelas'
                          $passwordUpdate
                      WHERE id = $id";
            
            if (mysqli_query($conn, $query)) {
                // Set success message
                $_SESSION['alert'] = 'Data siswa berhasil diperbarui' . $passwordMessage;
                $_SESSION['alert_type'] = 'success';
                
                // Redirect to students page
                redirect('siswa.php');
            } else {
                $error = 'Gagal memperbarui data siswa: ' . mysqli_error($conn);
            }
        }
    }
}
?>

<!-- Edit Student Form -->
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
            <label for="nisn">NISN <span style="color: red;">*</span></label>
            <input type="text" id="nisn" name="nisn" required value="<?php echo htmlspecialchars($student['nisn']); ?>">
        </div>
        
        <div class="form-group">
            <label for="nama">Nama Siswa <span style="color: red;">*</span></label>
            <input type="text" id="nama" name="nama" required value="<?php echo htmlspecialchars($student['nama']); ?>">
        </div>
        
        <div class="form-group">
            <label for="kelas">Kelas <span style="color: red;">*</span></label>
            <select id="kelas" name="kelas" required>
                <option value="">Pilih Kelas</option>
                <?php for ($i = 1; $i <= 6; $i++) : ?>
                    <option value="<?php echo $i; ?>" <?php echo $student['kelas'] == $i ? 'selected' : ''; ?>>
                        Kelas <?php echo $i; ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        
        <hr style="margin: 20px 0;">
        <h3 style="font-size: 1.2rem; margin-bottom: 15px;">Pengaturan Password</h3>
        
        <div class="form-group">
            <div style="margin-bottom: 15px;">
                <input type="checkbox" id="reset_password" name="reset_password" onchange="togglePasswordFields()">
                <label for="reset_password">Reset password ke default (noefal#<?php echo substr($student['nisn'], -3); ?>)</label>
            </div>
            
            <div style="margin-bottom: 15px;">
                <input type="checkbox" id="custom_password" name="custom_password" onchange="togglePasswordFields()">
                <label for="custom_password">Gunakan password kustom</label>
            </div>
        </div>
        
        <div id="password_fields" style="display: none;">
            <div class="form-group">
                <label for="password">Password Baru</label>
                <input type="password" id="password" name="password">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password Baru</label>
                <input type="password" id="confirm_password" name="confirm_password">
            </div>
        </div>
        
        <div class="form-group" style="display: flex; gap: 10px;">
            <button type="submit" class="btn">Simpan</button>
            <a href="siswa.php" class="btn btn-secondary">Batal</a>
        </div>
    </form>
    
    <script>
        function togglePasswordFields() {
            const resetPassword = document.getElementById('reset_password');
            const customPassword = document.getElementById('custom_password');
            const passwordFields = document.getElementById('password_fields');
            
            // If reset password is checked, uncheck custom password
            if (resetPassword.checked && customPassword.checked) {
                customPassword.checked = false;
            }
            
            // If custom password is checked, uncheck reset password
            if (customPassword.checked && resetPassword.checked) {
                resetPassword.checked = false;
            }
            
            // Show password fields only if custom password is checked
            passwordFields.style.display = customPassword.checked ? 'block' : 'none';
        }
    </script>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?> 