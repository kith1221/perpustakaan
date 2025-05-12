<?php
ob_start(); // Tambahkan output buffering untuk menghindari "headers already sent"
// Start session
session_start();

// Include required files
require_once 'config.php';
require_once 'functions.php';

// Function to authenticate admin
function adminLogin($username, $password) {
    global $conn;
    
    $username = cleanInput($username);
    
    $query = "SELECT * FROM admin WHERE username = '$username'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $admin = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $admin['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['nama'] = $admin['nama'];
            $_SESSION['role'] = 'admin';
            
            return true;
        }
    }
    
    return false;
}

// Function to authenticate student
function siswaLogin($nisn, $password) {
    global $conn;
    
    $nisn = cleanInput($nisn);
    
    $query = "SELECT * FROM siswa WHERE nisn = '$nisn'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $siswa = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $siswa['password'])) {
            // Set session variables with latest data from database
            $_SESSION['user_id'] = $siswa['id'];
            $_SESSION['nisn'] = $siswa['nisn'];
            $_SESSION['nama'] = $siswa['nama'];
            $_SESSION['kelas'] = $siswa['kelas'];
            $_SESSION['role'] = 'siswa';
            
            return true;
        }
    }
    
    return false;
}

// Function to log out
function logout() {
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
    
    return true;
}
?> 