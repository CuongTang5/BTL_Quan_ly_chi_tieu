<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../../function/db_connection.php';

if(!isset($_SESSION['user_id'])){
    header("Location: ../auth/login.php");
    exit();
}

$conn = getDbConnection();
$user_id = $_SESSION['user_id'];

// Lấy danh sách chi tiêu
$stmt = $conn->prepare("SELECT * FROM transactions WHERE user_id=? ORDER BY date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Tính tổng chi tiêu
$total_stmt = $conn->prepare("SELECT SUM(amount) as total FROM transactions WHERE user_id=?");
$total_stmt->bind_param("i", $user_id);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_amount = $total_row['total'] ?? 0;

// Đếm số giao dịch
$count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE user_id=?");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$transaction_count = $count_row['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Các khoản chi tiêu - Quản Lý Chi Tiêu</title>
<link rel="stylesheet" href="../../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --primary-color: #2f855a;
    --primary-light: #38a169;
    --primary-dark: #276749;
    --danger-color: #e53e3e;
    --warning-color: #dd6b20;
    --info-color: #3182ce;
    --light-bg: #f7fafc;
    --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --hover-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    background-color: #f0f2f5;
    color: #2d3748;
    line-height: 1.6;
}

/* Header cố định */
header {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 70px;
    background: var(--primary-color);
    color: white;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

header .logo {
    font-weight: 700;
    font-size: 22px;
    display: flex;
    align-items: center;
}

header .logo i {
    margin-right: 10px;
    font-size: 24px;
}

header nav {
    display: flex;
    align-items: center;
}

header nav a {
    color: white;
    text-decoration: none;
    margin-left: 25px;
    font-weight: 500;
    padding: 8px 12px;
    border-radius: 6px;
    transition: background 0.3s;
}

header nav a:hover {
    background: rgba(255, 255, 255, 0.1);
}

header nav a.active {
    background: rgba(255, 255, 255, 0.2);
    font-weight: 600;
}

/* Container */
.container {
    width: 95%;
    max-width: 1200px;
    margin: 0 auto;
    padding-top: 100px;
}

/* Page Header */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 15px;
}

.page-title {
    font-size: 28px;
    color: #2d3748;
    font-weight: 600;
}

/* Stats Cards */
.stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: var(--card-shadow);
    display: flex;
    align-items: center;
    transition: transform 0.3s, box-shadow 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--hover-shadow);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    font-size: 24px;
    color: white;
}

.stat-icon.primary {
    background: var(--primary-color);
}

.stat-icon.danger {
    background: var(--danger-color);
}

.stat-icon.info {
    background: var(--info-color);
}

.stat-info h3 {
    font-size: 14px;
    color: #718096;
    margin-bottom: 5px;
    font-weight: 500;
}

.stat-value {
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 5px;
}

.stat-desc {
    font-size: 12px;
    color: #a0aec0;
}

/* Filters và Actions */
.filters-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
    gap: 15px;
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: var(--card-shadow);
}

.filter-group {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.filter-select {
    padding: 10px 15px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: white;
    font-size: 14px;
    color: #4a5568;
    cursor: pointer;
    transition: border 0.3s;
}

.filter-select:focus {
    outline: none;
    border-color: var(--primary-color);
}

.search-box {
    position: relative;
    display: flex;
    align-items: center;
}

.search-input {
    padding: 10px 15px 10px 40px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    width: 250px;
    font-size: 14px;
    transition: border 0.3s;
}

.search-input:focus {
    outline: none;
    border-color: var(--primary-color);
}

.search-icon {
    position: absolute;
    left: 12px;
    color: #a0aec0;
}

/* Buttons */
.button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--primary-color);
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: background 0.3s, transform 0.2s;
    border: none;
    cursor: pointer;
    font-size: 14px;
}

.button:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    color: white;
}

.button i {
    font-size: 16px;
}

.button.secondary {
    background: #e2e8f0;
    color: #4a5568;
}

.button.secondary:hover {
    background: #cbd5e0;
}

.button.danger {
    background: var(--danger-color);
}

.button.danger:hover {
    background: #c53030;
}

/* Table */
.table-container {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--card-shadow);
    margin-bottom: 30px;
}

.table-wrapper {
    overflow-x: auto;
    width: 100%;
}

.table {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px;
}

