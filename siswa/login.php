<?php
// Include required files
require_once '../includes/auth.php';

// Check if student is already logged in
if (isLoggedIn() && !isAdmin()) {
    redirect('index.php');
}

// Initialize error message
$error = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $nisn = cleanInput($_POST['nisn']);
    $password = $_POST['password'];
    
    // Validate form data
    if (empty($nisn) || empty($password)) {
        $error = 'Harap isi semua field';
    } else {
        // Attempt to login
        if (siswaLogin($nisn, $password)) {
            // Set sessionStorage dan redirect ke dashboard dengan JavaScript
            echo "<script>
                sessionStorage.setItem('justLoggedIn', '1');
                window.location.href = 'index.php';
            </script>";
            exit;
        } else {
            $error = 'NISN atau password salah';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Siswa - Sistem Informasi Perpustakaan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="logo">Perpustakaan Madrasah Nurul Falah</div>
        </div>
    </div>

    <div class="main">
        <div class="container">
            <div class="login-container">
                <div class="card">
                    <h2 class="login-title">Login Siswa</h2>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="" method="post">
                        <div class="form-group">
                            <label for="nisn">NISN</label>
                            <input type="text" id="nisn" name="nisn" required value="<?php echo isset($_POST['nisn']) ? htmlspecialchars($_POST['nisn']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn" style="width: 100%;">Login</button>
                        </div>
                    </form>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="../index.php">Kembali ke Halaman Utama</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Sistem Informasi Perpustakaan - Madrasah Nurul Falah</p>
        </div>
    </div>
</body>
</html> 