<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../function/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$conn = getDbConnection();
$user_id = $_SESSION['user_id'];

// Xử lý filter
$filter = $_GET['filter'] ?? 'month';
$current_year = date('Y');
$current_month = date('m');

// Chi tiêu theo danh mục
$category_stmt = $conn->prepare("
    SELECT category, SUM(amount) as total 
    FROM transactions 
    WHERE user_id = ? 
    " . ($filter === 'month' ? "AND YEAR(date) = ? AND MONTH(date) = ?" :
    ($filter === 'year' ? "AND YEAR(date) = ?" :
        "AND date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)")) . "
    GROUP BY category 
    ORDER BY total DESC
");

if ($filter === 'month') {
    $category_stmt->bind_param("iii", $user_id, $current_year, $current_month);
} elseif ($filter === 'year') {
    $category_stmt->bind_param("ii", $user_id, $current_year);
} else {
    $category_stmt->bind_param("i", $user_id);
}
$category_stmt->execute();
$category_result = $category_stmt->get_result();
// Chi tiêu 6 tháng gần đây
$monthly_stmt = $conn->prepare("
    SELECT DATE_FORMAT(date, '%Y-%m') as month, 
           DATE_FORMAT(date, '%m/%Y') as month_label,
           SUM(amount) as total 
    FROM transactions 
    WHERE user_id = ? AND date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(date, '%Y-%m'), DATE_FORMAT(date, '%m/%Y') 
    ORDER BY month ASC
");
// THÊM: Thực thi truy vấn monthly
$monthly_stmt->bind_param("i", $user_id);
$monthly_stmt->execute();
$monthly_result = $monthly_stmt->get_result(); // Biến $monthly_result đã được gán giá trị hợp lệ

// Chi tiêu theo tuần (4 tuần gần đây)
$weekly_stmt = $conn->prepare("
    SELECT YEARWEEK(date) as week, 
           CONCAT('Tuần ', WEEK(date)) as week_label,
           SUM(amount) as total 
    FROM transactions 
    WHERE user_id = ? AND date >= DATE_SUB(NOW(), INTERVAL 4 WEEK)
    GROUP BY YEARWEEK(date), CONCAT('Tuần ', WEEK(date))
    ORDER BY week ASC
    LIMIT 4
");
// SỬA: Chỉ thực thi truy vấn weekly một lần
$weekly_stmt->bind_param("i", $user_id);
$weekly_stmt->execute();
$weekly_result = $weekly_stmt->get_result();
$weekly_stmt->bind_param("i", $user_id);
$weekly_stmt->execute();
$weekly_result = $weekly_stmt->get_result();
$weekly_stmt->bind_param("i", $user_id);
$weekly_stmt->execute();
$weekly_result = $weekly_stmt->get_result();
$weekly_stmt->bind_param("i", $user_id);
$weekly_stmt->execute();
$weekly_result = $weekly_stmt->get_result();

// Top 5 giao dịch lớn nhất
$top_transactions_stmt = $conn->prepare("
    SELECT description, category, amount, date 
    FROM transactions 
    WHERE user_id = ? 
    ORDER BY amount DESC 
    LIMIT 5
");
$top_transactions_stmt->bind_param("i", $user_id);
$top_transactions_stmt->execute();
$top_transactions_result = $top_transactions_stmt->get_result();

// Tổng chi tiêu
$total_stmt = $conn->prepare("
    SELECT SUM(amount) as total 
    FROM transactions 
    WHERE user_id = ? 
    " . ($filter === 'month' ? "AND YEAR(date) = ? AND MONTH(date) = ?" :
    ($filter === 'year' ? "AND YEAR(date) = ?" :
        "AND date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)")) . "
");

if ($filter === 'month') {
    $total_stmt->bind_param("iii", $user_id, $current_year, $current_month);
} elseif ($filter === 'year') {
    $total_stmt->bind_param("ii", $user_id, $current_year);
} else {
    $total_stmt->bind_param("i", $user_id);
}
$total_stmt->execute();
$total_row = $total_stmt->get_result()->fetch_assoc();
$total_amount = $total_row['total'] ?? 0;

// So sánh với kỳ trước
$comparison_stmt = $conn->prepare("
    SELECT SUM(amount) as total 
    FROM transactions 
    WHERE user_id = ? 
    " . ($filter === 'month' ? "AND YEAR(date) = ? AND MONTH(date) = ?" :
    ($filter === 'year' ? "AND YEAR(date) = ?" :
        "AND date BETWEEN DATE_SUB(DATE_SUB(NOW(), INTERVAL 3 MONTH), INTERVAL 3 MONTH) AND DATE_SUB(NOW(), INTERVAL 3 MONTH)")) . "
");

if ($filter === 'month') {
    $last_month = $current_month - 1;
    $last_year = $current_year;
    if ($last_month == 0) {
        $last_month = 12;
        $last_year = $current_year - 1;
    }
    $comparison_stmt->bind_param("iii", $user_id, $last_year, $last_month);
} elseif ($filter === 'year') {
    $comparison_stmt->bind_param("ii", $user_id, $current_year - 1);
} else {
    $comparison_stmt->bind_param("i", $user_id);
}
$comparison_stmt->execute();
$comparison_row = $comparison_stmt->get_result()->fetch_assoc();
$comparison_amount = $comparison_row['total'] ?? 0;

// Tính phần trăm thay đổi
$change_percentage = 0;
if ($comparison_amount > 0) {
    $change_percentage = (($total_amount - $comparison_amount) / $comparison_amount) * 100;
}

#$conn->close();

// Chuẩn bị dữ liệu cho biểu đồ
$category_labels = [];
$category_data = [];
$category_colors = [
    '#2f855a',
    '#38a169',
    '#48bb78',
    '#68d391',
    '#9ae6b4',
    '#dd6b20',
    '#ed8936',
    '#f6ad55',
    '#fbd38d',
    '#feebc8',
    '#3182ce',
    '#4299e1',
    '#63b3ed',
    '#90cdf4',
    '#bee3f8',
    '#805ad5',
    '#9f7aea',
    '#b794f4',
    '#d6bcfa',
    '#e9d8fd',
    '#e53e3e',
    '#f56565',
    '#fc8181',
    '#fed7d7'
];

while ($row = $category_result->fetch_assoc()) {
    $category_labels[] = $row['category'];
    $category_data[] = $row['total'];
}
$category_result->data_seek(0);

$monthly_labels = [];
$monthly_data = [];
while ($row = $monthly_result->fetch_assoc()) {
    $monthly_labels[] = $row['month_label'];
    $monthly_data[] = $row['total'];
}

$weekly_labels = [];
$weekly_data = [];
while ($row = $weekly_result->fetch_assoc()) {
    $weekly_labels[] = $row['week_label'];
    $weekly_data[] = $row['total'];
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biểu đồ chi tiêu - Quản Lý Chi Tiêu</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/chart.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

    <header>
        <div class="logo">💰 Quản Lý Chi Tiêu</div>
        <nav>
            <a href="/Quanlychitieu/dashboard.php">Trang chủ</a>

            <a href="/Quanlychitieu/view/user/transactions.php">Các khoản chi tiêu</a>

            <a href="/Quanlychitieu/view/chart.php">Xem biểu đồ</a>

            <a href="/Quanlychitieu/view/user/pages/goal.php" class="active">Mục tiêu tiết kiệm</a>

            <a href="/Quanlychitieu/view/auth/logout.php">Đăng xuất</a>
        </nav>
    </header>

    <section class="charts">
        <div class="container">
            <div class="page-header">
                <h1 class="page-title"><i class="fas fa-chart-pie"></i> Phân tích chi tiêu</h1>
                <div class="chart-controls">
                    <select id="timeFilter" class="filter-select" onchange="updateFilter(this.value)">
                        <option value="month" <?php echo $filter === 'month' ? 'selected' : ''; ?>>Tháng này</option>
                        <option value="last3months" <?php echo $filter === 'last3months' ? 'selected' : ''; ?>>3 tháng gần
                            đây</option>
                        <option value="year" <?php echo $filter === 'year' ? 'selected' : ''; ?>>Năm nay</option>
                    </select>
                </div>
            </div>

            <!-- Thống kê tổng quan -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Tổng chi tiêu</h3>
                        <p class="stat-value"><?php echo number_format($total_amount, 0, ',', '.'); ?>₫</p>
                        <p class="stat-change <?php echo $change_percentage >= 0 ? 'increase' : 'decrease'; ?>">
                            <?php echo ($change_percentage >= 0 ? '+' : '') . number_format($change_percentage, 1); ?>%
                            so với kỳ trước
                        </p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Số danh mục</h3>
                        <p class="stat-value"><?php echo $category_result->num_rows; ?></p>
                        <p class="stat-desc">Danh mục chi tiêu</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Trung bình/ngày</h3>
                        <p class="stat-value">
                            <?php
                            $days = $filter === 'month' ? date('t') : ($filter === 'year' ? 365 : 90);
                            $daily_avg = $total_amount / $days;
                            echo number_format($daily_avg, 0, ',', '.'); ?>₫
                        </p>
                        <p class="stat-desc">Chi tiêu trung bình</p>
                    </div>
                </div>
            </div>

            <!-- Biểu đồ chính -->
            <div class="charts-grid">
                <!-- Biểu đồ tròn - Chi tiêu theo danh mục -->
                <div class="chart-card full-width">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-pie"></i> Phân bổ chi tiêu theo danh mục</h3>
                        <span class="chart-period">
                            <?php
                            echo $filter === 'month' ? 'Tháng ' . date('m/Y') :
                                ($filter === 'year' ? 'Năm ' . date('Y') : '3 tháng gần đây');
                            ?>
                        </span>
                    </div>
                    <div class="chart-container">
                        <canvas id="categoryChart"></canvas>
                    </div>
                    <?php if ($category_result->num_rows == 0): ?>
                        <div class="chart-empty">
                            <i class="fas fa-chart-pie"></i>
                            <p>Không có dữ liệu chi tiêu trong khoảng thời gian này</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Biểu đồ đường - Chi tiêu theo tháng -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-line"></i> Xu hướng chi tiêu</h3>
                        <span class="chart-period">6 tháng gần đây</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                    <?php if ($monthly_result->num_rows == 0): ?>
                        <div class="chart-empty">
                            <i class="fas fa-chart-line"></i>
                            <p>Không có dữ liệu</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Biểu đồ cột - Chi tiêu theo tuần -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-bar"></i> Chi tiêu theo tuần</h3>
                        <span class="chart-period">4 tuần gần đây</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="weeklyChart"></canvas>
                    </div>
                    <?php if ($weekly_result->num_rows == 0): ?>
                        <div class="chart-empty">
                            <i class="fas fa-chart-bar"></i>
                            <p>Không có dữ liệu</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chi tiết danh mục -->
            <div class="details-section">
                <div class="section-header">
                    <h2><i class="fas fa-list"></i> Chi tiết theo danh mục</h2>
                </div>
                <div class="categories-list">
                    <?php if ($category_result->num_rows > 0): ?>
                        <?php
                        $color_index = 0;
                        while ($category = $category_result->fetch_assoc()):
                            $percentage = $total_amount > 0 ? ($category['total'] / $total_amount) * 100 : 0;
                            ?>
                            <div class="category-item">
                                <div class="category-color"
                                    style="background-color: <?php echo $category_colors[$color_index % count($category_colors)]; ?>">
                                </div>
                                <div class="category-info">
                                    <span class="category-name"><?php echo htmlspecialchars($category['category']); ?></span>
                                    <span class="category-percentage"><?php echo number_format($percentage, 1); ?>%</span>
                                </div>
                                <div class="category-amount">
                                    <?php echo number_format($category['total'], 0, ',', '.'); ?>₫
                                </div>
                            </div>
                            <?php $color_index++; endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-folder-open"></i>
                            <p>Không có dữ liệu danh mục</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
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
                    <li><a href="../dashboard.php">Trang chủ</a></li>
                    <li><a href="user/transactions.php">Chi tiêu</a></li>
                    <li><a href="chart.php">Biểu đồ</a></li>
                    <li><a href="goals/index.php">Mục tiêu</a></li>
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
        // Hàm cập nhật filter
        function updateFilter(filter) {
            window.location.href = `chart.php?filter=${filter}`;
        }

        // Biểu đồ tròn - Chi tiêu theo danh mục
        <?php if ($category_result->num_rows > 0): ?>
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');
            const categoryChart = new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($category_labels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($category_data); ?>,
                        backgroundColor: <?php echo json_encode(array_slice($category_colors, 0, count($category_labels))); ?>,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value.toLocaleString()}₫ (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        <?php endif; ?>

        // Biểu đồ đường - Chi tiêu theo tháng
        <?php if ($monthly_result->num_rows > 0): ?>
            const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
            const monthlyChart = new Chart(monthlyCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($monthly_labels); ?>,
                    datasets: [{
                        label: 'Chi tiêu',
                        data: <?php echo json_encode($monthly_data); ?>,
                        borderColor: '#3182ce',
                        backgroundColor: 'rgba(49, 130, 206, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    return value.toLocaleString() + '₫';
                                }
                            }
                        }
                    }
                }
            });
        <?php endif; ?>

        // Biểu đồ cột - Chi tiêu theo tuần
        <?php if ($weekly_result->num_rows > 0): ?>
            const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
            const weeklyChart = new Chart(weeklyCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($weekly_labels); ?>,
                    datasets: [{
                        label: 'Chi tiêu',
                        data: <?php echo json_encode($weekly_data); ?>,
                        backgroundColor: '#2f855a',
                        borderColor: '#2f855a',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    return value.toLocaleString() + '₫';
                                }
                            }
                        }
                    }
                }
            });
        <?php endif; ?>
    </script>

</body>

</html>
