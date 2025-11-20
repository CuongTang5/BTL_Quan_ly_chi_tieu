<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/Quanlychitieu/function/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /Quanlychitieu/view/auth/login.php");
    exit();
}

$conn = getDbConnection();
$user_id = $_SESSION['user_id'];

// --- XỬ LÝ FORM ---
// 1. Thêm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_invest'])) {
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $amount = floatval($_POST['amount']);
    $date = $_POST['date'];

    if ($amount > 0 && !empty($name)) {
        $stmt = $conn->prepare("INSERT INTO investments (user_id, name, type, initial_amount, current_amount, buy_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdds", $user_id, $name, $type, $amount, $amount, $date);
        $stmt->execute();
        header("Location: investments.php"); exit();
    }
}

// 2. Cập nhật giá
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_value'])) {
    $id = intval($_POST['invest_id']);
    $new_value = floatval($_POST['current_value']);
    if ($new_value >= 0) {
        $stmt = $conn->prepare("UPDATE investments SET current_amount = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("dii", $new_value, $id, $user_id);
        $stmt->execute();
        header("Location: investments.php"); exit();
    }
}

// 3. Xóa
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM investments WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $del_id, $user_id);
    $stmt->execute();
    header("Location: investments.php"); exit();
}

// --- LẤY DỮ LIỆU ---
$stmt = $conn->prepare("SELECT * FROM investments WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$total_invested = 0;
$total_value = 0;
$items = [];

while ($row = $result->fetch_assoc()) {
    $total_invested += $row['initial_amount'];
    $total_value += $row['current_amount'];
    $items[] = $row;
}

$total_profit = $total_value - $total_invested;
$profit_percent = $total_invested > 0 ? ($total_profit / $total_invested) * 100 : 0;

$conn->close();

$page_title = 'Danh mục đầu tư';
$active_page = 'investments';

include $_SERVER['DOCUMENT_ROOT'] . '/Quanlychitieu/includes/header.php';
?>

<style>
    /* 1. Header & Buttons */
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
    .page-title { font-size: 24px; color: #2d3748; font-weight: 700; }
    .btn-primary {
        background: var(--primary-color, #2f855a); color: white; 
        padding: 10px 20px; border-radius: 8px; border: none; 
        font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 8px;
        text-decoration: none; transition: 0.2s;
    }
    .btn-primary:hover { background: #276749; transform: translateY(-2px); }

    /* 2. Stats Cards (Giống Dashboard) */
    .stats-overview { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 40px; }
    .stat-card { background: white; padding: 25px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 20px; border: 1px solid #edf2f7; }
    .stat-icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
    .stat-info h3 { font-size: 14px; color: #718096; margin-bottom: 5px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-value { font-size: 24px; font-weight: 700; color: #2d3748; }
    
    /* Màu sắc Stats */
    .icon-blue { background: #ebf8ff; color: #3182ce; }
    .icon-green { background: #f0fff4; color: #38a169; }
    .icon-purple { background: #faf5ff; color: #805ad5; }
    .text-success { color: #38a169; }
    .text-danger { color: #e53e3e; }

    /* 3. Grid Danh mục */
    .invest-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px; }
    
    .asset-card {
        background: white; border-radius: 16px; padding: 25px;
        border: 1px solid #edf2f7; box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        transition: all 0.3s ease; display: flex; flex-direction: column;
    }
    .asset-card:hover { transform: translateY(-5px); box-shadow: 0 12px 20px -5px rgba(0,0,0,0.1); border-color: #cbd5e0; }

    /* Asset Header */
    .asset-header { display: flex; justify-content: space-between; margin-bottom: 20px; }
    .asset-title { display: flex; gap: 15px; align-items: center; }
    .asset-icon-box { 
        width: 50px; height: 50px; border-radius: 12px; 
        display: flex; align-items: center; justify-content: center; font-size: 22px;
    }
    .asset-name h4 { margin: 0; font-size: 16px; font-weight: 700; color: #2d3748; }
    .asset-date { font-size: 12px; color: #a0aec0; }

    /* Asset Body (So sánh giá) */
    .asset-body { 
        background: #f7fafc; border-radius: 12px; padding: 15px; 
        display: flex; justify-content: space-between; margin-bottom: 20px;
    }
    .price-col { display: flex; flex-direction: column; }
    .price-label { font-size: 11px; text-transform: uppercase; color: #718096; font-weight: 600; margin-bottom: 5px; }
    .price-val { font-weight: 700; color: #2d3748; font-size: 15px; }
    .arrow-icon { align-self: center; color: #cbd5e0; font-size: 14px; }

    /* Asset Footer */
    .asset-footer { display: flex; justify-content: space-between; align-items: center; margin-top: auto; }
    
    /* Badge Lãi/Lỗ */
    .pnl-badge { padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 5px; }
    .pnl-up { background: #c6f6d5; color: #22543d; }
    .pnl-down { background: #fed7d7; color: #822727; }

    /* Actions */
    .action-row { display: flex; gap: 10px; }
    .btn-icon { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; border: none; cursor: pointer; transition: 0.2s; }
    .btn-edit { background: #ebf8ff; color: #3182ce; }
    .btn-edit:hover { background: #3182ce; color: white; }
    .btn-del { background: #fff5f5; color: #e53e3e; }
    .btn-del:hover { background: #e53e3e; color: white; }

    /* Loại Asset Colors */
    .type-gold { background: #fffaf0; color: #d69e2e; }
    .type-stock { background: #ebf8ff; color: #3182ce; }
    .type-crypto { background: #fff5f5; color: #e53e3e; }
    .type-saving { background: #f0fff4; color: #38a169; }
    .type-other { background: #edf2f7; color: #718096; }

    /* MODAL (Giống trang Goals) */
    .modal { display: none; position: fixed; z-index: 999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; backdrop-filter: blur(2px); }
    .modal-box { background: white; width: 90%; max-width: 500px; padding: 30px; border-radius: 16px; animation: slideUp 0.3s ease; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
    @keyframes slideUp { from {transform: translateY(20px); opacity: 0;} to {transform: translateY(0); opacity: 1;} }
    
    .form-group { margin-bottom: 15px; }
    .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #4a5568; font-size: 14px; }
    .form-input { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; outline: none; box-sizing: border-box; transition: 0.2s; }
    .form-input:focus { border-color: #3182ce; box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1); }
    .modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px; }
    .btn-secondary { background: #edf2f7; color: #4a5568; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 500; }
</style>

<section class="invest-page">
    <div class="container">
        
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="fas fa-chart-line"></i> Đầu Tư & Tích Lũy</h1>
                <p style="color: #718096; margin-top: 5px;">Quản lý tài sản và theo dõi lợi nhuận.</p>
            </div>
            <button onclick="openModal('addModal')" class="btn-primary">
                <i class="fas fa-plus-circle"></i> Thêm khoản đầu tư
            </button>
        </div>

        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-icon icon-blue"><i class="fas fa-wallet"></i></div>
                <div class="stat-info">
                    <h3>Tổng Tài Sản</h3>
                    <div class="stat-value"><?php echo number_format($total_value, 0, ',', '.'); ?> ₫</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-green"><i class="fas fa-seedling"></i></div>
                <div class="stat-info">
                    <h3>Vốn Ban Đầu</h3>
                    <div class="stat-value"><?php echo number_format($total_invested, 0, ',', '.'); ?> ₫</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-purple"><i class="fas fa-percentage"></i></div>
                <div class="stat-info">
                    <h3>Lợi Nhuận (P/L)</h3>
                    <div class="stat-value <?php echo $total_profit >= 0 ? 'text-success' : 'text-danger'; ?>">
                        <?php echo $total_profit >= 0 ? '+' : ''; ?><?php echo number_format($total_profit, 0, ',', '.'); ?> ₫
                    </div>
                    <small style="<?php echo $profit_percent >= 0 ? 'color:#38a169' : 'color:#e53e3e'; ?>; font-weight:600;">
                        <?php echo $profit_percent >= 0 ? '▲' : '▼'; ?> <?php echo number_format(abs($profit_percent), 2); ?>%
                    </small>
                </div>
            </div>
        </div>

        <div class="invest-grid">
            <?php if (count($items) > 0): ?>
                <?php foreach ($items as $item): 
                    $pnl = $item['current_amount'] - $item['initial_amount'];
                    $pnl_percent = $item['initial_amount'] > 0 ? ($pnl / $item['initial_amount']) * 100 : 0;
                    
                    // Config Icon & Style
                    $icon = 'fa-box-open'; $style = 'type-other';
                    switch($item['type']) {
                        case 'gold': $icon = 'fa-gem'; $style = 'type-gold'; break;
                        case 'stock': $icon = 'fa-chart-bar'; $style = 'type-stock'; break;
                        case 'crypto': $icon = 'fa-bitcoin'; $style = 'type-crypto'; break;
                        case 'saving': $icon = 'fa-piggy-bank'; $style = 'type-saving'; break;
                    }
                ?>
                <div class="asset-card">
                    <div class="asset-header">
                        <div class="asset-title">
                            <div class="asset-icon-box <?php echo $style; ?>">
                                <i class="fas <?php echo $icon; ?>"></i>
                            </div>
                            <div class="asset-name">
                                <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                <span class="asset-date"><?php echo date('d/m/Y', strtotime($item['buy_date'])); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="asset-body">
                        <div class="price-col">
                            <span class="price-label">Vốn gốc</span>
                            <span class="price-val"><?php echo number_format($item['initial_amount'], 0, ',', '.'); ?></span>
                        </div>
                        <div class="arrow-icon"><i class="fas fa-arrow-right"></i></div>
                        <div class="price-col" style="text-align: right;">
                            <span class="price-label">Hiện tại</span>
                            <span class="price-val" style="color: #3182ce;"><?php echo number_format($item['current_amount'], 0, ',', '.'); ?></span>
                        </div>
                    </div>

                    <div class="asset-footer">
                        <div class="pnl-badge <?php echo $pnl >= 0 ? 'pnl-up' : 'pnl-down'; ?>">
                            <i class="fas fa-<?php echo $pnl >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                            <?php echo number_format(abs($pnl_percent), 1); ?>%
                        </div>
                        
                        <div class="action-row">
                            <button class="btn-icon btn-edit" title="Cập nhật giá" onclick="openUpdateModal(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>', <?php echo $item['current_amount']; ?>)">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <a href="?delete_id=<?php echo $item['id']; ?>" class="btn-icon btn-del" title="Xóa/Chốt lời" onclick="return confirm('Bạn có chắc muốn xóa khoản đầu tư này?')">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 60px; color: #a0aec0; background: white; border-radius: 16px; border: 1px dashed #cbd5e0;">
                    <i class="fas fa-seedling" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                    Chưa có khoản đầu tư nào. Hãy bắt đầu tích lũy ngay!
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<div id="addModal" class="modal">
    <div class="modal-box">
        <h2 style="margin-bottom: 20px; color: #2d3748; font-size: 20px;">🌱 Thêm khoản đầu tư</h2>
        <form method="POST">
            <input type="hidden" name="add_invest" value="1">
            
            <div class="form-group">
                <label class="form-label">Tên tài sản</label>
                <input type="text" name="name" class="form-input" placeholder="VD: Vàng SJC 1 chỉ, Cổ phiếu FPT..." required>
            </div>

            <div class="form-group">
                <label class="form-label">Loại hình</label>
                <select name="type" class="form-input" style="cursor: pointer;">
                    <option value="gold">🥇 Vàng / Kim loại quý</option>
                    <option value="stock">📈 Chứng khoán / Cổ phiếu</option>
                    <option value="crypto">🪙 Crypto / Tiền ảo</option>
                    <option value="saving">🐷 Sổ tiết kiệm</option>
                    <option value="other">📦 Khác</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Số vốn bỏ ra (VNĐ)</label>
                <input type="number" name="amount" class="form-input" placeholder="VD: 5000000" required min="0" step="1000">
            </div>

            <div class="form-group">
                <label class="form-label">Ngày mua</label>
                <input type="date" name="date" class="form-input" value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('addModal')">Hủy</button>
                <button type="submit" class="btn-primary">Lưu lại</button>
            </div>
        </form>
    </div>
</div>

<div id="updateModal" class="modal">
    <div class="modal-box">
        <h2 style="margin-bottom: 10px; color: #2d3748; font-size: 20px;">📈 Cập nhật thị trường</h2>
        <p style="color: #718096; font-size: 14px; margin-bottom: 20px;">Tài sản: <strong id="up_name" style="color: #3182ce;">...</strong></p>
        
        <form method="POST">
            <input type="hidden" name="update_value" value="1">
            <input type="hidden" name="invest_id" id="up_id">
            
            <div class="form-group">
                <label class="form-label">Giá trị hiện tại (VNĐ)</label>
                <input type="number" name="current_value" id="up_val" class="form-input" required min="0" step="1000">
                <small style="color: #a0aec0; display: block; margin-top: 5px;">Nhập giá trị thực tế của tài sản tại thời điểm này.</small>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('updateModal')">Hủy</button>
                <button type="submit" class="btn-primary">Cập nhật ngay</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) { document.getElementById(id).style.display = 'flex'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }
    
    function openUpdateModal(id, name, val) {
        document.getElementById('up_id').value = id;
        document.getElementById('up_name').innerText = name;
        document.getElementById('up_val').value = val;
        openModal('updateModal');
    }

    window.onclick = function(e) {
        if (e.target.classList.contains('modal')) e.target.style.display = 'none';
    }
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/Quanlychitieu/includes/footer.php'; ?>