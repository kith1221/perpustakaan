<?php
ob_start(); // Tambahkan output buffering untuk menghindari "headers already sent"
// Include required files
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check if student is logged in
if (!isLoggedIn() || isAdmin()) {
    redirect('login.php');
}

// Get student data from session
$siswaNama = $_SESSION['nama'];
$siswaKelas = $_SESSION['kelas'];
$siswaId = $_SESSION['user_id'];

// Get student's overdue books
$overdueBooks = getStudentOverdueBooks($siswaId);
$overdueCount = count($overdueBooks);

// Function to check if current page matches the given page
function isCurrentPage($page) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    return ($currentPage == $page) ? true : false;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Perpustakaan Siswa</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Add SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <style>
        /* Modern Color Scheme - Madrasah Green Theme */
        :root {
            --primary-color: #0D6E37; /* Deep madrasah green */
            --secondary-color: #1A8C4C; /* Lighter green */
            --accent-color: #D4AF37; /* Gold accent */
            --light-color: #F0F5F1;
            --dark-color: #0A4E27; /* Darker green */
            --success-color: #28A745;
            --warning-color: #FFC107;
            --danger-color: #DC3545;
            --white-color: #ffffff;
            --text-color: #333333;
            --grey-color: #757575;
            --border-color: #E1E8ED;
            --card-bg: #FFFFFF;
        }

        /* Header & Navigation */
        .header {
            background-color: var(--primary-color);
            background-image: linear-gradient(135deg, var(--primary-color), var(--dark-color));
            padding: 1rem 0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: linear-gradient(45deg, rgba(255,255,255,0.05) 25%, transparent 25%, transparent 50%, rgba(255,255,255,0.05) 50%, rgba(255,255,255,0.05) 75%, transparent 75%);
            background-size: 4px 4px;
            z-index: 1;
        }

        .header .container {
            position: relative;
            z-index: 2;
        }

        .header .user-actions {
            position: relative;
            display: flex;
            align-items: center;
        }

        .student-app {
            background-color: var(--light-color);
            min-height: 100vh;
            background-image: linear-gradient(135deg, rgba(13, 110, 55, 0.03) 25%, transparent 25%),
                            linear-gradient(225deg, rgba(13, 110, 55, 0.03) 25%, transparent 25%),
                            linear-gradient(45deg, rgba(13, 110, 55, 0.03) 25%, transparent 25%),
                            linear-gradient(315deg, rgba(13, 110, 55, 0.03) 25%, #F0F5F1 25%);
            background-position: 20px 0, 20px 0, 0 0, 0 0;
            background-size: 40px 40px;
            background-repeat: repeat;
        }

        .card {
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-top: 3px solid var(--primary-color);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 7px 20px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: var(--white-color);
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-title {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.25rem;
            margin: 0;
            position: relative;
            padding-left: 15px;
        }

        .card-title::before {
            content: "";
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 70%;
            background-color: var(--primary-color);
            border-radius: 2px;
        }

        .btn {
            border-radius: 6px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: all 0.5s;
        }

        .btn:hover {
            background-color: var(--dark-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .btn:hover::before {
            left: 100%;
        }

        /* Mobile Navigation Styles */
        @media (max-width: 768px) {
            body {
                font-size: 0.95rem;
            }

            .header .container {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .logo {
                margin-bottom: 10px;
                width: 100%;
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 1.3rem;
                font-weight: 600;
                color: var(--white-color);
                letter-spacing: 0.5px;
            }

            .logo::before {
                content: "☪";
                margin-right: 10px;
                font-size: 1.7rem;
                color: var(--accent-color);
            }
            
            .nav {
                display: none;
            }
            
            .nav.active {
                display: block;
            }
            
            .nav ul {
                flex-direction: column;
                width: 100%;
                padding: 0;
                margin: 0;
            }
            
            .nav ul li {
                margin: 0;
                width: 100%;
                border-top: 1px solid rgba(0, 0, 0, 0.1);
                transition: background-color 0.3s ease;
            }
            
            .nav ul li a {
                display: flex;
                align-items: center;
                padding: 15px 20px;
                width: 100%;
                color: #333333;
                transition: all 0.3s ease;
                font-weight: 500;
            }

            .nav ul li a i {
                margin-right: 10px;
                font-size: 18px;
                width: 24px;
                text-align: center;
            }
            
            .mobile-menu-toggle {
                display: block;
                cursor: pointer;
                background: transparent;
                border: none;
                color: var(--white-color);
                font-size: 24px;
                transition: transform 0.3s ease;
                outline: none;
            }

            .mobile-menu-toggle:active {
                transform: scale(0.95);
            }
            
            .nav ul li a:hover, .nav ul li a:active {
                background-color: #f5f5f5;
                padding-left: 25px;
            }

            .active-tab {
                background-color: #e9f7ef;
                border-left: 4px solid var(--accent-color);
            }

            .active-tab a {
                color: var(--primary-color) !important;
                font-weight: 600 !important;
            }

            .alert {
                border-radius: 6px;
                padding: 15px;
                margin-bottom: 20px;
                display: flex;
                align-items: center;
                position: relative;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            }

            .alert i {
                margin-right: 10px;
                font-size: 1.25rem;
            }

            .alert-success {
                background-color: rgba(40, 167, 69, 0.12);
                color: var(--success-color);
                border-left: 4px solid var(--success-color);
            }

            .alert-danger {
                background-color: rgba(220, 53, 69, 0.12);
                color: var(--danger-color);
                border-left: 4px solid var(--danger-color);
            }

            /* Animations */
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }

            /* Main content adjustments */
            .main {
                padding: 1.5rem 0;
            }

            .container {
                padding: 0 15px;
            }

            .card {
                margin-bottom: 20px;
                padding: 15px;
            }

            .card-header {
                padding: 15px;
            }

            .card-title {
                font-size: 1.2rem;
            }

            /* Loading animation */
            .page-loading {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(255, 255, 255, 0.8);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 9999;
                opacity: 1;
            }
        }
        
        /* Customize login page */
        .login-container {
            max-width: 500px;
            margin: 50px auto;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .login-header {
            background-color: var(--primary-color);
            background-image: linear-gradient(135deg, var(--primary-color), var(--dark-color));
            color: var(--white-color);
            padding: 25px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: linear-gradient(45deg, rgba(255,255,255,0.05) 25%, transparent 25%, transparent 50%, rgba(255,255,255,0.05) 50%, rgba(255,255,255,0.05) 75%, transparent 75%);
            background-size: 4px 4px;
            z-index: 1;
        }
        
        .login-logo {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            z-index: 2;
            display: inline-block;
        }

        .login-logo::before {
            content: "☪";
            margin-right: 10px;
            font-size: 1.7rem;
            color: var(--accent-color);
        }
        
        .login-title {
            font-size: 1.2rem;
            font-weight: 400;
            margin: 0;
            position: relative;
            z-index: 2;
        }
        
        .login-body {
            padding: 30px;
            background-color: var(--white-color);
        }
        
        .login-form .form-group {
            margin-bottom: 20px;
        }
        
        .login-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--primary-dark);
        }
        
        .login-form input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .login-form input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(13, 110, 55, 0.1);
            outline: none;
        }
        
        .login-form .btn {
            width: 100%;
            padding: 12px;
            font-size: 1rem;
            font-weight: 600;
            margin-top: 10px;
        }
        
        .login-footer {
            text-align: center;
            padding: 20px;
            background-color: rgba(0, 0, 0, 0.02);
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .login-links a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .login-links a:hover {
            color: var(--dark-color);
            text-decoration: underline;
        }
        
        /* Dashboard custom styles */
        .dashboard-welcome-card {
            background-color: var(--primary-color);
            border-left: 5px solid var(--accent-color);
            padding: 1.5rem;
            margin-bottom: 20px;
            box-shadow: 0 4px 10px rgba(13, 110, 55, 0.15);
        }
        
        .welcome-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--white-color);
            margin-bottom: 0.5rem;
        }
        
        .student-class {
            color: var(--white-color);
            font-size: 1rem;
            margin-bottom: 0;
            opacity: 0.9;
        }

        .student-class i {
            color: var(--accent-color);
            margin-right: 5px;
        }
        
        .student-stats {
            display: flex;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .stat-item {
            background-color: var(--primary-color);
            padding: 10px 15px;
            border-radius: 8px;
            margin-right: 15px;
            margin-bottom: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 120px;
            box-shadow: 0 4px 8px rgba(13, 110, 55, 0.15);
        }
        
        .stat-item i {
            color: var(--white-color);
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .stat-item strong {
            font-size: 1.5rem;
            color: var(--white-color);
            margin-bottom: 3px;
        }
        
        .stat-item span {
            font-size: 0.8rem;
            color: var(--white-color);
            text-align: center;
            font-weight: 500;
        }
        
        /* Reading interest card */
        .reading-interest-card {
            margin-bottom: 20px;
        }
        
        .reading-interest-content {
            padding: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .reading-level {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 0 0 200px;
        }
        
        .level-badge {
            background-color: var(--primary-color);
            color: var(--white-color);
            padding: 15px 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 15px;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 0 5px 15px rgba(13, 110, 55, 0.15);
        }
        
        .level-badge i {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--accent-color);
        }
        
        .level-badge span {
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .points-container {
            background-color: rgba(13, 110, 55, 0.05);
            padding: 10px 15px;
            border-radius: 8px;
            text-align: center;
            width: 100%;
        }
        
        .points-label {
            display: block;
            color: var(--grey-color);
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .points-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .progress-container {
            flex: 1;
            min-width: 250px;
        }
        
        .progress-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .progress-percent {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
            display: block;
        }
        
        .progress-label {
            color: var(--grey-color);
            font-size: 0.9rem;
        }
        
        .progress-counts {
            font-size: 0.9rem;
            color: var(--grey-color);
        }
        
        .progress-bar-container {
            height: 15px;
            background-color: rgba(13, 110, 55, 0.1);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 15px;
        }
        
        .progress-bar {
            height: 100%;
            background-color: var(--primary-color);
            border-radius: 10px;
            transition: width 1s ease;
        }
        
        .progress-info {
            background-color: rgba(13, 110, 55, 0.05);
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .progress-info p {
            margin: 5px 0;
        }
        
        .progress-info i {
            color: var(--primary-color);
        }
        
        /* Empty state */
        .empty-state {
            padding: 30px;
            text-align: center;
            color: var(--grey-color);
        }
        
        .empty-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            color: rgba(13, 110, 55, 0.3);
        }
        
        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--white-color);
        }
        
        /* Card footer */
        .card-footer {
            padding: 15px 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
        }
        
        /* Book card */
        .book-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .book-card {
            background-color: var(--white-color);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }
        
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .book-cover {
            height: 200px;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        
        .book-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .book-card:hover .book-cover img {
            transform: scale(1.05);
        }
        
        .book-info {
            padding: 15px;
        }
        
        .book-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 5px;
            line-height: 1.4;
            height: 2.8em;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .book-category {
            display: inline-block;
            background-color: rgba(13, 110, 55, 0.1);
            color: var(--primary-color);
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-bottom: 10px;
        }
        
        .book-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: var(--grey-color);
        }
        
        .book-author, .book-year {
            display: flex;
            align-items: center;
        }
        
        .book-author i, .book-year i {
            margin-right: 5px;
            color: var(--primary-color);
        }
        
        .book-actions {
            padding: 10px 15px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
        }
        
        .book-actions .btn {
            padding: 6px 12px;
            font-size: 0.85rem;
        }
        
        .book-status {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: var(--primary-color);
            color: var(--white-color);
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            z-index: 2;
        }
        
        .book-status.available {
            background-color: var(--success-color);
        }
        
        .book-status.borrowed {
            background-color: var(--danger-color);
        }
        
        /* Enhanced table styles */
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }
        
        .table th {
            background-color: var(--primary-color);
            color: var(--white-color);
            font-weight: 600;
            padding: 1rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-size: 0.9rem;
        }
        
        .table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        .table tr:hover {
            background-color: rgba(13, 110, 55, 0.05);
        }

        /* Notification bell styles */
        .notification-bell {
            position: relative;
            display: inline-block;
            padding: 0 5px;
            cursor: pointer;
            z-index: 1010;
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
            background: var(--primary-color);
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
            border-left: 3px solid var(--danger-color);
        }
        
        .notification-footer {
            padding: 10px 15px;
            text-align: center;
            border-top: 1px solid #eee;
        }

        .nav {
            width: 100%;
            margin-top: 10px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .nav ul {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
            flex-direction: column;
        }

        .nav ul li {
            margin: 0;
            width: 100%;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s ease;
        }

        .nav ul li:first-child {
            border-top: none;
        }

        .nav ul li a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            width: 100%;
            color: #333333;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav ul li a i {
            margin-right: 10px;
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        .nav ul li a:hover, .nav ul li a:active {
            background-color: #f5f5f5;
            padding-left: 25px;
        }

        .active-tab {
            background-color: #e9f7ef;
            border-left: 4px solid var(--accent-color);
        }

        .active-tab a {
            color: var(--primary-color) !important;
            font-weight: 600 !important;
        }

        /* Responsive Layout */
        .app-container {
            padding: 1.5rem 0;
        }

        .app-wrapper {
            display: flex;
            gap: 20px;
            flex-direction: column;
        }

        /* Desktop Navigation Styles */
        @media (min-width: 769px) {
            .nav {
                display: block;
                margin-bottom: 20px;
                width: 100%;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
                border-radius: 8px;
                overflow: hidden;
                background: #ffffff;
            }
            
            .nav ul {
                display: flex;
                flex-direction: row;
                list-style: none;
                padding: 0;
                margin: 0;
                overflow: hidden;
            }
            
            .nav ul li {
                margin: 0;
                border-top: none;
                border-right: 1px solid rgba(0, 0, 0, 0.1);
                width: auto;
                flex: 1;
                position: relative;
            }
            
            .nav ul li:last-child {
                border-right: none;
            }
            
            .nav ul li a {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 15px 10px;
                text-decoration: none;
                color: #333333;
                font-weight: 500;
                transition: all 0.3s ease;
                text-align: center;
            }
            
            .nav ul li a:hover {
                background-color: #f5f5f5;
            }
            
            .nav ul li a i {
                margin-right: 8px;
            }
            
            .active-tab {
                background-color: #e9f7ef !important;
                position: relative;
            }
            
            .active-tab::after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 0;
                width: 100%;
                height: 3px;
                background-color: var(--accent-color);
            }
            
            .active-tab a {
                color: var(--primary-color) !important;
                font-weight: 600 !important;
            }
        }

        /* Mobile Navigation Styles */
        @media (max-width: 768px) {
            .nav {
                display: none;
            }
            
            .nav.active {
                display: block;
            }
            
            .nav ul {
                flex-direction: column;
            }
            
            .nav ul li {
                width: 100%;
                border-top: 1px solid rgba(0, 0, 0, 0.1);
            }
            
            .nav ul li a {
                justify-content: flex-start;
                padding: 15px 20px;
            }
            
            .nav ul li a i {
                margin-right: 10px;
                width: 24px;
                text-align: center;
            }
            
            .active-tab {
                border-left: 4px solid var(--accent-color);
            }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
            const nav = document.querySelector('.nav');
            
            if (mobileMenuToggle && nav) {
                mobileMenuToggle.addEventListener('click', function() {
                    nav.classList.toggle('active');
                    
                    // Change icon
                    const iconElement = mobileMenuToggle.querySelector('i');
                    if (nav.classList.contains('active')) {
                        iconElement.classList.remove('fa-bars');
                        iconElement.classList.add('fa-times');
                    } else {
                        iconElement.classList.remove('fa-times');
                        iconElement.classList.add('fa-bars');
                    }
                });
            }
            
            // Toggle notification dropdown
            const notificationBell = document.getElementById('notificationBell');
            const notificationDropdown = document.getElementById('notificationDropdown');
            
            if (notificationBell && notificationDropdown) {
                console.log('Notification elements found');
                
                notificationBell.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notificationDropdown.classList.toggle('show');
                    console.log('Notification bell clicked, dropdown state:', notificationDropdown.classList.contains('show'));
                });
                
                // Close notification dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!notificationDropdown.contains(e.target) && e.target !== notificationBell && !notificationBell.contains(e.target)) {
                        notificationDropdown.classList.remove('show');
                    }
                });
            } else {
                console.log('Notification elements NOT found:', { 
                    bell: Boolean(notificationBell), 
                    dropdown: Boolean(notificationDropdown) 
                });
            }

            // Handle logout confirmation
            const logoutLink = document.querySelector('a[href="../includes/logout.php"]');
            if (logoutLink) {
                logoutLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Konfirmasi Logout',
                        text: 'Apakah Anda yakin ingin keluar dari sistem?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#0D6E37',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, Logout',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = this.href;
                        }
                    });
                });
            }
        });
    </script>
    <!-- Add SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
