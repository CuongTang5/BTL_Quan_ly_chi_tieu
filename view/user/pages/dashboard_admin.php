<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/Quanlychitieu/function/db_connection.php";
$conn = getDbConnection();

// Thống kê tổng quan
$users_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$transactions_count = $conn->query("SELECT COUNT(*) as count FROM transactions")->fetch_assoc()['count'];
$categories_count = $conn->query("SELECT COUNT(*) as count FROM categories")->fetch_assoc()['count'];
$total_amount = $conn->query("SELECT SUM(amount) as total FROM transactions")->fetch_assoc()['total'] ?? 0;

// Người dùng mới trong tháng
// dashboard_admin.php, dòng 12
$sql = "SELECT COUNT(*) FROM users WHERE registration_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)"; 
$result = $mysqli->query($sql); 
// ...
// Giao dịch trong tháng
$monthly_transactions = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE date >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['count'];

$conn->close();
?>

<div class="dashboard-admin">
    <!-- Thống kê nhanh -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3>Tổng người dùng</h3>
                <p class="stat-value"><?php echo $users_count; ?></p>
                <p class="stat-desc"><?php echo $new_users; ?> người dùng mới</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon secondary">
                <i class="fas fa-receipt"></i>
            </div>
            <div class="stat-info">
                <h3>Tổng giao dịch</h3>
                <p class="stat-value"><?php echo $transactions_count; ?></p>
                <p class="stat-desc"><?php echo $monthly_transactions; ?> giao dịch tháng này</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-folder"></i>
            </div>
            <div class="stat-info">
                <h3>Danh mục</h3>
                <p class="stat-value"><?php echo $categories_count; ?></p>
                <p class="stat-desc">Danh mục chi tiêu</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-info">
                <h3>Tổng chi tiêu</h3>
                <p class="stat-value"><?php echo number_format($total_amount, 0, ',', '.'); ?>₫</p>
                <p class="stat-desc">Tất cả giao dịch</p>
            </div>
        </div>
    </div>

    <!-- Các chức năng nhanh -->
    <div class="quick-actions">
        <h2>Chức năng nhanh</h2>
        <div class="actions-grid">
            <a href="admin.php?page=users" class="action-card">
                <i class="fas fa-user-plus"></i>
                <span>Thêm người dùng</span>
            </a>
            <a href="admin.php?page=categories" class="action-card">
                <i class="fas fa-folder-plus"></i>
                <span>Thêm danh mục</span>
            </a>
            <a href="admin.php?page=reports" class="action-card">
                <i class="fas fa-chart-bar"></i>
                <span>Xem báo cáo</span>
            </a>
            <a href="admin.php?page=transactions" class="action-card">
                <i class="fas fa-search"></i>
                <span>Xem giao dịch</span>
            </a>
        </div>
    </div>

    <!-- Hoạt động gần đây -->
    <div class="recent-activity">
        <div class="table-container">
            <div class="table-header">
                <h2>Hoạt động gần đây</h2>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Người dùng</th>
                            <th>Hành động</th>
                            <th>Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo date('H:i d/m/Y'); ?></td>
                            <td>Admin</td>
                            <td>Đăng nhập</td>
                            <td>Truy cập hệ thống</td>
                        </tr>
                        <!-- Có thể thêm dữ liệu thực từ bảng logs sau -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.quick-actions {
    margin-bottom: 30px;
}

.quick-actions h2 {
    font-size: 20px;
    margin-bottom: 20px;
    color: #2d3748;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.action-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    text-decoration: none;
    color: #4a5568;
    text-align: center;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transition: all 0.3s;
    border: 2px solid transparent;
}

.action-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.1);
    border-color: var(--primary-color);
    color: var(--primary-color);
}

.action-card i {
    font-size: 32px;
    margin-bottom: 10px;
    display: block;
}

.action-card span {
    font-weight: 500;
    font-size: 14px;
}
</style>