<?php
session_start();

// Dùng đường dẫn tuyệt đối
include $_SERVER['DOCUMENT_ROOT'] . '/Quanlychitieu/function/db_connection.php';

// Nếu chưa đăng nhập → quay lại login
if(!isset($_SESSION['user_id'])){
    header("Location: /Quanlychitieu/view/auth/login.php");
    exit();
}

$conn = getDbConnection();
$user_id = $_SESSION['user_id'];

// Xử lý tìm kiếm
$search_keyword = $_GET['search'] ?? '';
$search_results = [];
$show_search_results = false;

if (!empty($search_keyword)) {
    $search_stmt = $conn->prepare("
        SELECT * FROM transactions 
        WHERE user_id = ? AND (
            description LIKE ? OR 
            category LIKE ? OR 
            CAST(amount AS CHAR) LIKE ?
        )
        ORDER BY date DESC 
        LIMIT 10
    ");
    $search_term = "%$search_keyword%";
    $search_stmt->bind_param("iss", $user_id, $search_term, $search_term, $search_term);
    $search_stmt->execute();
    $search_results = $search_stmt->get_result();
    $show_search_results = true;
}

// Lấy tổng chi tiêu tháng này
$current_month = date('Y-m');
$stmt = $conn->prepare("SELECT SUM(amount) as total FROM transactions WHERE user_id=? AND DATE_FORMAT(date, '%Y-%m') = ?");
$stmt->bind_param("is", $user_id, $current_month);
$stmt->execute();
$result = $stmt->get_result();
$month_total = $result->fetch_assoc();
$current_month_spending = $month_total['total'] ?? 0;

// Lấy tổng chi tiêu tháng trước
$last_month = date('Y-m', strtotime('-1 month'));
$stmt = $conn->prepare("SELECT SUM(amount) as total FROM transactions WHERE user_id=? AND DATE_FORMAT(date, '%Y-%m') = ?");
$stmt->bind_param("is", $user_id, $last_month);
$stmt->execute();
$result = $stmt->get_result();
$last_month_total = $result->fetch_assoc();
$last_month_spending = $last_month_total['total'] ?? 0;

// Tính phần trăm thay đổi
$spending_change = 0;
if ($last_month_spending > 0) {
    $spending_change = (($current_month_spending - $last_month_spending) / $last_month_spending) * 100;
}

// Lấy tổng số giao dịch
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$count_row = $result->fetch_assoc();
$transaction_count = $count_row['count'] ?? 0;

// Lấy chi tiêu gần đây (5 giao dịch mới nhất)
$stmt = $conn->prepare("SELECT * FROM transactions WHERE user_id=? ORDER BY date DESC, id DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_result = $stmt->get_result();

// Lấy mục tiêu tiết kiệm
$stmt = $conn->prepare("SELECT * FROM savings_goals WHERE user_id=? AND completed = 0 ORDER BY target_date ASC LIMIT 3");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$goals_result = $stmt->get_result();

// Đếm số mục tiêu đã hoàn thành
$stmt = $conn->prepare("SELECT COUNT(*) as completed_count FROM savings_goals WHERE user_id=? AND completed = 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$completed_result = $stmt->get_result();
$completed_row = $completed_result->fetch_assoc();
$completed_goals = $completed_row['completed_count'] ?? 0;

// Đếm tổng số mục tiêu
$stmt = $conn->prepare("SELECT COUNT(*) as total_count FROM savings_goals WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_goals_result = $stmt->get_result();
$total_goals_row = $total_goals_result->fetch_assoc();
$total_goals = $total_goals_row['total_count'] ?? 0;

// Tính phần trăm mục tiêu hoàn thành
$goals_percentage = 0;
if ($total_goals > 0) {
    $goals_percentage = round(($completed_goals / $total_goals) * 100);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - Quản Lý Chi Tiêu</title>
<link rel="stylesheet" href="css/dashboard.css"> 
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>

<body>

<header>
    <div class="logo">💰 Quản Lý Chi Tiêu</div>
    <nav>
        <a href="dashboard.php" class="active">Trang Chủ</a>
        <a href="view/user/transactions.php">Các khoản chi tiêu</a>
        <a href="/Quanlychitieu/view/chart.php">Xem biểu đồ</a>
        <a href="view/user/pages/goal.php">Mục tiêu tiết kiệm</a>
        <a href="view/user/profile.php" class="avatar-link">
        <img src="https://cdn-icons-png.flaticon.com/512/149/149071.png" alt="User Avatar" class="avatar-img"></a>
        <a href="view/auth/logout.php" class="logout-button">Đăng xuất</a>
    </nav>
</header>

<section class="dashboard">
    <div class="container">
        <!-- Header với tìm kiếm -->
        <div class="dashboard-header">
            <div class="welcome-section">
                <h1>Xin chào, <?php echo htmlspecialchars($_SESSION['username']); ?> 👋</h1>
                <p class="sub-text">Chúc bạn một ngày tiết kiệm hiệu quả!</p>
            </div>
            
            <!-- Thanh tìm kiếm -->
            <div class="search-section">
                <form method="GET" class="search-form">
                    <div class="search-box">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search_keyword); ?>" 
                               placeholder="Tìm kiếm giao dịch, danh mục, số tiền..." class="search-input">
                        <button type="submit" class="search-button">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if (!empty($search_keyword)): ?>
                            <a href="dashboard.php" class="clear-search" title="Xóa tìm kiếm">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Hiển thị kết quả tìm kiếm -->
        <?php if ($show_search_results): ?>
        <div class="search-results-section">
            <div class="section-header">
                <h2>
                    <i class="fas fa-search"></i>
                    Kết quả tìm kiếm cho "<?php echo htmlspecialchars($search_keyword); ?>"
                    <span class="results-count">(<?php echo $search_results->num_rows; ?> kết quả)</span>
                </h2>
                <a href="dashboard.php" class="view-all">Quay lại dashboard</a>
            </div>
            
            <div class="search-results">
                <?php if($search_results->num_rows > 0): ?>
                    <div class="results-grid">
                        <?php while($row = $search_results->fetch_assoc()): 
                            $category_class = strtolower($row['category'] ?? 'other');
                            if (!in_array($category_class, ['food', 'transport', 'shopping', 'bills', 'entertainment'])) {
                                $category_class = 'other';
                            }
                        ?>
                        <div class="result-item">
                            <div class="result-icon <?php echo $category_class; ?>">
                                <i class="fa-solid 
                                    <?php 
                                    switch($category_class) {
                                        case 'food': echo 'fa-utensils'; break;
                                        case 'transport': echo 'fa-gas-pump'; break;
                                        case 'shopping': echo 'fa-shopping-bag'; break;
                                        case 'bills': echo 'fa-file-invoice'; break;
                                        case 'entertainment': echo 'fa-film'; break;
                                        default: echo 'fa-receipt';
                                    }
                                    ?>
                                "></i>
                            </div>
                            <div class="result-details">
                                <h4><?php echo htmlspecialchars($row['category']); ?></h4>
                                <p class="result-description"><?php echo htmlspecialchars($row['description']); ?></p>
                                <div class="result-meta">
                                    <span class="result-date"><?php echo date('d/m/Y', strtotime($row['date'])); ?></span>
                                    <span class="result-amount"><?php echo number_format($row['amount'], 0, ',', '.'); ?> đ</span>
                                </div>
                            </div>
                            <div class="result-actions">
                                <a href="view/user/transactions_edit.php?id=<?php echo $row['id']; ?>" class="btn-edit" title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-search">
                        <i class="fas fa-search"></i>
                        <h3>Không tìm thấy kết quả</h3>
                        <p>Không có giao dịch nào phù hợp với từ khóa "<?php echo htmlspecialchars($search_keyword); ?>"</p>
                        <div class="search-suggestions">
                            <p>Thử:</p>
                            <ul>
                                <li>Kiểm tra lại từ khóa tìm kiếm</li>
                                <li>Tìm kiếm với từ khóa khác</li>
                                <li>Kiểm tra chính tả</li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Nội dung dashboard bình thường (chỉ hiển thị khi không có tìm kiếm) -->
        <?php if (!$show_search_results): ?>
        <!-- Thống kê nhanh -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-money-bill-wave"></i>
                </div>
                <div class="stat-info">
                    <h3>Chi tiêu tháng này</h3>
                    <p class="stat-value"><?php echo number_format($current_month_spending, 0, ',', '.'); ?> đ</p>
                    <p class="stat-change <?php echo $spending_change >= 0 ? 'increase' : 'decrease'; ?>">
                        <?php 
                        if ($spending_change > 0) echo '+';
                        echo number_format($spending_change, 1); ?>% so với tháng trước
                    </p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <div class="stat-info">
                    <h3>Tổng giao dịch</h3>
                    <p class="stat-value"><?php echo $transaction_count; ?></p>
                    <p class="stat-change">Tất cả giao dịch</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-bullseye"></i>
                </div>
                <div class="stat-info">
                    <h3>Mục tiêu hoàn thành</h3>
                    <p class="stat-value"><?php echo $completed_goals; ?>/<?php echo $total_goals; ?></p>
                    <p class="stat-change"><?php echo $goals_percentage; ?>% hoàn thành</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <div class="stat-info">
                    <h3>Xu hướng chi tiêu</h3>
                    <p class="stat-value">
                        <?php 
                        if ($spending_change < -10) echo "Giảm mạnh";
                        elseif ($spending_change < 0) echo "Giảm nhẹ";
                        elseif ($spending_change < 10) echo "Ổn định";
                        else echo "Tăng";
                        ?>
                    </p>
                    <p class="stat-change <?php echo $spending_change >= 0 ? 'increase' : 'decrease'; ?>">
                        <?php 
                        if ($spending_change > 0) echo '+';
                        echo number_format($spending_change, 1); ?>% so với tháng trước
                    </p>
                </div>
            </div>
        </div>

        <div class="dashboard-cards">
            <a class="card" href="view/user/transactions.php">
                <div class="card-icon">
                    <i class="fa-solid fa-wallet"></i>
                </div>
                <h3>Các khoản chi tiêu</h3>
                <p>Thêm các khoản chi hằng ngày.</p>
                <div class="card-footer">
                    <span>Quản lý chi tiêu</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </div>
            </a>

            <a class="card" href="/Quanlychitieu/view/chart.php">
                <div class="card-icon">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <h3>Xem biểu đồ</h3>
                <p>Phân tích chi tiêu của bạn.</p>
                <div class="card-footer">
                    <span>Phân tích dữ liệu</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </div>
            </a>

            <a class="card" href="view/user/pages/goal.php">
                <div class="card-icon">
                    <i class="fa-solid fa-bullseye"></i>
                </div>
                <h3>Mục tiêu tiết kiệm</h3>
                <p>Đặt và theo dõi tiến độ.</p>
                <div class="card-footer">
                    <span>Quản lý mục tiêu</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </div>
            </a>
            
            <a class="card" href="#">
                <div class="card-icon">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                </div>
                <h3>Báo cáo tài chính</h3>
                <p>Xem báo cáo chi tiết hàng tháng.</p>
                <div class="card-footer">
                    <span>Báo cáo</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </div>
            </a>
            
            <a class="card" href="#">
                <div class="card-icon">
                    <i class="fa-solid fa-bell"></i>
                </div>
                <h3>Nhắc nhở thanh toán</h3>
                <p>Thiết lập nhắc nhở cho các hóa đơn.</p>
                <div class="card-footer">
                    <span>Nhắc nhở</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </div>
            </a>
            
            <a class="card" href="#">
                <div class="card-icon">
                    <i class="fa-solid fa-lightbulb"></i>
                </div>
                <h3>Lời khuyên tài chính</h3>
                <p>Nhận gợi ý để tiết kiệm hiệu quả hơn.</p>
                <div class="card-footer">
                    <span>Gợi ý</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </div>
            </a>
        </div>
        
        <!-- Chi tiêu gần đây -->
        <div class="recent-section">
            <div class="section-header">
                <h2>Chi tiêu gần đây</h2>
                <a href="view/user/transactions.php" class="view-all">Xem tất cả</a>
            </div>
            <div class="recent-transactions">
                <?php if($recent_result->num_rows > 0): ?>
                    <?php while($row = $recent_result->fetch_assoc()): 
                        $category_class = strtolower($row['category'] ?? 'other');
                        if (!in_array($category_class, ['food', 'transport', 'shopping', 'bills', 'entertainment'])) {
                            $category_class = 'other';
                        }
                    ?>
                    <div class="transaction-item">
                        <div class="transaction-icon <?php echo $category_class; ?>">
                            <i class="fa-solid 
                                <?php 
                                switch($category_class) {
                                    case 'food': echo 'fa-utensils'; break;
                                    case 'transport': echo 'fa-gas-pump'; break;
                                    case 'shopping': echo 'fa-shopping-bag'; break;
                                    case 'bills': echo 'fa-file-invoice'; break;
                                    case 'entertainment': echo 'fa-film'; break;
                                    default: echo 'fa-receipt';
                                }
                                ?>
                            "></i>
                        </div>
                        <div class="transaction-details">
                            <h4><?php echo htmlspecialchars($row['category']); ?></h4>
                            <p><?php echo htmlspecialchars($row['description']); ?></p>
                            <small><?php echo date('d/m/Y', strtotime($row['date'])); ?></small>
                        </div>
                        <div class="transaction-amount negative">
                            - <?php echo number_format($row['amount'], 0, ',', '.'); ?> đ
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-receipt"></i>
                        <p>Chưa có giao dịch nào</p>
                        <a href="view/user/transactions.php" class="button">Thêm giao dịch đầu tiên</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Mục tiêu sắp hoàn thành -->
        <div class="goals-section">
            <div class="section-header">
                <h2>Mục tiêu sắp hoàn thành</h2>
                <a href="view/user/pages/goal.php" class="view-all">Xem tất cả</a>
            </div>
            <div class="goals-list">
                <?php if($goals_result->num_rows > 0): ?>
                    <?php while($row = $goals_result->fetch_assoc()): 
                        $progress = 0;
                        if ($row['target_amount'] > 0) {
                            $progress = min(100, round(($row['current_amount'] / $row['target_amount']) * 100));
                        }
                    ?>
                    <div class="goal-item">
                        <div class="goal-info">
                            <h4><?php echo htmlspecialchars($row['goal_name']); ?></h4>
                            <p><?php echo number_format($row['current_amount'], 0, ',', '.'); ?> đ / <?php echo number_format($row['target_amount'], 0, ',', '.'); ?> đ</p>
                        </div>
                        <div class="goal-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                            <span><?php echo $progress; ?>%</span>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-bullseye"></i>
                        <p>Chưa có mục tiêu nào</p>
                        <a href="view/user/pages/goal.php" class="button">Tạo mục tiêu đầu tiên</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

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

</body>
</html>