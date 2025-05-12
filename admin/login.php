<?php
// Include required files
require_once '../includes/auth.php';

// Check if admin is already logged in
if (isLoggedIn() && isAdmin()) {
    redirect('index.php');
}

// Initialize error message
$error = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = cleanInput($_POST['username']);
    $password = $_POST['password'];
    
    // Validate form data
    if (empty($username) || empty($password)) {
        $error = 'Harap isi semua field';
    } else {
        // Attempt to login
        if (adminLogin($username, $password)) {
            // Redirect to admin dashboard
            redirect('index.php');
        } else {
            $error = 'Username atau password salah';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Sistem Informasi Perpustakaan</title>
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
                    <h2 class="login-title">Login Admin</h2>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="" method="post">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
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