.table th {
    background: var(--primary-color);
    color: white;
    padding: 16px 20px;
    text-align: left;
    font-weight: 600;
    font-size: 14px;
}

.table td {
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
    font-size: 14px;
}

.table tr:last-child td {
    border-bottom: none;
}

.table tr:hover {
    background: #f7fafc;
}

/* Category Badge */
.category-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    text-align: center;
}

.category-badge.food {
    background: #fed7d7;
    color: #c53030;
}

.category-badge.transport {
    background: #feebc8;
    color: #dd6b20;
}

.category-badge.shopping {
    background: #c6f6d5;
    color: #276749;
}

.category-badge.bills {
    background: #bee3f8;
    color: #2c5aa0;
}

.category-badge.entertainment {
    background: #e9d8fd;
    color: #6b46c1;
}

.category-badge.other {
    background: #edf2f7;
    color: #4a5568;
}

/* Amount styling */
.amount {
    font-weight: 600;
}

.amount.negative {
    color: var(--danger-color);
}

.amount.positive {
    color: var(--primary-color);
}

/* Actions */
.actions {
    display: flex;
    gap: 10px;
}

.action-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: background 0.3s, transform 0.2s;
    color: white;
    font-size: 14px;
}

.action-btn.edit {
    background: var(--info-color);
}

.action-btn.edit:hover {
    background: #2c5aa0;
    transform: scale(1.1);
}

.action-btn.delete {
    background: var(--danger-color);
}

.action-btn.delete:hover {
    background: #c53030;
    transform: scale(1.1);
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 50px 20px;
    color: #718096;
}

.empty-state i {
    font-size: 64px;
    margin-bottom: 20px;
    color: #cbd5e0;
}

.empty-state h3 {
    font-size: 20px;
    margin-bottom: 10px;
    color: #4a5568;
}

.empty-state p {
    margin-bottom: 20px;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 30px;
}

.pagination a, .pagination span {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
}

.pagination a {
    background: white;
    color: #4a5568;
    box-shadow: var(--card-shadow);
    transition: background 0.3s;
}

.pagination a:hover {
    background: var(--primary-color);
    color: white;
}

.pagination .current {
    background: var(--primary-color);
    color: white;
}

.pagination .disabled {
    background: #e2e8f0;
    color: #a0aec0;
    cursor: not-allowed;
}

/* Footer */
footer {
    text-align: center;
    padding: 20px;
    color: #718096;
    font-size: 14px;
    margin-top: 40px;
}

