<?php
session_start();
// Dùng đường dẫn tuyệt đối
include $_SERVER['DOCUMENT_ROOT'] . '/Quanlychitieu/function/db_connection.php';

// Check login
if(!isset($_SESSION['user_id'])){
    header("Location: /Quanlychitieu/view/auth/login.php");
    exit();
}

$conn = getDbConnection();
$user_id = $_SESSION['user_id'];

// --- 1. LẤY THAM SỐ TỪ URL ---
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? 'all'; 
$category_filter = $_GET['category'] ?? '';
$sort_option = $_GET['sort'] ?? 'newest';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 6; 

// --- 2. XÂY DỰNG QUERY ---
$where_clauses = ["user_id = ?"];
$params = [$user_id];
$types = "i";

if (!empty($search)) {
    $where_clauses[] = "(description LIKE ? OR category LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term; $params[] = $search_term; $types .= "ss";
}

if ($type_filter !== 'all') {
    $where_clauses[] = "type = ?";
    $params[] = $type_filter; $types .= "s";
}

if (!empty($category_filter) && $category_filter !== 'all') {
    $where_clauses[] = "category = ?";
    $params[] = $category_filter; $types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);

$order_sql = "date DESC, id DESC";
switch ($sort_option) {
    case 'oldest': $order_sql = "date ASC, id ASC"; break;
    case 'amount_desc': $order_sql = "amount DESC, id DESC"; break;
    case 'amount_asc': $order_sql = "amount ASC, id ASC"; break;
}

// --- 3. PHÂN TRANG ---
$count_sql = "SELECT COUNT(*) as total FROM transactions WHERE $where_sql";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);
$offset = ($page - 1) * $limit;

// --- 4. LẤY DỮ LIỆU ---
$data_sql = "SELECT * FROM transactions WHERE $where_sql ORDER BY $order_sql LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($data_sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// --- 5. THỐNG KÊ TỔNG QUAN ---
$stats_sql = "SELECT type, SUM(amount) as total FROM transactions WHERE user_id = ? GROUP BY type";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats_res = $stats_stmt->get_result();

$total_income = 0;
$total_expense = 0;

while ($row = $stats_res->fetch_assoc()) {
    if ($row['type'] == 'income') $total_income = $row['total'];
    if ($row['type'] == 'expense') $total_expense = $row['total'];
}
$balance = $total_income - $total_expense;

function buildQuery($new_params = []) {
    $params = $_GET;
    foreach ($new_params as $key => $value) $params[$key] = $value;
    return http_build_query($params);
}
$conn->close();

// --- CẤU HÌNH HEADER ---
$page_title = 'Quản lý thu chi';
$active_page = 'transactions';

include $_SERVER['DOCUMENT_ROOT'] . '/Quanlychitieu/includes/header.php';
?>

