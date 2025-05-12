<?php
// Set page title
$pageTitle = 'Profil Siswa';

// Include header
require_once 'includes/header.php';

// Initialize variables
$error = '';
$success = '';

// Get student data
$siswa_id = $_SESSION['user_id'];
$query = "SELECT * FROM siswa WHERE id = $siswa_id";
$result = mysqli_query($conn, $query);
$siswa = mysqli_fetch_assoc($result);

// Refresh session with current data from database
$_SESSION['nama'] = $siswa['nama'];
$_SESSION['nisn'] = $siswa['nisn'];
$_SESSION['kelas'] = $siswa['kelas'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate form data
    if (!empty($old_password)) {
        // Verify old password
        if (!password_verify($old_password, $siswa['password'])) {
            $error = 'Password lama tidak sesuai';
        } elseif (empty($new_password)) {
            $error = 'Password baru tidak boleh kosong';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password baru minimal 6 karakter';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Konfirmasi password baru tidak sesuai';
        } else {
            // Update student with new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $query = "UPDATE siswa SET password = '$hashed_password' WHERE id = $siswa_id";
            
            // Execute update if no error
            if (mysqli_query($conn, $query)) {
                $success = 'Password berhasil diperbarui';
                
                // Refresh student data
                $query = "SELECT * FROM siswa WHERE id = $siswa_id";
                $result = mysqli_query($conn, $query);
                $siswa = mysqli_fetch_assoc($result);
            } else {
                $error = 'Gagal memperbarui password: ' . mysqli_error($conn);
            }
        }
    } else {
        $error = 'Harap isi password lama untuk mengubah password';
    }
}
?>

<!-- Profile Form -->
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
            <label for="nisn">NISN</label>
            <input type="text" id="nisn" value="<?php echo htmlspecialchars($siswa['nisn']); ?>" readonly disabled>
            <small style="display: block; margin-top: 5px; color: #666;">NISN tidak dapat diubah</small>
        </div>
        
        <div class="form-group">
            <label for="nama">Nama Lengkap</label>
            <input type="text" id="nama" value="<?php echo htmlspecialchars($siswa['nama']); ?>" readonly disabled>
            <small style="display: block; margin-top: 5px; color: #666;">Nama lengkap hanya dapat diubah oleh Admin</small>
        </div>
        
        <div class="form-group">
            <label for="kelas">Kelas</label>
            <input type="text" id="kelas" value="<?php echo htmlspecialchars($siswa['kelas']); ?>" readonly disabled>
            <small style="display: block; margin-top: 5px; color: #666;">Kelas hanya dapat diubah oleh Admin</small>
        </div>
        
        <hr style="margin: 20px 0;">
        
        <h3 style="font-size: 1.2rem; margin-bottom: 15px;">Ubah Password</h3>
        
        <div class="form-group">
            <label for="old_password">Password Lama <span style="color: red;">*</span></label>
            <input type="password" id="old_password" name="old_password" required>
        </div>
        
        <div class="form-group">
            <label for="new_password">Password Baru <span style="color: red;">*</span></label>
            <input type="password" id="new_password" name="new_password" required>
            <small style="display: block; margin-top: 5px; color: #666;">Minimal 6 karakter</small>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Konfirmasi Password Baru <span style="color: red;">*</span></label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        
        <div class="form-group" style="display: flex; gap: 10px;">
            <button type="submit" class="btn">Ubah Password</button>
        </div>
    </form>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?> 