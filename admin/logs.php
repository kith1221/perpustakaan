<?php
// Set page title
$pageTitle = 'Log Aktivitas';

// Include header
require_once 'includes/header.php';

// Handle pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Handle filtering
$filter_type = isset($_GET['type']) ? cleanInput($_GET['type']) : '';
$filter_condition = '';
if (!empty($filter_type)) {
    $filter_condition = "WHERE activity_type = '$filter_type'";
}

// Get log entries
$query = "SELECT l.*, a.username as admin_username 
          FROM activity_log l 
          LEFT JOIN admin a ON l.user_id = a.id 
          $filter_condition
          ORDER BY l.created_at DESC 
          LIMIT $offset, $per_page";
$result = mysqli_query($conn, $query);
$logs = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $logs[] = $row;
    }
}

// Get total log entries for pagination
$count_query = "SELECT COUNT(*) as total FROM activity_log $filter_condition";
$count_result = mysqli_query($conn, $count_query);
$total_logs = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_logs / $per_page);

// Get all available log types for filter
$types_query = "SELECT DISTINCT activity_type FROM activity_log ORDER BY activity_type";
$types_result = mysqli_query($conn, $types_query);
$log_types = [];

if ($types_result) {
    while ($row = mysqli_fetch_assoc($types_result)) {
        $log_types[] = $row['activity_type'];
    }
}
?>

<!-- Filter and controls -->
<div style="display: flex; justify-content: space-between; margin-bottom: 15px; flex-wrap: wrap;">
    <div>
        <form action="" method="get" style="display: flex; align-items: center;">
            <label for="type" style="margin-right: 10px;">Filter:</label>
            <select name="type" id="type" style="margin-right: 10px;">
                <option value="">Semua Aktivitas</option>
                <?php foreach ($log_types as $type): ?>
                    <option value="<?php echo $type; ?>" <?php echo $filter_type == $type ? 'selected' : ''; ?>>
                        <?php echo ucfirst(str_replace('_', ' ', $type)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn">Filter</button>
            <?php if (!empty($filter_type)): ?>
                <a href="logs.php" class="btn btn-secondary" style="margin-left: 5px;">Reset</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Logs Table -->
<div class="card">
    <?php if (empty($logs)): ?>
        <div style="padding: 20px; text-align: center;">
            <i class="fas fa-info-circle" style="font-size: 24px; color: #6c757d; margin-bottom: 10px;"></i>
            <p>Tidak ada log aktivitas yang ditemukan.</p>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Waktu</th>
                        <th>Admin</th>
                        <th>Jenis Aktivitas</th>
                        <th>Detail</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo $log['id']; ?></td>
                            <td><?php echo date('d-m-Y H:i:s', strtotime($log['created_at'])); ?></td>
                            <td>
                                <?php 
                                if (!empty($log['admin_username'])) {
                                    echo htmlspecialchars($log['admin_username']);
                                } else {
                                    echo '<span style="color: #6c757d;">System</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                $type_display = ucfirst(str_replace('_', ' ', $log['activity_type']));
                                $type_color = '';
                                
                                switch ($log['activity_type']) {
                                    case 'stock_update':
                                        $type_color = 'background-color: #e3f2fd; color: #0d47a1;';
                                        break;
                                    case 'system':
                                        $type_color = 'background-color: #f5f5f5; color: #424242;';
                                        break;
                                    case 'error':
                                        $type_color = 'background-color: #ffebee; color: #c62828;';
                                        break;
                                    default:
                                        $type_color = '';
                                }
                                ?>
                                <span style="padding: 3px 8px; border-radius: 4px; font-size: 0.85rem; <?php echo $type_color; ?>">
                                    <?php echo $type_display; ?>
                                </span>
                            </td>
                            <td style="max-width: 500px; word-wrap: break-word;"><?php echo htmlspecialchars($log['details']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div style="margin-top: 20px; text-align: center;">
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo !empty($filter_type) ? '&type=' . urlencode($filter_type) : ''; ?>">&laquo; Prev</a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1) {
                        echo '<a href="?page=1' . (!empty($filter_type) ? '&type=' . urlencode($filter_type) : '') . '">1</a>';
                        if ($start_page > 2) {
                            echo '<span>...</span>';
                        }
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        $active = $i == $page ? 'active' : '';
                        echo '<a href="?page=' . $i . (!empty($filter_type) ? '&type=' . urlencode($filter_type) : '') . '" class="' . $active . '">' . $i . '</a>';
                    }
                    
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<span>...</span>';
                        }
                        echo '<a href="?page=' . $total_pages . (!empty($filter_type) ? '&type=' . urlencode($filter_type) : '') . '">' . $total_pages . '</a>';
                    }
                    ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo !empty($filter_type) ? '&type=' . urlencode($filter_type) : ''; ?>">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
    /* Pagination styles */
    .pagination {
        display: inline-flex;
        background-color: #fff;
        border-radius: 4px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .pagination a, .pagination span {
        color: #333;
        padding: 8px 16px;
        text-decoration: none;
        transition: background-color 0.3s;
        border: 1px solid #ddd;
        margin: 0 2px;
    }
    
    .pagination a.active {
        background-color: var(--primary-color);
        color: white;
        border: 1px solid var(--primary-color);
    }
    
    .pagination a:hover:not(.active) {
        background-color: #f1f1f1;
    }
</style>

<?php
// Include footer
require_once 'includes/footer.php';
?> 