<style>
    /* CSS Thống kê */
    .stats-overview { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px; }
    .stat-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
    .stat-info h3 { font-size: 14px; color: #718096; margin-bottom: 5px; }
    .stat-value { font-size: 20px; font-weight: 700; }
    
    .income-card .stat-icon { background: #def7ec; color: #03543f; }
    .income-card .stat-value { color: #046c4e; }
    .expense-card .stat-icon { background: #fde8e8; color: #9b1c1c; }
    .expense-card .stat-value { color: #c81e1e; }
    .balance-card .stat-icon { background: #e1effe; color: #1e429f; }
    .balance-card .stat-value { color: #3f83f8; }

    /* CSS Filter */
    .filters-toolbar { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 15px; justify-content: space-between; align-items: center; }
    .form-select { padding: 9px 15px; border: 1px solid #e2e8f0; border-radius: 8px; outline: none; color: #4a5568; cursor: pointer; }
    
    /* CSS Table */
    .transaction-table { width: 100%; border-collapse: collapse; }
    .transaction-table th { background: #f8fafc; padding: 15px; text-align: left; color: #64748b; font-weight: 600; border-bottom: 2px solid #edf2f7; font-size: 14px; }
    .transaction-table td { padding: 15px; border-bottom: 1px solid #edf2f7; color: #2d3748; font-size: 14px; }
    .transaction-table tr:hover { background-color: #f8f9fa; }

    /* Badge & Amount */
    .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
    .badge-income { background: #def7ec; color: #03543f; border: 1px solid #bcf0da; }
    .badge-expense { background: #fde8e8; color: #9b1c1c; border: 1px solid #fbd5d5; }
    .amount-income { color: #057a55; font-weight: 700; }
    .amount-expense { color: #e02424; font-weight: 700; }

    /* Action Buttons */
    .action-group { display: flex; justify-content: center; gap: 8px; }
    .action-btn { width: 34px; height: 34px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.2s; border: none; cursor: pointer; }
    .action-btn.edit { background: #e0f2fe; color: #0284c7; }
    .action-btn.edit:hover { background: #0284c7; color: white; }
    .action-btn.delete { background: #fee2e2; color: #dc2626; }
    .action-btn.delete:hover { background: #dc2626; color: white; }

    /* --- [MỚI] CSS CHO PHÂN TRANG (PAGINATION) --- */
    .pagination-container { display: flex; justify-content: center; margin-top: 30px; gap: 5px; }
    .pagination-link { 
        display: inline-block; padding: 8px 14px; 
        border: 1px solid #e2e8f0; border-radius: 8px; 
        text-decoration: none; color: #64748b; 
        background: white; transition: all 0.2s; font-weight: 500;
    }
    .pagination-link:hover { background-color: #f1f5f9; border-color: #cbd5e0; color: #0f172a; }
    .pagination-link.active { background-color: #2f855a; color: white; border-color: #2f855a; }
    .pagination-link.disabled { color: #cbd5e0; pointer-events: none; background: #f8fafc; }

    @media (max-width: 768px) { .filters-toolbar { flex-direction: column; align-items: stretch; } }
</style>

<section class="transactions-page">
    <div class="container">
        
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <h1 style="font-size: 24px; font-weight: 700; color: #2d3748; margin-bottom: 5px;">Quản Lý Thu Chi</h1>
                <p style="color: #718096;">Theo dõi dòng tiền ra vào của bạn.</p>
            </div>
            <a href="transactions_add.php" class="button" style="background: #2f855a; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-plus"></i> Thêm giao dịch
            </a>
        </div>

        <div class="stats-overview">
            <div class="stat-card income-card">
                <div class="stat-icon"><i class="fas fa-arrow-down"></i></div>
                <div class="stat-info">
                    <h3>Tổng Thu Nhập</h3>
                    <p class="stat-value">+ <?php echo number_format($total_income, 0, ',', '.'); ?> ₫</p>
                </div>
            </div>
            <div class="stat-card expense-card">
                <div class="stat-icon"><i class="fas fa-arrow-up"></i></div>
                <div class="stat-info">
                    <h3>Tổng Chi Tiêu</h3>
                    <p class="stat-value">- <?php echo number_format($total_expense, 0, ',', '.'); ?> ₫</p>
                </div>
            </div>
            <div class="stat-card balance-card">
                <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                <div class="stat-info">
                    <h3>Số Dư Hiện Tại</h3>
                    <p class="stat-value"><?php echo number_format($balance, 0, ',', '.'); ?> ₫</p>
                </div>
            </div>
        </div>

        <div class="filters-toolbar">
            <form method="GET" id="filterForm" style="display: flex; gap: 10px; flex-wrap: wrap; width: 100%; align-items: center;">
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">

                <select name="type" class="form-select" onchange="document.getElementById('filterForm').submit()" style="font-weight: 600;">
                    <option value="all" <?= $type_filter == 'all' ? 'selected' : '' ?>>Tất cả giao dịch</option>
                    <option value="expense" <?= $type_filter == 'expense' ? 'selected' : '' ?>>🔴 Khoản Chi</option>
                    <option value="income" <?= $type_filter == 'income' ? 'selected' : '' ?>>🟢 Khoản Thu</option>
                </select>

                <select name="category" class="form-select" onchange="document.getElementById('filterForm').submit()">
                    <option value="all">-- Tất cả danh mục --</option>
                    <optgroup label="Chi tiêu">
                        <option value="Food" <?= $category_filter == 'Food' ? 'selected' : '' ?>>Ăn uống</option>
                        <option value="Transport" <?= $category_filter == 'Transport' ? 'selected' : '' ?>>Di chuyển</option>
                        <option value="Shopping" <?= $category_filter == 'Shopping' ? 'selected' : '' ?>>Mua sắm</option>
                        <option value="Bills" <?= $category_filter == 'Bills' ? 'selected' : '' ?>>Hóa đơn</option>
                        <option value="Entertainment" <?= $category_filter == 'Entertainment' ? 'selected' : '' ?>>Giải trí</option>
                    </optgroup>
                    <optgroup label="Thu nhập">
                        <option value="Salary" <?= $category_filter == 'Salary' ? 'selected' : '' ?>>Lương</option>
                        <option value="Bonus" <?= $category_filter == 'Bonus' ? 'selected' : '' ?>>Thưởng</option>
                        <option value="Investment" <?= $category_filter == 'Investment' ? 'selected' : '' ?>>Đầu tư</option>
                        <option value="Other" <?= $category_filter == 'Other' ? 'selected' : '' ?>>Khác</option>
                    </optgroup>
                </select>

                <select name="sort" class="form-select" onchange="document.getElementById('filterForm').submit()">
                    <option value="newest" <?= $sort_option == 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                    <option value="oldest" <?= $sort_option == 'oldest' ? 'selected' : '' ?>>Cũ nhất</option>
                    <option value="amount_desc" <?= $sort_option == 'amount_desc' ? 'selected' : '' ?>>Số tiền giảm dần</option>
                    <option value="amount_asc" <?= $sort_option == 'amount_asc' ? 'selected' : '' ?>>Số tiền tăng dần</option>
                </select>

                <?php if($type_filter != 'all' || !empty($category_filter) || $sort_option != 'newest' || !empty($search)): ?>
                    <a href="transactions.php" style="margin-left: auto; color: #718096; text-decoration: none; font-size: 13px;"><i class="fas fa-undo"></i> Đặt lại</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container" style="background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="overflow-x: auto;">
                <table class="transaction-table">
                    <thead>
                        <tr>
                            <th>Ngày</th>
                            <th>Loại</th>
                            <th>Danh mục</th>
                            <th>Mô tả</th>
                            <th>Số tiền</th>
                            <th style="text-align: center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result->num_rows > 0): ?>
                            <?php 
                            $cat_map = [
                                'Food' => '🍔 Ăn uống', 'Transport' => '🛵 Di chuyển', 'Shopping' => '🛍️ Mua sắm',
                                'Bills' => '🧾 Hóa đơn', 'Entertainment' => '🎬 Giải trí', 'Other' => '📦 Khác',
                                'Salary' => '💰 Lương', 'Bonus' => '🎁 Thưởng', 'Investment' => '📈 Đầu tư'
                            ];
                            ?>
                            <?php while($row = $result->fetch_assoc()): 
                                $type = $row['type'] ?? 'expense';
                                $is_income = ($type === 'income');
                                $cat_raw = $row['category'];
                                $display_name = $cat_map[$cat_raw] ?? $cat_raw;
                            ?>
                            <tr style="border-bottom: 1px solid #f3f4f6;">
                                <td><?php echo date('d/m/Y', strtotime($row['date'])); ?></td>
                                <td>
                                    <?php if($is_income): ?>
                                        <span class="badge badge-income"><i class="fas fa-arrow-down"></i> Thu</span>
                                    <?php else: ?>
                                        <span class="badge badge-expense"><i class="fas fa-arrow-up"></i> Chi</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($display_name); ?></td>
                                <td style="color: #6b7280;"><?php echo htmlspecialchars($row['description']); ?></td>
                                <td class="<?php echo $is_income ? 'amount-income' : 'amount-expense'; ?>">
                                    <?php echo $is_income ? '+' : '-'; ?> 
                                    <?php echo number_format($row['amount'], 0, ',', '.'); ?> ₫
                                </td>
                                <td style="text-align: center;">
                                    <div class="action-group">
                                        <a href="transactions_edit.php?id=<?php echo $row['id']; ?>" class="action-btn edit" title="Sửa"><i class="fas fa-edit"></i></a>
                                        <a href="transactions_delete.php?id=<?php echo $row['id']; ?>" class="action-btn delete" title="Xóa" onclick="return confirm('Bạn chắc chắn muốn xóa?')"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: #9ca3af;">
                                    <i class="fas fa-inbox" style="font-size: 40px; margin-bottom: 10px; display: block; color: #e5e7eb;"></i>
                                    Không tìm thấy giao dịch nào.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination-container" style="margin-top: 30px;">
            <a href="?<?php echo buildQuery(['page' => $page - 1]); ?>" class="pagination-link <?php echo ($page <= 1) ? 'disabled' : ''; ?>"><i class="fas fa-chevron-left"></i></a>
            
            <?php 
            $start = max(1, $page - 2); $end = min($total_pages, $page + 2);
            
            if($start > 1) echo '<a href="?'.buildQuery(['page'=>1]).'" class="pagination-link">1</a>';
            if($start > 2) echo '<span style="padding:0 5px; color:#cbd5e0;">...</span>';

            for ($i = $start; $i <= $end; $i++): ?>
                <a href="?<?php echo buildQuery(['page' => $i]); ?>" class="pagination-link <?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor;

            if($end < $total_pages - 1) echo '<span style="padding:0 5px; color:#cbd5e0;">...</span>';
            if($end < $total_pages) echo '<a href="?'.buildQuery(['page'=>$total_pages]).'" class="pagination-link">'.$total_pages.'</a>';
            ?>
            
            <a href="?<?php echo buildQuery(['page' => $page + 1]); ?>" class="pagination-link <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>"><i class="fas fa-chevron-right"></i></a>
        </div>
        <?php endif; ?>

    </div>
</section>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/Quanlychitieu/includes/footer.php'; ?>