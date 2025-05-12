<?php
ob_start(); // Tambahkan output buffering untuk menghindari "headers already sent"
// Include required files
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Get admin data from session
$adminNama = $_SESSION['nama'];

// Get count of overdue books
$overdueCount = countOverdueBooks();
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Sistem Perpustakaan</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Vendor CSS-->
    <style>
        /* Notification bell styles */
        .notification-bell {
            position: relative;
            padding: 0 8px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .notification-bell:hover {
            transform: scale(1.1);
        }
        
        .notification-badge {
            position: absolute;
            top: -8px;
            right: 0;
            background-color: #DC3545;
            color: white;
            border-radius: 50%;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: bold;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Notification dropdown */
        .notification-dropdown {
            position: fixed;
            top: 120px;
            right: 20px;
            width: 300px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            z-index: 1001;
            display: none;
            overflow: hidden;
        }
        
        .notification-dropdown.show {
            display: block;
        }
        
        .notification-header {
            padding: 10px 15px;
            background: #0D6E37;
            color: white;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .notification-body {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .notification-item {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s;
        }
        
        .notification-item:hover {
            background-color: #f5f5f5;
        }
        
        .notification-item.unread {
            border-left: 3px solid #DC3545;
        }
        
        .notification-footer {
            padding: 10px 15px;
            text-align: center;
            border-top: 1px solid #eee;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    
                    // Change icon
                    const iconElement = sidebarToggle.querySelector('i');
                    if (sidebar.classList.contains('active')) {
                        iconElement.classList.remove('fa-bars');
                        iconElement.classList.add('fa-times');
                    } else {
                        iconElement.classList.remove('fa-times');
                        iconElement.classList.add('fa-bars');
                    }
                });
            }
            
            // Hide sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                const isMobile = window.innerWidth <= 768;
                if (isMobile && 
                    sidebar.classList.contains('active') && 
                    !sidebar.contains(event.target) &&
                    event.target !== sidebarToggle &&
                    !sidebarToggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                    
                    // Reset icon
                    const iconElement = sidebarToggle.querySelector('i');
                    iconElement.classList.remove('fa-times');
                    iconElement.classList.add('fa-bars');
                }
            });
            
            // Toggle notification dropdown
            const notificationBell = document.getElementById('notificationBell');
            const notificationDropdown = document.getElementById('notificationDropdown');
            
            if (notificationBell && notificationDropdown) {
                notificationBell.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notificationDropdown.classList.toggle('show');
                });
                
                // Close notification dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!notificationDropdown.contains(e.target) && e.target !== notificationBell) {
                        notificationDropdown.classList.remove('show');
                    }
                });
            }
        });
    </script>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
</head>
<body id="admin-body">
    <!-- Mobile Toggle Button -->
    <button class="toggle-sidebar" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Notification Dropdown -->
    <div class="notification-dropdown" id="notificationDropdown">
        <div class="notification-header">
            <span>Notifikasi</span>
            <span><?php echo $overdueCount; ?> Baru</span>
        </div>
        <div class="notification-body">
            <?php if ($overdueCount > 0): ?>
            <div class="notification-item unread">
                <div style="display: flex; align-items: center;">
                    <div style="margin-right: 10px; color: #DC3545;">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div>
                        <div style="font-weight: bold; color: #DC3545;">
                            Keterlambatan Pengembalian Buku
                        </div>
                        <div style="font-size: 0.9rem; color: #666;">
                            Terdapat <?php echo $overdueCount; ?> buku yang terlambat dikembalikan.
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="notification-item">
                <div style="text-align: center; padding: 20px 0;">
                    <i class="fas fa-check-circle" style="font-size: 2rem; color: #28A745; margin-bottom: 10px;"></i>
                    <div>Tidak ada keterlambatan pengembalian buku.</div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="notification-footer">
            <?php if ($overdueCount > 0): ?>
            <a href="peminjaman.php?filter=active&late=true" style="color: #0D6E37; text-decoration: none; font-weight: bold;">
                Lihat semua keterlambatan
            </a>
            <?php endif; ?>
        </div>
    </div>

    <nav id="sidebar">
        <div class="sidebar-header">
            <h3 class="title">Perpustakaan</h3>
            <p class="subtitle">Madrasah Nurul Falah</p>
        </div>
        
        <div class="nav">
            <ul>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active-tab' : ''; ?>">
                    <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'siswa.php' ? 'active-tab' : ''; ?>">
                    <a href="siswa.php"><i class="fas fa-users"></i> Daftar Siswa</a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'buku.php' ? 'active-tab' : ''; ?>">
                    <a href="buku.php"><i class="fas fa-book"></i> Daftar Buku</a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'peminjaman.php' ? 'active-tab' : ''; ?>">
                    <a href="peminjaman.php"><i class="fas fa-book-reader"></i> Peminjaman</a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'riwayat.php' ? 'active-tab' : ''; ?>">
                    <a href="riwayat.php"><i class="fas fa-history"></i> Riwayat</a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'active-tab' : ''; ?>">
                    <a href="logs.php"><i class="fas fa-clipboard-list"></i> Log Aktivitas</a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'minat_baca.php' ? 'active-tab' : ''; ?>">
                    <a href="minat_baca.php"><i class="fas fa-chart-pie"></i> Minat Baca</a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'aktifitas_membaca.php' ? 'active-tab' : ''; ?>">
                    <a href="aktifitas_membaca.php"><i class="fas fa-chart-line"></i> Aktifitas Membaca</a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active-tab' : ''; ?>">
                    <a href="profile.php"><i class="fas fa-user"></i> Profil</a>
                </li>
                <li>
                    <a href="../includes/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="admin-content"><?php /* This div is closed in footer.php */ ?> 