<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/Quanlychitieu/function/db_connection.php';

if(!isset($_SESSION['user_id'])){
    header("Location: /Quanlychitieu/view/auth/login.php");
    exit();
}

$conn = getDbConnection();
$user_id = $_SESSION['user_id'];

// --- MẢNG DỊCH DANH MỤC (Dùng chung) ---
$cat_map = [
    'Food' => '🍔 Ăn uống',
    'Transport' => '🛵 Di chuyển',
    'Shopping' => '🛍️ Mua sắm',
    'Bills' => '🧾 Hóa đơn',
    'Entertainment' => '🎬 Giải trí',
    'Other' => '📦 Khác',
    'Salary' => '💰 Lương',
    'Bonus' => '🎁 Thưởng',
    'Investment' => '📈 Đầu tư'
];

// --- HÀM HỖ TRỢ ---
function highlightKeyword($text, $keyword) {
    if (empty($keyword)) return htmlspecialchars($text);
    $escaped_keyword = preg_quote($keyword, '/');
    $safe_text = htmlspecialchars($text);
    return preg_replace("/($escaped_keyword)/i", '<mark class="highlight-text">$1</mark>', $safe_text);
}

// --- XỬ LÝ TÌM KIẾM ---
$search_keyword = $_GET['search'] ?? '';
$search_results = [];
$show_search_results = false;
$total_pages = 0;
$total_records = 0;
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;

if (!empty($search_keyword)) {
    $search_term = "%$search_keyword%";
    
    $count_sql = "SELECT COUNT(*) as total FROM transactions WHERE user_id = ? AND (description LIKE ? OR category LIKE ? OR CAST(amount AS CHAR) LIKE ?)";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("isss", $user_id, $search_term, $search_term, $search_term);
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_records / $limit);

    $offset = ($current_page - 1) * $limit;
    $search_sql = "SELECT * FROM transactions WHERE user_id = ? AND (description LIKE ? OR category LIKE ? OR CAST(amount AS CHAR) LIKE ?) ORDER BY date DESC LIMIT ? OFFSET ?";
    $search_stmt = $conn->prepare($search_sql);
    $search_stmt->bind_param("isssii", $user_id, $search_term, $search_term, $search_term, $limit, $offset);
    $search_stmt->execute();
    $search_results = $search_stmt->get_result();
    $show_search_results = true;
}

// --- LOGIC THỐNG KÊ ---
$current_month_spending = 0;
$spending_change = 0;
$transaction_count = 0;
$completed_goals = 0;
$total_goals = 0;
$goals_percentage = 0;
$recent_result = null;
$goals_result = null;

if (!$show_search_results) {
    // Chi tiêu tháng này (Chỉ tính type = 'expense' nếu đã có cột type, nếu chưa thì tạm tính hết)
    // Để an toàn, ta tính tổng amount âm (nếu bạn lưu số âm) hoặc lọc theo type nếu đã update DB
    // Giả sử bạn đã chạy lệnh SQL update cột 'type', ta lọc: WHERE type='expense'
    $current_month = date('Y-m');
    
    // Kiểm tra xem bảng có cột type chưa để tránh lỗi
    $check_col = $conn->query("SHOW COLUMNS FROM transactions LIKE 'type'");
    $has_type = $check_col->num_rows > 0;
    
    $sql_month = "SELECT SUM(amount) as total FROM transactions WHERE user_id=? AND DATE_FORMAT(date, '%Y-%m') = ?";
    if($has_type) $sql_month .= " AND type = 'expense'"; // Chỉ tính chi tiêu
    
    $stmt = $conn->prepare($sql_month);
    $stmt->bind_param("is", $user_id, $current_month);
    $stmt->execute();
    $current_month_spending = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    // Tháng trước
    $last_month = date('Y-m', strtotime('-1 month'));
    $stmt = $conn->prepare($sql_month); // Tái sử dụng query
    $stmt->bind_param("is", $user_id, $last_month);
    $stmt->execute();
    $last_month_spending = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    if ($last_month_spending > 0) {
        $spending_change = (($current_month_spending - $last_month_spending) / $last_month_spending) * 100;
    }

    // Tổng giao dịch
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $transaction_count = $stmt->get_result()->fetch_assoc()['count'] ?? 0;

    // Giao dịch gần đây
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE user_id=? ORDER BY date DESC, id DESC LIMIT 5");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $recent_result = $stmt->get_result();

    // Mục tiêu
    $stmt = $conn->prepare("SELECT * FROM savings_goals WHERE user_id=? AND completed = 0 ORDER BY target_date ASC LIMIT 3");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $goals_result = $stmt->get_result();

    // Thống kê mục tiêu
    $stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed FROM savings_goals WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $g_stats = $stmt->get_result()->fetch_assoc();
    $total_goals = $g_stats['total'];
    $completed_goals = $g_stats['completed'];
    if ($total_goals > 0) $goals_percentage = round(($completed_goals / $total_goals) * 100);
}
$conn->close();