</head>
<body class="student-app">
    <header class="header">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
            <div class="logo">
                <span style="color: var(--white-color); font-weight: 600; font-size: 1.2rem;">Perpustakaan Madrasah</span>
                <button class="mobile-menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <div class="user-actions">
                <!-- Notification Bell -->
                <div class="notification-bell" id="notificationBell" style="margin-right: 15px; position: relative;">
                    <i class="fas fa-bell" style="font-size: 1.25rem; color: var(--white-color);"></i>
                    <?php if ($overdueCount > 0): ?>
                    <span class="notification-badge"><?php echo $overdueCount; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="user-info">
                    <i class="fas fa-user-circle" style="font-size: 1.25rem; margin-right: 8px; color: var(--white-color);"></i>
                    <span style="color: var(--white-color); font-weight: 500;"><?php echo htmlspecialchars($siswaNama); ?></span>
                </div>
            </div>
        </div>
    </header>

    <!-- Notification Dropdown -->
    <div class="notification-dropdown" id="notificationDropdown">
        <div class="notification-header">
            <span>Notifikasi</span>
            <span><?php echo $overdueCount; ?> Buku</span>
        </div>
        <div class="notification-body">
            <?php if ($overdueCount > 0): ?>
                <?php foreach($overdueBooks as $book): ?>
                <div class="notification-item unread">
                    <div style="display: flex; align-items: center;">
                        <div style="margin-right: 10px; color: var(--danger-color);">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div>
                            <div style="font-weight: bold; color: var(--danger-color);">
                                Buku Terlambat: <?php echo htmlspecialchars($book['judul']); ?>
                            </div>
                            <div style="font-size: 0.9rem; color: #666;">
                                Terlambat <?php echo $book['days_overdue']; ?> hari. Harap segera dikembalikan.
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="notification-item">
                <div style="text-align: center; padding: 20px 0;">
                    <i class="fas fa-check-circle" style="font-size: 2rem; color: var(--success-color); margin-bottom: 10px;"></i>
                    <div>Tidak ada buku yang terlambat.</div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="notification-footer">
            <?php if ($overdueCount > 0): ?>
            <a href="peminjaman.php" style="color: var(--primary-color); text-decoration: none; font-weight: bold;">
                Lihat peminjaman
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="app-container">
        <div class="container">
            <div class="app-wrapper">
                <!-- Navigation Sidebar -->
                <div class="nav" id="mobileNav">
                    <ul>
                        <li <?php echo isCurrentPage('index.php') ? 'class="active-tab"' : ''; ?>>
                            <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                        </li>
                        <li <?php echo isCurrentPage('peminjaman.php') ? 'class="active-tab"' : ''; ?>>
                            <a href="peminjaman.php"><i class="fas fa-book"></i> Peminjaman Buku</a>
                        </li>
                        <li <?php echo isCurrentPage('riwayat.php') ? 'class="active-tab"' : ''; ?>>
                            <a href="riwayat.php"><i class="fas fa-history"></i> Riwayat Peminjaman</a>
                        </li>
                        <li <?php echo isCurrentPage('profile.php') ? 'class="active-tab"' : ''; ?>>
                            <a href="profile.php"><i class="fas fa-user"></i> Profil</a>
                        </li>
                        <li>
                            <a href="../includes/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </li>
                    </ul>
                </div>
                
                <!-- Main Content Area -->
                <div class="main-content">
                    <?php if (isset($pageTitle) && $pageTitle !== 'Dashboard Siswa'): ?>
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title"><?php echo $pageTitle; ?></h2>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['alert'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['alert_type']; ?>">
                            <?php if ($_SESSION['alert_type'] == 'success'): ?>
                                <i class="fas fa-check-circle"></i>
                            <?php else: ?>
                                <i class="fas fa-exclamation-circle"></i>
                            <?php endif; ?>
                            <?php 
                                echo $_SESSION['alert']; 
                                unset($_SESSION['alert']);
                                unset($_SESSION['alert_type']);
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 