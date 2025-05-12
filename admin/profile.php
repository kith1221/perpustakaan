<?php
// Set page title
$pageTitle = 'Profil Admin';

// Include header
require_once 'includes/header.php';

// Initialize variables
$error = '';
$success = '';

// Get admin data
$admin_id = $_SESSION['user_id'];
$query = "SELECT * FROM admin WHERE id = $admin_id";
$result = mysqli_query($conn, $query);
$admin = mysqli_fetch_assoc($result);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $nama = cleanInput($_POST['nama']);
    $username = cleanInput($_POST['username']);
    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';
    
    // Validate inputs
    if (empty($nama) || empty($username)) {
        $error = "Nama dan username tidak boleh kosong!";
    } else {
        // Check if username already exists (excluding current admin)
        $checkUsername = mysqli_query($conn, "SELECT * FROM admin WHERE username = '$username' AND id != $admin_id");
        if (mysqli_num_rows($checkUsername) > 0) {
            $error = "Username sudah digunakan!";
        } else {
            // Start with basic update query
            $updateQuery = "UPDATE admin SET nama = '$nama', username = '$username'";
            
            // If password change is requested
            if (!empty($password_lama) || !empty($password_baru) || !empty($konfirmasi_password)) {
                // Verify all password fields are filled
                if (empty($password_lama) || empty($password_baru) || empty($konfirmasi_password)) {
                    $error = "Semua field password harus diisi jika ingin mengubah password!";
                } 
                // Verify password confirmation matches
                elseif ($password_baru !== $konfirmasi_password) {
                    $error = "Konfirmasi password baru tidak cocok!";
                } 
                // Verify old password
                else {
                    // Get current password from database
                    $passwordQuery = "SELECT password FROM admin WHERE id = $admin_id";
                    $passwordResult = mysqli_query($conn, $passwordQuery);
                    $currentPassword = mysqli_fetch_assoc($passwordResult)['password'];
                    
                    // Check if old password matches
                    if (password_verify($password_lama, $currentPassword)) {
                        // Check password length
                        if (strlen($password_baru) < 6) {
                            $error = "Password baru minimal 6 karakter!";
                        } else {
                            // Hash new password
                            $hashedPassword = password_hash($password_baru, PASSWORD_DEFAULT);
                            
                            // Add password to update query
                            $updateQuery .= ", password = '$hashedPassword'";
                        }
                    } else {
                        $error = "Password lama tidak valid!";
                    }
                }
            }
            
            // If no errors occurred, execute update
            if (empty($error)) {
                $updateQuery .= " WHERE id = $admin_id";
                if (mysqli_query($conn, $updateQuery)) {
                    $success = "Profil berhasil diperbarui!";
                    
                    // Refresh admin data
                    $result = mysqli_query($conn, "SELECT * FROM admin WHERE id = $admin_id");
                    $admin = mysqli_fetch_assoc($result);
                    
                    // Update session with new admin name
                    $_SESSION['nama'] = $admin['nama'];
                } else {
                    $error = "Gagal memperbarui profil: " . mysqli_error($conn);
                }
            }
        }
    }
}
?>

<!-- Profile Form -->
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4>Profil Admin</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="mb-3">
                            <label for="nama" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="nama" name="nama" value="<?= $admin['nama'] ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?= $admin['username'] ?>" required>
                        </div>
                        
                        <hr>
                        <h5>Ubah Password</h5>
                        <p class="text-muted small">Kosongkan field password jika tidak ingin mengubah password.</p>
                        
                        <div class="mb-3">
                            <label for="password_lama" class="form-label">Password Lama</label>
                            <input type="password" class="form-control" id="password_lama" name="password_lama">
                        </div>
                        
                        <div class="mb-3">
                            <label for="password_baru" class="form-label">Password Baru</label>
                            <input type="password" class="form-control" id="password_baru" name="password_baru">
                            <small class="text-muted">Minimal 6 karakter</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="konfirmasi_password" class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password">
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?> 