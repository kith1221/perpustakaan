<?php
// Include required files
require_once 'includes/header.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_students'])) {
    $selectedStudents = array_map('intval', $_POST['selected_students']);
    $successCount = 0;
    $errorCount = 0;
    $errorMessages = [];

    // Begin transaction
    mysqli_begin_transaction($conn);

    try {
        // First, check all students for active loans
        $activeLoans = [];
        foreach ($selectedStudents as $studentId) {
            $query = "SELECT s.id, s.nama, s.nisn, COUNT(p.id) as active_loans 
                     FROM siswa s 
                     LEFT JOIN peminjaman p ON s.id = p.siswa_id AND p.status = 'dipinjam'
                     WHERE s.id = $studentId
                     GROUP BY s.id";
            $result = mysqli_query($conn, $query);
            
            if ($result && $row = mysqli_fetch_assoc($result)) {
                if ($row['active_loans'] > 0) {
                    $activeLoans[] = $row;
                    $errorCount++;
                    $errorMessages[] = "Siswa {$row['nama']} (NISN: {$row['nisn']}) tidak dapat dihapus karena memiliki peminjaman aktif";
                }
            }
        }

        // If there are no active loans, proceed with deletion
        if (empty($activeLoans)) {
            // Delete all selected students
            $ids = implode(',', $selectedStudents);
            $query = "DELETE FROM siswa WHERE id IN ($ids)";
            
            if (mysqli_query($conn, $query)) {
                $successCount = count($selectedStudents);
                mysqli_commit($conn);
            } else {
                throw new Exception("Gagal menghapus siswa: " . mysqli_error($conn));
            }
        } else {
            // Rollback if there are active loans
            mysqli_rollback($conn);
        }

        // Set appropriate alert message
        if ($successCount > 0 && $errorCount === 0) {
            $_SESSION['alert'] = "Berhasil menghapus $successCount siswa";
            $_SESSION['alert_type'] = 'success';
        } elseif ($successCount > 0 && $errorCount > 0) {
            $_SESSION['alert'] = "Berhasil menghapus $successCount siswa, gagal menghapus $errorCount siswa";
            $_SESSION['alert_type'] = 'warning';
        } else {
            $_SESSION['alert'] = "Gagal menghapus siswa";
            $_SESSION['alert_type'] = 'danger';
        }

        // Add error messages to session if any
        if (!empty($errorMessages)) {
            $_SESSION['error_details'] = $errorMessages;
        }

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['alert'] = "Terjadi kesalahan: " . $e->getMessage();
        $_SESSION['alert_type'] = 'danger';
    }
} else {
    $_SESSION['alert'] = "Tidak ada siswa yang dipilih untuk dihapus";
    $_SESSION['alert_type'] = 'warning';
}

// Redirect back to students page
redirect('siswa.php');
?> 