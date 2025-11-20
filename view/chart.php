<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/Quanlychitieu/function/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /Quanlychitieu/view/auth/login.php");
    exit();
}

$conn = getDbConnection();
$user_id = $_SESSION['user_id'];

// --- 1. MẢNG DỊCH TÊN & MÀU SẮC ---
$cat_map = [
    'Food' => 'Ăn uống',
    'Transport' => 'Di chuyển',
    'Shopping' => 'Mua sắm',
    'Bills' => 'Hóa đơn',
    'Entertainment' => 'Giải trí',
    'Other' => 'Khác'
];

$chart_colors = ['#e53e3e', '#dd6b20', '#38a169', '#3182ce', '#805ad5', '#718096', '#d69e2e', '#319795', '#d53f8c', '#667eea'];

// --- 2. XỬ LÝ FILTER ---
$filter = $_GET['filter'] ?? 'month'; 
$current_year = date('Y');
$current_month = date('m');

$time_sql = "";
$params = [];
$types = "";

if ($filter === 'month') {
    $time_sql = "AND YEAR(date) = ? AND MONTH(date) = ?";
    $params = [$current_year, $current_month];
    $types = "ii";
} elseif ($filter === 'year') {
    $time_sql = "AND YEAR(date) = ?";
    $params = [$current_year];
    $types = "i";
} else { 
    $time_sql = "AND date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
}

// --- 3. TRUY VẤN DỮ LIỆU ---

// A. Chi tiêu theo danh mục
$cat_sql = "SELECT category, SUM(amount) as total FROM transactions WHERE user_id = ? $time_sql GROUP BY category ORDER BY total DESC";
$cat_stmt = $conn->prepare($cat_sql);
if(!empty($types)) {
    $cat_stmt->bind_param("i" . $types, $user_id, ...$params);
} else {
    $cat_stmt->bind_param("i", $user_id);
}
$cat_stmt->execute();
$category_result = $cat_stmt->get_result();