$page_title = 'Dashboard - Quản Lý Chi Tiêu';
$active_page = 'dashboard';

include $_SERVER['DOCUMENT_ROOT'] . '/Quanlychitieu/includes/header.php';
?>

<section class="dashboard">
    <div class="container">
        
        <div class="dashboard-header">
            <div class="welcome-section">
                <h1>Xin chào, <?php echo htmlspecialchars($_SESSION['username']); ?> 👋</h1>
                <p class="sub-text">Chúc bạn một ngày quản lý tài chính hiệu quả!</p>
            </div>

            <?php if ($show_search_results): ?>
            <div class="search-results-section">
                <div class="section-header">
                    <h2>
                        <i class="fas fa-search"></i> Kết quả tìm kiếm cho "<?php echo htmlspecialchars($search_keyword); ?>"
                        <span class="results-count">(Tổng: <?php echo $total_records; ?> kết quả)</span>
                    </h2>
                    <a href="dashboard.php" class="view-all">Quay lại dashboard</a>
                </div>
                
                <div class="search-results">
                    <?php if($search_results->num_rows > 0): ?>
                        <div class="results-grid">
                            <?php while($row = $search_results->fetch_assoc()): 
                                $cat_raw = $row['category'];
                                $display_name = $cat_map[$cat_raw] ?? $cat_raw; // DỊCH TÊN Ở ĐÂY
                                
                                $cat_class = strtolower($cat_raw ?? 'other');
                                if (!in_array($cat_class, ['food', 'transport', 'shopping', 'bills', 'entertainment'])) $cat_class = 'other';
                            ?>
                            <div class="result-item">
                                <div class="result-icon <?php echo $cat_class; ?>">
                                    <i class="fa-solid fa-receipt"></i>
                                </div>
                                <div class="result-details">
                                    <h4><?php echo highlightKeyword($display_name, $search_keyword); ?></h4>
                                    <p><?php echo highlightKeyword($row['description'], $search_keyword); ?></p>
                                    <div class="result-meta">
                                        <span class="result-date"><?php echo date('d/m/Y', strtotime($row['date'])); ?></span>
                                        <span class="result-amount"><?php echo number_format($row['amount'], 0, ',', '.'); ?> đ</span>
                                    </div>
                                </div>
                                <div class="result-actions">
                                    <a href="view/user/transactions_edit.php?id=<?php echo $row['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <?php else: ?>
                        <div class="empty-search">
                            <i class="fas fa-search"></i>
                            <h3>Không tìm thấy kết quả</h3>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!$show_search_results): ?>
        
        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
                <div class="stat-info">
                    <h3>Chi tiêu tháng này</h3>
                    <p class="stat-value"><?php echo number_format($current_month_spending, 0, ',', '.'); ?> đ</p>
                    <p class="stat-change <?php echo $spending_change >= 0 ? 'increase' : 'decrease'; ?>">
                        <?php echo ($spending_change > 0 ? '+' : '') . number_format($spending_change, 1); ?>% so với tháng trước
                    </p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-receipt"></i></div>
                <div class="stat-info">
                    <h3>Tổng giao dịch</h3>
                    <p class="stat-value"><?php echo $transaction_count; ?></p>
                    <p class="stat-change">Tất cả giao dịch</p>
                </div>
            </div>
             <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-bullseye"></i></div>
                <div class="stat-info">
                    <h3>Mục tiêu hoàn thành</h3>
                    <p class="stat-value"><?php echo $completed_goals; ?>/<?php echo $total_goals; ?></p>
                    <p class="stat-change"><?php echo $goals_percentage; ?>% hoàn thành</p>
                </div>
            </div>
             <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-chart-line"></i></div>
                <div class="stat-info">
                    <h3>Xu hướng</h3>
                    <p class="stat-value"><?php echo ($spending_change < 0) ? "Giảm" : "Tăng"; ?></p>
                    <p class="stat-change">Dựa trên chi tiêu</p>
                </div>
            </div>
        </div>

        <div class="dashboard-cards">
            <a class="card" href="view/user/transactions.php">
                <div class="card-icon"><i class="fa-solid fa-wallet"></i></div>
                <h3>Quản lý thu chi</h3>
                <p>Ghi chép thu nhập và chi tiêu hằng ngày.</p>
                <div class="card-footer"><span>Xem chi tiết</span><i class="fa-solid fa-arrow-right"></i></div>
            </a>
            <a class="card" href="/Quanlychitieu/view/chart.php">
                <div class="card-icon"><i class="fa-solid fa-chart-pie"></i></div>
                <h3>Xem biểu đồ</h3>
                <p>Phân tích dòng tiền trực quan.</p>
                <div class="card-footer"><span>Phân tích</span><i class="fa-solid fa-arrow-right"></i></div>
            </a>
            <a class="card" href="view/user/pages/goal.php">
                <div class="card-icon"><i class="fa-solid fa-bullseye"></i></div>
                <h3>Mục tiêu tiết kiệm</h3>
                <p>Đặt mục tiêu và theo dõi tiến độ.</p>
                <div class="card-footer"><span>Quản lý</span><i class="fa-solid fa-arrow-right"></i></div>
            </a>
            <a class="card" href="view/user/pages/investments.php">
                <div class="card-icon"><i class="fa-solid fa-chart-line"></i></div>
                <h3>Đầu tư & Tích sản</h3>
                <p>Theo dõi danh mục đầu tư sinh lời.</p>
                <div class="card-footer"><span>Đầu tư ngay</span><i class="fa-solid fa-arrow-right"></i></div>
            </a>
        </div>

        <div class="recent-section">
            <div class="section-header">
                <h2>Giao dịch gần đây</h2>
                <a href="view/user/transactions.php" class="view-all">Xem tất cả</a>
            </div>
            <div class="recent-transactions">
                <?php if($recent_result && $recent_result->num_rows > 0): ?>
                    <?php while($row = $recent_result->fetch_assoc()): 
                         $cat_raw = $row['category'];
                         $cat_slug = strtolower($cat_raw ?? 'other');
                         if (!in_array($cat_slug, ['food', 'transport', 'shopping', 'bills', 'entertainment', 'salary', 'bonus', 'investment'])) $cat_slug = 'other';
                         
                         // DỊCH TÊN Ở ĐÂY
                         $display_name = $cat_map[$cat_raw] ?? $cat_raw;
                         
                         // Xử lý màu sắc Thu/Chi
                         $type = $row['type'] ?? 'expense';
                         $is_income = ($type === 'income');
                         $amount_class = $is_income ? 'text-success' : 'text-danger';
                         $sign = $is_income ? '+' : '-';
                    ?>
                    <div class="transaction-item">
                        <div class="transaction-icon <?php echo $cat_slug; ?>">
                            <i class="fa-solid <?php 
                                switch($cat_slug) {
                                    case 'food': echo 'fa-utensils'; break;
                                    case 'transport': echo 'fa-gas-pump'; break;
                                    case 'shopping': echo 'fa-shopping-bag'; break;
                                    case 'bills': echo 'fa-file-invoice'; break;
                                    case 'entertainment': echo 'fa-film'; break;
                                    case 'salary': echo 'fa-money-bill'; break;
                                    case 'bonus': echo 'fa-gift'; break;
                                    case 'investment': echo 'fa-chart-line'; break;
                                    default: echo 'fa-receipt';
                                }
                            ?>"></i>
                        </div>
                        <div class="transaction-details">
                            <h4><?php echo htmlspecialchars($display_name); ?></h4>
                            <p><?php echo htmlspecialchars($row['description']); ?></p>
                            <small><?php echo date('d/m/Y', strtotime($row['date'])); ?></small>
                        </div>
                        <div class="transaction-amount" style="color: <?php echo $is_income ? '#38a169' : '#e53e3e'; ?>; font-weight: bold;">
                            <?php echo $sign; ?> <?php echo number_format($row['amount'], 0, ',', '.'); ?> đ
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-receipt"></i>
                        <p>Chưa có giao dịch nào</p>
                        <a href="view/user/transactions_add.php" class="button">Thêm giao dịch đầu tiên</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="goals-section">
            <div class="section-header">
                <h2>Mục tiêu sắp hoàn thành</h2>
                <a href="view/user/pages/goal.php" class="view-all">Xem tất cả</a>
            </div>
            <div class="goals-list">
                <?php if($goals_result && $goals_result->num_rows > 0): ?>
                    <?php while($row = $goals_result->fetch_assoc()): 
                        $progress = 0;
                        if ($row['target_amount'] > 0) $progress = min(100, round(($row['current_amount'] / $row['target_amount']) * 100));
                    ?>
                    <div class="goal-item">
                        <div class="goal-info">
                            <h4><?php echo htmlspecialchars($row['goal_name']); ?></h4>
                            <p><?php echo number_format($row['current_amount'], 0, ',', '.'); ?> / <?php echo number_format($row['target_amount'], 0, ',', '.'); ?> đ</p>
                        </div>
                        <div class="goal-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                            <span><?php echo $progress; ?>%</span>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php endif; ?>

    </div> 
</section>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/Quanlychitieu/includes/footer.php'; ?>