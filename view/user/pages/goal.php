<?php
session_start();
// Dùng đường dẫn tuyệt đối
include $_SERVER['DOCUMENT_ROOT'] . '/Quanlychitieu/function/db_connection.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: /Quanlychitieu/view/auth/login.php");
    exit();
}

$conn = getDbConnection();
$user_id = $_SESSION['user_id'];

// --- XỬ LÝ FORM ---

// 1. Thêm mục tiêu mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_goal'])) {
    $goal_name = trim($_POST['goal_name']);
    $target_amount = floatval($_POST['target_amount']);
    $target_date = $_POST['target_date'];
    $description = trim($_POST['description']);

    if (!empty($goal_name) && $target_amount > 0) {
        $stmt = $conn->prepare("INSERT INTO savings_goals (user_id, goal_name, target_amount, current_amount, target_date, description) VALUES (?, ?, ?, 0, ?, ?)");
        $stmt->bind_param("isdss", $user_id, $goal_name, $target_amount, $target_date, $description);

        if ($stmt->execute()) {
            header("Location: goal.php");
            exit();
        }
    }
}

// 2. Cập nhật tiền
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_progress'])) {
    $goal_id = intval($_POST['goal_id']);
    $amount_to_add = floatval($_POST['amount_to_add']);

    if ($amount_to_add > 0) {
        $stmt = $conn->prepare("SELECT current_amount, target_amount FROM savings_goals WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $goal_id, $user_id);
        $stmt->execute();
        $goal = $stmt->get_result()->fetch_assoc();

        if ($goal) {
            $new_amount = $goal['current_amount'] + $amount_to_add;
            $completed = ($new_amount >= $goal['target_amount']) ? 1 : 0;

            $update_stmt = $conn->prepare("UPDATE savings_goals SET current_amount = ?, completed = ? WHERE id=? AND user_id=?");
            $update_stmt->bind_param("diii", $new_amount, $completed, $goal_id, $user_id);
            $update_stmt->execute();
            
            header("Location: goal.php");
            exit();
        }
    }
}

// 3. Xóa mục tiêu
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM savings_goals WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $delete_id, $user_id);
    $stmt->execute();
    header("Location: goal.php");
    exit();
}

