<?php
// Include database configuration
require_once 'config.php';

// Function to clean input data
function cleanInput($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

// Function to redirect
function redirect($url) {
    // If headers are already sent, use JavaScript redirect
    if (headers_sent()) {
        echo "<script>window.location.href='$url';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=$url'></noscript>";
        exit;
    } else {
        header("Location: $url");
        exit();
    }
}

// Function to get student details by ID
function getStudentById($id) {
    global $conn;
    $id = (int)$id;
    $query = "SELECT * FROM siswa WHERE id = $id";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

// Function to get book details by ID
function getBookById($id) {
    global $conn;
    $id = (int)$id;
    $query = "SELECT * FROM buku WHERE id = $id";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

// Function to get active loans by student ID
function getActiveLoans($studentId) {
    global $conn;
    $studentId = (int)$studentId;
    $query = "SELECT p.*, b.judul FROM peminjaman p 
              JOIN buku b ON p.buku_id = b.id 
              WHERE p.siswa_id = $studentId AND p.status = 'dipinjam'";
    $result = mysqli_query($conn, $query);
    
    $loans = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $loans[] = $row;
    }
    
    return $loans;
}

// Function to get loan history by student ID
function getLoanHistory($studentId) {
    global $conn;
    $studentId = (int)$studentId;
    $query = "SELECT p.*, b.judul FROM peminjaman p 
              JOIN buku b ON p.buku_id = b.id 
              WHERE p.siswa_id = $studentId 
              ORDER BY p.tanggal_pinjam DESC";
    $result = mysqli_query($conn, $query);
    
    $history = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $history[] = $row;
    }
    
    return $history;
}

// Function to get all loan history (for admin)
function getAllLoanHistory($month = null, $year = null) {
    global $conn;
    $query = "SELECT p.*, b.judul, s.nama as nama_siswa, s.kelas
              FROM peminjaman p 
              JOIN buku b ON p.buku_id = b.id 
              JOIN siswa s ON p.siswa_id = s.id";
              
    // Add filters if provided
    $conditions = [];
    
    if ($month !== null) {
        $conditions[] = "MONTH(p.tanggal_pinjam) = " . (int)$month;
    }
    
    if ($year !== null) {
        $conditions[] = "YEAR(p.tanggal_pinjam) = " . (int)$year;
    }
    
    // Add WHERE clause if there are conditions
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $query .= " ORDER BY p.tanggal_pinjam DESC";
    $result = mysqli_query($conn, $query);
    
    $history = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $history[] = $row;
        }
    }
    
    return $history;
}

// Function to format date
function formatDate($date) {
    return date('d-m-Y', strtotime($date));
}

// Function to count overdue books
function countOverdueBooks() {
    global $conn;
    $query = "SELECT COUNT(*) as total FROM peminjaman 
              WHERE status = 'dipinjam' 
              AND DATE_ADD(tanggal_pinjam, INTERVAL lama_pinjam DAY) < CURDATE()";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        $data = mysqli_fetch_assoc($result);
        return $data['total'] ?: 0;
    }
    
    return 0;
}

// Function to get student overdue books
function getStudentOverdueBooks($studentId) {
    global $conn;
    $studentId = (int)$studentId;
    $query = "SELECT p.*, b.judul, 
              DATEDIFF(CURDATE(), DATE_ADD(p.tanggal_pinjam, INTERVAL p.lama_pinjam DAY)) as days_overdue
              FROM peminjaman p 
              JOIN buku b ON p.buku_id = b.id 
              WHERE p.siswa_id = $studentId 
              AND p.status = 'dipinjam' 
              AND DATE_ADD(p.tanggal_pinjam, INTERVAL p.lama_pinjam DAY) < CURDATE()";
    $result = mysqli_query($conn, $query);
    
    $overdueBooks = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $overdueBooks[] = $row;
        }
    }
    
    return $overdueBooks;
}

// Function to get all overdue books for admin
function getAllOverdueBooks() {
    global $conn;
    $query = "SELECT p.*, s.nama as siswa_nama, s.kelas as siswa_kelas, s.nisn as siswa_nisn,
              b.judul as buku_judul, 
              DATEDIFF(CURDATE(), DATE_ADD(p.tanggal_pinjam, INTERVAL p.lama_pinjam DAY)) as days_overdue
              FROM peminjaman p 
              JOIN siswa s ON p.siswa_id = s.id 
              JOIN buku b ON p.buku_id = b.id 
              WHERE p.status = 'dipinjam' 
              AND DATE_ADD(p.tanggal_pinjam, INTERVAL p.lama_pinjam DAY) < CURDATE()
              ORDER BY days_overdue DESC";
    $result = mysqli_query($conn, $query);
    
    $overdueBooks = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $overdueBooks[] = $row;
        }
    }
    
    return $overdueBooks;
} 