// B. Xu hướng 6 tháng
$monthly_stmt = $conn->prepare("
    SELECT DATE_FORMAT(date, '%Y-%m') as month, DATE_FORMAT(date, '%m/%Y') as month_label, SUM(amount) as total 
    FROM transactions 
    WHERE user_id = ? AND date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month, month_label ORDER BY month ASC
");
$monthly_stmt->bind_param("i", $user_id);
$monthly_stmt->execute();
$monthly_result = $monthly_stmt->get_result();

// C. Chi tiêu 4 tuần
$weekly_stmt = $conn->prepare("
    SELECT YEARWEEK(date, 1) as week, CONCAT('Tuần ', WEEK(date, 1)) as week_label, SUM(amount) as total 
    FROM transactions 
    WHERE user_id = ? AND date >= DATE_SUB(NOW(), INTERVAL 4 WEEK)
    GROUP BY week, week_label ORDER BY week ASC
");
$weekly_stmt->bind_param("i", $user_id);
$weekly_stmt->execute();
$weekly_result = $weekly_stmt->get_result();

// D. Tổng chi tiêu
$total_sql = "SELECT SUM(amount) as total FROM transactions WHERE user_id = ? $time_sql";
$total_stmt = $conn->prepare($total_sql);
if(!empty($types)) {
    $total_stmt->bind_param("i" . $types, $user_id, ...$params);
} else {
    $total_stmt->bind_param("i", $user_id);
}
$total_stmt->execute();
$total_amount = $total_stmt->get_result()->fetch_assoc()['total'] ?? 0;

// --- 4. CHUẨN BỊ DỮ LIỆU JS ---
$cat_labels = []; $cat_data = [];
while ($row = $category_result->fetch_assoc()) {
    $raw_cat = $row['category'];
    $cat_labels[] = $cat_map[$raw_cat] ?? $raw_cat; 
    $cat_data[] = $row['total'];
}
$category_result->data_seek(0);

$month_labels = []; $month_data = [];
while ($row = $monthly_result->fetch_assoc()) {
    $month_labels[] = $row['month_label'];
    $month_data[] = $row['total'];
}

$week_labels = []; $week_data = [];
while ($row = $weekly_result->fetch_assoc()) {
    $week_labels[] = $row['week_label'];
    $week_data[] = $row['total'];
}

$conn->close();

// --- 5. GIAO DIỆN ---
$page_title = 'Biểu đồ chi tiêu';
$active_page = 'chart';

include $_SERVER['DOCUMENT_ROOT'] . '/Quanlychitieu/includes/header.php';
?>

<style>
    .charts-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 25px; margin-bottom: 30px; }
    .chart-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); display: flex; flex-direction: column; }
    .chart-card.full-width { grid-column: 1 / -1; }
    .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #edf2f7; padding-bottom: 10px; }
    .chart-header h3 { font-size: 16px; color: #2d3748; font-weight: 600; display: flex; align-items: center; gap: 8px; }
    .chart-container { position: relative; height: 300px; width: 100%; }
    
    /* ===== BỘ LỌC TAB STYLE (ĐẸP) ===== */
    .filter-container {
        display: inline-flex;
        background: #f1f5f9; /* Màu nền xám nhạt */
        padding: 4px;
        border-radius: 12px; /* Bo tròn cả cụm */
        gap: 5px;
    }
    
    .filter-item {
        text-decoration: none;
        padding: 8px 16px;
        font-size: 14px;
        font-weight: 500;
        color: #64748b;
        border-radius: 8px; /* Bo tròn từng nút */
        transition: all 0.3s ease;
        border: none;
    }
    
    .filter-item:hover {
        color: #0f172a;
        background: rgba(255,255,255, 0.5);
    }
    
    /* Trạng thái đang chọn */
    .filter-item.active {
        background: #ffffff;
        color: #2f855a; /* Màu xanh chủ đạo */
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(0,0,0,0.08); /* Đổ bóng nhẹ */
    }

    /* List chi tiết */
    .categories-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
    .category-item { display: flex; align-items: center; padding: 10px; border-radius: 8px; border: 1px solid #edf2f7; transition: transform 0.2s; }
    .category-item:hover { transform: translateY(-2px); border-color: #cbd5e0; }
    .color-dot { width: 12px; height: 12px; border-radius: 50%; margin-right: 10px; flex-shrink: 0; }
    .cat-name { font-size: 14px; color: #4a5568; flex-grow: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cat-total { font-weight: 600; font-size: 14px; color: #2d3748; }
    
    @media (max-width: 768px) { 
        .charts-grid { grid-template-columns: 1fr; } 
        .chart-container { height: 250px; }
        .page-header { flex-direction: column; align-items: flex-start; gap: 15px; }
        .filter-container { width: 100%; justify-content: space-between; }
        .filter-item { flex-grow: 1; text-align: center; padding: 8px 5px; font-size: 13px; }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<section class="charts-page">
    <div class="container">
        
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <h1 style="font-size: 24px; color: #2d3748; font-weight: 700;">Phân tích tài chính</h1>
                <p style="color: #718096;">Dữ liệu trực quan về dòng tiền của bạn.</p>
            </div>

            <div class="filter-container">
                <a href="?filter=month" class="filter-item <?= ($filter == 'month') ? 'active' : '' ?>">
                    Tháng này
                </a>
                <a href="?filter=last3months" class="filter-item <?= ($filter == 'last3months') ? 'active' : '' ?>">
                    3 Tháng
                </a>
                <a href="?filter=year" class="filter-item <?= ($filter == 'year') ? 'active' : '' ?>">
                    Năm nay
                </a>
            </div>

        </div>

        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(47, 133, 90, 0.1); color: #2f855a;"><i class="fas fa-coins"></i></div>
                <div class="stat-info">
                    <h3>Tổng chi tiêu</h3>
                    <p class="stat-value"><?= number_format($total_amount, 0, ',', '.') ?>₫</p>
                    <p class="stat-change">
                        <?php 
                        if($filter == 'month') echo 'Trong tháng này';
                        elseif($filter == 'year') echo 'Trong năm nay';
                        else echo '3 tháng gần đây';
                        ?>
                    </p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(49, 130, 206, 0.1); color: #3182ce;"><i class="fas fa-list-ul"></i></div>
                <div class="stat-info">
                    <h3>Số danh mục</h3>
                    <p class="stat-value"><?= $category_result->num_rows ?></p>
                    <p class="stat-change">Đã phát sinh chi phí</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(221, 107, 32, 0.1); color: #dd6b20;"><i class="fas fa-calendar-day"></i></div>
                <div class="stat-info">
                    <h3>Trung bình/Ngày</h3>
                    <?php 
                        $days = ($filter == 'month') ? date('d') : 30;
                        if($filter == 'year') $days = date('z') + 1; // Số ngày đã qua trong năm
                        $daily = $days > 0 ? $total_amount / $days : 0;
                    ?>
                    <p class="stat-value"><?= number_format($daily, 0, ',', '.') ?>₫</p>
                    <p class="stat-change">Ước tính</p>
                </div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-pie"></i> Cơ cấu chi tiêu</h3>
                </div>
                <div class="chart-container">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-bar"></i> 4 Tuần gần nhất</h3>
                </div>
                <div class="chart-container">
                    <canvas id="weeklyChart"></canvas>
                </div>
            </div>

            <div class="chart-card full-width">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-line"></i> Xu hướng 6 tháng qua</h3>
                </div>
                <div class="chart-container" style="height: 350px;">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>

        <div class="chart-card full-width" style="margin-top: 20px;">
            <div class="chart-header">
                <h3><i class="fas fa-stream"></i> Chi tiết theo danh mục</h3>
            </div>
            <div class="categories-list">
                <?php 
                $i = 0;
                if($category_result->num_rows > 0):
                    while($cat = $category_result->fetch_assoc()): 
                        $color = $chart_colors[$i % count($chart_colors)];
                        $raw_cat = $cat['category'];
                        $display_name = $cat_map[$raw_cat] ?? $raw_cat;
                ?>
                    <div class="category-item">
                        <div class="color-dot" style="background-color: <?= $color ?>"></div>
                        <span class="cat-name"><?= htmlspecialchars($display_name) ?></span>
                        <span class="cat-total"><?= number_format($cat['total'], 0, ',', '.') ?>₫</span>
                    </div>
                <?php 
                    $i++; 
                    endwhile; 
                else:
                    echo '<p style="color:#718096; font-style:italic; padding:10px;">Chưa có dữ liệu chi tiêu cho khoảng thời gian này.</p>';
                endif;
                ?>
            </div>
        </div>

    </div>
</section>

<script>
    // 1. Biểu đồ Tròn
    const catCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(catCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($cat_labels) ?>,
            datasets: [{
                data: <?= json_encode($cat_data) ?>,
                backgroundColor: <?= json_encode(array_slice($chart_colors, 0, count($cat_labels))) ?>,
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { boxWidth: 12, usePointStyle: true, font: {size: 11} } }
            }
        }
    });

    // 2. Biểu đồ Cột
    const weekCtx = document.getElementById('weeklyChart').getContext('2d');
    new Chart(weekCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($week_labels) ?>,
            datasets: [{
                label: 'Chi tiêu',
                data: <?= json_encode($week_data) ?>,
                backgroundColor: '#38a169',
                borderRadius: 5,
                barPercentage: 0.6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { 
                y: { beginAtZero: true, grid: { display: false } },
                x: { grid: { display: false } }
            }
        }
    });

    // 3. Biểu đồ Đường
    const monthCtx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(monthCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($month_labels) ?>,
            datasets: [{
                label: 'Tổng chi',
                data: <?= json_encode($month_data) ?>,
                borderColor: '#3182ce',
                backgroundColor: 'rgba(49, 130, 206, 0.1)',
                borderWidth: 3,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#3182ce',
                pointRadius: 5,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { 
                y: { 
                    beginAtZero: true,
                    ticks: { callback: function(value) { return value.toLocaleString(); } }
                },
                x: { grid: { display: false } }
            }
        }
    });
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/Quanlychitieu/includes/footer.php'; ?>