// --- LẤY DỮ LIỆU ---
$stmt = $conn->prepare("SELECT * FROM savings_goals WHERE user_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Tính thống kê
$total_goals = $result->num_rows;
$total_saved = 0;
$total_target = 0;
while ($row = $result->fetch_assoc()) {
    $total_saved += $row['current_amount'];
    $total_target += $row['target_amount'];
}
$result->data_seek(0); 

$conn->close();

// --- GIAO DIỆN ---
$page_title = 'Mục tiêu tiết kiệm';
$active_page = 'goal';

include $_SERVER['DOCUMENT_ROOT'] . '/Quanlychitieu/includes/header.php';
?>

<style>
    /* Header Page */
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
    .page-title { font-size: 24px; color: #2d3748; font-weight: 700; }
    
    /* Stats Cards */
    .stats-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 40px; }
    
    /* Grid Mục tiêu */
    .goals-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px; }
    
    /* Card Mục tiêu Style */
    .goal-card {
        background: white; border-radius: 16px; padding: 25px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        display: flex; flex-direction: column; justify-content: space-between;
        transition: transform 0.3s ease;
        border: 1px solid #edf2f7;
        position: relative; overflow: hidden;
    }
    .goal-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
    
    /* Trạng thái hoàn thành */
    .goal-card.completed { border: 2px solid #48bb78; background: #f0fff4; }
    .goal-card.completed::after {
        content: "DONE"; position: absolute; top: 10px; right: -30px;
        background: #48bb78; color: white; padding: 5px 40px;
        transform: rotate(45deg); font-size: 12px; font-weight: bold;
    }

    .goal-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
    .goal-header h3 { font-size: 18px; color: #2d3748; margin: 0; font-weight: 600; }
    
    /* Thanh Tiến độ */
    .progress-container { margin: 20px 0; }
    .progress-labels { display: flex; justify-content: space-between; font-size: 13px; color: #718096; margin-bottom: 5px; }
    .progress-bar-bg { width: 100%; height: 10px; background: #edf2f7; border-radius: 5px; overflow: hidden; }
    .progress-bar-fill { height: 100%; background: linear-gradient(90deg, #4fd1c5 0%, #2f855a 100%); border-radius: 5px; transition: width 0.5s ease; }
    
    /* Nút bấm trong card */
    .goal-actions { display: flex; gap: 10px; margin-top: 20px; }
    .btn-add-money {
        flex: 1; padding: 10px; background: #3182ce; color: white; border: none; border-radius: 8px;
        cursor: pointer; font-weight: 500; display: flex; align-items: center; justify-content: center; gap: 5px; transition: 0.2s;
    }
    .btn-add-money:hover { background: #2b6cb0; }
    .btn-delete {
        padding: 10px; background: #fed7d7; color: #c53030; border: none; border-radius: 8px;
        cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center;
    }
    .btn-delete:hover { background: #feb2b2; }

    /* ===== MODAL CSS ===== */
    .modal {
        display: none; /* Mặc định ẩn */
        position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%;
        background-color: rgba(0,0,0,0.5);
        align-items: center; justify-content: center;
    }
    .modal-content {
        background-color: #fff; padding: 30px; border-radius: 16px;
        width: 90%; max-width: 500px; position: relative;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        animation: slideDown 0.3s ease;
    }
    @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
    .modal-header h2 { margin: 0; font-size: 20px; color: #2d3748; }
    .close-btn { background: none; border: none; font-size: 28px; cursor: pointer; color: #a0aec0; }
    .close-btn:hover { color: #2d3748; }

    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568; }
    .form-group input, .form-group textarea {
        width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; outline: none; box-sizing: border-box;
    }
    .form-group input:focus { border-color: #3182ce; }
    
    .modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
    .btn-cancel { padding: 10px 20px; background: #edf2f7; border: none; border-radius: 8px; cursor: pointer; color: #4a5568; }
    .btn-confirm { padding: 10px 20px; background: #2f855a; border: none; border-radius: 8px; cursor: pointer; color: white; font-weight: 600; }
    
    .empty-state { text-align: center; padding: 50px; color: #718096; }
    .empty-state i { font-size: 48px; margin-bottom: 15px; color: #cbd5e0; }
</style>

<section class="goals-page">
    <div class="container">
        
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="fas fa-bullseye"></i> Mục tiêu tiết kiệm</h1>
                <p style="color: #718096;">Đặt mục tiêu và hiện thực hóa ước mơ.</p>
            </div>
            <button class="button" onclick="openModal('addGoalModal')" style="background: var(--primary-color); color: white; padding: 12px 24px; border-radius: 8px; border: none; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-plus"></i> Thêm mục tiêu
            </button>
        </div>

        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(47, 133, 90, 0.1); color: #2f855a;"><i class="fas fa-bullseye"></i></div>
                <div class="stat-info">
                    <h3>Số mục tiêu</h3>
                    <p class="stat-value"><?php echo $total_goals; ?></p>
                    <p class="stat-desc">Đang theo đuổi</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(56, 161, 105, 0.1); color: #38a169;"><i class="fas fa-piggy-bank"></i></div>
                <div class="stat-info">
                    <h3>Đã tiết kiệm</h3>
                    <p class="stat-value"><?php echo number_format($total_saved, 0, ',', '.'); ?>₫</p>
                    <p class="stat-desc">Tổng tích lũy</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(49, 130, 206, 0.1); color: #3182ce;"><i class="fas fa-chart-pie"></i></div>
                <div class="stat-info">
                    <h3>Tiến độ chung</h3>
                    <p class="stat-value">
                        <?php echo $total_target > 0 ? number_format(($total_saved / $total_target) * 100, 1) : 0; ?>%
                    </p>
                    <p class="stat-desc">Trên tổng mục tiêu</p>
                </div>
            </div>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <div class="goals-grid">
                <?php while ($goal = $result->fetch_assoc()):
                    $percent = $goal['target_amount'] > 0 ? ($goal['current_amount'] / $goal['target_amount']) * 100 : 0;
                    $percent = min(100, $percent);
                    $is_completed = $percent >= 100;
                    $days_left = ceil((strtotime($goal['target_date']) - time()) / (60 * 60 * 24));
                ?>
                <div class="goal-card <?php echo $is_completed ? 'completed' : ''; ?>">
                    <div class="goal-header">
                        <div>
                            <h3><?php echo htmlspecialchars($goal['goal_name']); ?></h3>
                            <small style="color: #718096;">Ngày đích: <?php echo date('d/m/Y', strtotime($goal['target_date'])); ?></small>
                        </div>
                        <?php if(!$is_completed): ?>
                            <span style="background: #ebf8ff; color: #3182ce; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                                Còn <?php echo max(0, $days_left); ?> ngày
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="progress-container">
                        <div class="progress-labels">
                            <span><?php echo number_format($goal['current_amount'], 0, ',', '.'); ?>₫</span>
                            <span style="font-weight: 600;"><?php echo number_format($percent, 1); ?>%</span>
                            <span><?php echo number_format($goal['target_amount'], 0, ',', '.'); ?>₫</span>
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" style="width: <?php echo $percent; ?>%;"></div>
                        </div>
                    </div>

                    <p style="font-size: 14px; color: #718096; margin-bottom: 15px; height: 40px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                        <?php echo htmlspecialchars($goal['description'] ?: 'Không có mô tả thêm.'); ?>
                    </p>

                    <div class="goal-actions">
                        <button class="btn-add-money" onclick="openDepositModal(<?php echo $goal['id']; ?>, '<?php echo htmlspecialchars($goal['goal_name']); ?>')" <?php echo $is_completed ? 'disabled style="background:#ccc; cursor: not-allowed;"' : ''; ?>>
                            <i class="fas fa-coins"></i> Thêm tiền
                        </button>
                        <a href="?delete_id=<?php echo $goal['id']; ?>" class="btn-delete" onclick="return confirm('Xóa mục tiêu này? Dữ liệu không thể khôi phục.')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-bullseye"></i>
                <p>Bạn chưa có mục tiêu nào. Hãy đặt mục tiêu ngay!</p>
            </div>
        <?php endif; ?>

    </div>
</section>

<div id="addGoalModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>🎯 Tạo mục tiêu mới</h2>
            <button class="close-btn" onclick="closeModal('addGoalModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="add_goal" value="1">
            <div class="form-group">
                <label>Tên mục tiêu</label>
                <input type="text" name="goal_name" required placeholder="VD: Mua iPhone 16...">
            </div>
            <div class="form-group">
                <label>Số tiền cần (VNĐ)</label>
                <input type="number" name="target_amount" required step="1000" placeholder="VD: 20000000">
            </div>
            <div class="form-group">
                <label>Hạn hoàn thành</label>
                <input type="date" name="target_date" required min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label>Mô tả (Tùy chọn)</label>
                <textarea name="description" rows="3" placeholder="Ghi chú thêm..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('addGoalModal')">Hủy</button>
                <button type="submit" class="btn-confirm">Tạo mục tiêu</button>
            </div>
        </form>
    </div>
</div>

<div id="depositModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>💰 Thêm tiền tiết kiệm</h2>
            <button class="close-btn" onclick="closeModal('depositModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="update_progress" value="1">
            <input type="hidden" name="goal_id" id="deposit_goal_id">
            
            <p style="margin-bottom: 15px; color: #4a5568;">Bạn đang thêm tiền vào mục tiêu: <strong id="deposit_goal_name" style="color: #2f855a;">...</strong></p>
            
            <div class="form-group">
                <label>Số tiền thêm vào (VNĐ)</label>
                <input type="number" name="amount_to_add" required min="1000" step="1000" placeholder="VD: 500000" autofocus>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('depositModal')">Hủy</button>
                <button type="submit" class="btn-confirm">Xác nhận thêm</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Hàm mở Modal
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'flex';
    }

    // Hàm đóng Modal
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    // Hàm mở Modal nạp tiền và điền dữ liệu
    function openDepositModal(id, name) {
        document.getElementById('deposit_goal_id').value = id;
        document.getElementById('deposit_goal_name').innerText = name;
        openModal('depositModal');
    }

    // Đóng modal khi click ra ngoài vùng trắng
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = "none";
        }
    }
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/Quanlychitieu/includes/footer.php'; ?>