/* Responsive */
@media (max-width: 768px) {
    header {
        padding: 0 15px;
        height: 60px;
    }
    
    header .logo {
        font-size: 18px;
    }
    
    header nav a {
        margin-left: 15px;
        font-size: 14px;
        padding: 6px 10px;
    }
    
    .container {
        padding-top: 80px;
        width: 98%;
    }
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .filters-actions {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .filter-group {
        width: 100%;
        justify-content: space-between;
    }
    
    .search-input {
        width: 100%;
    }
    
    .table th, .table td {
        padding: 12px 15px;
    }
    
    .stats-cards {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>

<!-- HEADER từ dashboard.php -->
<header>
    <div class="logo">💰 Quản Lý Chi Tiêu</div>
    <nav>
        <a href="/Quanlychitieu/dashboard.php">Trang Chủ</a>
        
        <a href="/Quanlychitieu/view/user/transactions.php">Các Khoản Chi tiêu</a>
        
        <a href="/Quanlychitieu/view/chart.php">Xem biểu đồ</a>
        
        <a href="/Quanlychitieu/view/user/pages/goal.php" class="active">Mục tiêu tiết kiệm</a>
        
        <a href="/Quanlychitieu/view/auth/logout.php">Đăng xuất</a>
    </nav>
</header>

<section class="transactions">
    <div class="container">
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-receipt"></i> Các khoản chi tiêu của bạn</h1>
            <a href="transactions_add.php" class="button">
                <i class="fas fa-plus"></i> Thêm chi tiêu mới
            </a>
        </div>

        <!-- Thống kê nhanh -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-info">
                    <h3>Tổng chi tiêu</h3>
                    <p class="stat-value"><?= number_format($total_amount, 0, ',', '.') ?>₫</p>
                    <p class="stat-desc">Tất cả giao dịch</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-info">
                    <h3>Số giao dịch</h3>
                    <p class="stat-value"><?= $transaction_count ?></p>
                    <p class="stat-desc">Tổng số giao dịch</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-info">
                    <h3>Chi tiêu trung bình</h3>
                    <p class="stat-value">
                        <?= $transaction_count > 0 ? number_format($total_amount / $transaction_count, 0, ',', '.') : 0 ?>₫
                    </p>
                    <p class="stat-desc">Mỗi giao dịch</p>
                </div>
            </div>
        </div>

        <!-- Bộ lọc và tìm kiếm -->
        <div class="filters-actions">
            <div class="filter-group">
                <select class="filter-select">
                    <option>Tất cả danh mục</option>
                    <option>Ăn uống</option>
                    <option>Di chuyển</option>
                    <option>Mua sắm</option>
                    <option>Hóa đơn</option>
                    <option>Giải trí</option>
                    <option>Khác</option>
                </select>
                
                <select class="filter-select">
                    <option>Sắp xếp theo ngày mới nhất</option>
                    <option>Sắp xếp theo ngày cũ nhất</option>
                    <option>Sắp xếp theo số tiền (cao-thấp)</option>
                    <option>Sắp xếp theo số tiền (thấp-cao)</option>
                </select>
                
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Tìm kiếm giao dịch...">
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="#" class="button secondary">
                    <i class="fas fa-download"></i> Xuất file
                </a>
            </div>
        </div>

        <!-- Bảng giao dịch -->
        <div class="table-container">
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ngày</th>
                            <th>Danh mục</th>
                            <th>Mô tả</th>
                            <th>Số tiền</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): 
                            $category_class = strtolower($row['category'] ?? 'other');
                            if (!in_array($category_class, ['food', 'transport', 'shopping', 'bills', 'entertainment'])) {
                                $category_class = 'other';
                            }
                        ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($row['date'])) ?></td>
                            <td>
                                <span class="category-badge <?= $category_class ?>">
                                    <?= htmlspecialchars($row['category']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['description']) ?></td>
                            <td class="amount negative">- <?= number_format($row['amount'], 0, ',', '.') ?>₫</td>
                            <td class="actions">
                                <a href="transactions_edit.php?id=<?= $row['id'] ?>" class="action-btn edit" title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="transactions_delete.php?id=<?= $row['id'] ?>" class="action-btn delete" title="Xóa" onclick="return confirm('Bạn có chắc muốn xóa giao dịch này?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fas fa-receipt"></i>
                                    <h3>Chưa có giao dịch nào</h3>
                                    <p>Bắt đầu theo dõi chi tiêu của bạn bằng cách thêm giao dịch đầu tiên.</p>
                                    <a href="transactions_add.php" class="button">
                                        <i class="fas fa-plus"></i> Thêm giao dịch đầu tiên
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Phân trang (giả lập) -->
        <div class="pagination">
            <a href="#" class="disabled"><i class="fas fa-chevron-left"></i></a>
            <span class="current">1</span>
            <a href="#">2</a>
            <a href="#">3</a>
            <a href="#"><i class="fas fa-chevron-right"></i></a>
        </div>
        
        <!-- FOOTER từ dashboard.php -->
<footer>
    <div class="footer-content">
        <div class="footer-section">
            <h3>Quản Lý Chi Tiêu</h3>
            <p>Ứng dụng giúp bạn quản lý tài chính cá nhân một cách hiệu quả và thông minh.</p>
        </div>
        <div class="footer-section">
            <h3>Liên kết nhanh</h3>
            <ul>
                <li><a href="dashboard.php">Trang chủ</a></li>
                <li><a href="view/user/transactions.php">Chi tiêu</a></li>
                <li><a href="/Quanlychitieu/view/chart.php">Biểu đồ</a></li>
                <li><a href="view/user/pages/goal.php">Mục tiêu</a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h3>Hỗ trợ</h3>
            <ul>
                <li><a href="#">Trung tâm trợ giúp</a></li>
                <li><a href="#">Liên hệ</a></li>
                <li><a href="#">Điều khoản sử dụng</a></li>
                <li><a href="#">Chính sách bảo mật</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <p>© 2025 Quản Lý Chi Tiêu. Designed with ❤️</p>
    </div>
</footer>

<script>
// JavaScript tìm kiếm giữ nguyên
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('.search-input');
    const tableRows = document.querySelectorAll('.table tbody tr');
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});
</script>

</body>
</html>