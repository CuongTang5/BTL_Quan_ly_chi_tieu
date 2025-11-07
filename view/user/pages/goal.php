<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include $_SERVER['DOCUMENT_ROOT'] . '/Quanlychitieu/function/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$conn = getDbConnection();
$user_id = $_SESSION['user_id'];

// Lấy danh sách mục tiêu
$stmt = $conn->prepare("SELECT * FROM savings_goals WHERE user_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Tính tổng số mục tiêu và số tiền đã tiết kiệm
$total_goals = $result->num_rows;
$total_saved = 0;
$total_target = 0;

while ($goal = $result->fetch_assoc()) {
    $total_saved += $goal['current_amount'];
    $total_target += $goal['target_amount'];
}
$result->data_seek(0); // Reset pointer để dùng lại

// Xử lý thêm mục tiêu mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_goal'])) {
    $goal_name = trim($_POST['goal_name']);
    $target_amount = floatval($_POST['target_amount']);
    $target_date = $_POST['target_date'];
    $description = trim($_POST['description']);

    if (!empty($goal_name) && $target_amount > 0) {
        $stmt = $conn->prepare("INSERT INTO savings_goals (user_id, goal_name, target_amount, current_amount, target_date, description) VALUES (?, ?, ?, 0, ?, ?)");
        // Cũ: $stmt->bind_param("isddss", $user_id, $goal_name, $target_amount, $target_date, $description);
// Mới: Bỏ đi tham số d thứ 2 và biến current_amount (vì nó đã là 0 trong SQL)
        $stmt->bind_param("isdss", $user_id, $goal_name, $target_amount, $target_date, $description);

        if ($stmt->execute()) {
            $success_message = "Thêm mục tiêu thành công!";
            // Refresh trang để hiển thị mục tiêu mới
            header("Location: /Quanlychitieu/view/user/pages/goal.php");
            exit();
        } else {
            $error_message = "Có lỗi xảy ra khi thêm mục tiêu!";
        }
    } else {
        $error_message = "Vui lòng điền đầy đủ thông tin!";
    }
}

// Xử lý cập nhật tiến độ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_progress'])) {
    $goal_id = intval($_POST['goal_id']);
    $amount_to_add = floatval($_POST['amount_to_add']);

    if ($amount_to_add > 0) {
        // Lấy thông tin mục tiêu hiện tại
        $stmt = $conn->prepare("SELECT current_amount, target_amount FROM savings_goals WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $goal_id, $user_id);
        $stmt->execute();
        $goal_result = $stmt->get_result();

        if ($goal_result->num_rows > 0) {
            $goal = $goal_result->fetch_assoc();
            $new_amount = $goal['current_amount'] + $amount_to_add;
            $completed = $new_amount >= $goal['target_amount'] ? 1 : 0;

            // Cập nhật số tiền hiện tại
            $stmt = $conn->prepare("UPDATE savings_goals SET current_amount = ?, completed = ? WHERE id=? AND user_id=?");
            $stmt->bind_param("ddii", $new_amount, $completed, $goal_id, $user_id);

            if ($stmt->execute()) {
                $success_message = "Cập nhật tiến độ thành công!";
                header("Location: /Quanlychitieu/view/user/pages/goal.php");
                exit();
            }
        }
    }
}

// Xử lý xóa mục tiêu
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM savings_goals WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $delete_id, $user_id);

    if ($stmt->execute()) {
        $success_message = "Xóa mục tiêu thành công!";
        header("Location: /Quanlychitieu/view/user/pages/goal.php");
        exit();
    } else {
        $error_message = "Có lỗi xảy ra khi xóa mục tiêu!";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Mục tiêu tiết kiệm - Quản Lý Chi Tiêu</title>

    <head>
        <link rel="stylesheet" href="/Quanlychitieu/css/style.css">
        <link rel="stylesheet" href="/Quanlychitieu/css/goal.css">
    </head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
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
</header>

    <section class="goals">
        <div class="container">
            <div class="page-header">
                <h1 class="page-title"><i class="fas fa-bullseye"></i> Mục tiêu tiết kiệm</h1>
                <button class="button" onclick="openModal()">
                    <i class="fas fa-plus"></i> Thêm mục tiêu mới
                </button>
            </div>

            <!-- Thống kê tổng quan -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Tổng số mục tiêu</h3>
                        <p class="stat-value"><?php echo $total_goals; ?></p>
                        <p class="stat-desc">Tất cả mục tiêu</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-piggy-bank"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Đã tiết kiệm</h3>
                        <p class="stat-value"><?php echo number_format($total_saved, 0, ',', '.'); ?>₫</p>
                        <p class="stat-desc">Tổng số tiền đã tiết kiệm</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-flag-checkered"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Tỷ lệ hoàn thành</h3>
                        <p class="stat-value">
                            <?php echo $total_target > 0 ? number_format(($total_saved / $total_target) * 100, 1) : 0; ?>%
                        </p>
                        <p class="stat-desc">Tiến độ tổng thể</p>
                    </div>
                </div>
            </div>

            <!-- Thông báo -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Danh sách mục tiêu -->
            <div class="goals-container">
                <?php if ($result->num_rows > 0): ?>
                    <div class="goals-grid">
                        <?php while ($goal = $result->fetch_assoc()):
                            $progress = $goal['target_amount'] > 0 ? ($goal['current_amount'] / $goal['target_amount']) * 100 : 0;
                            $progress = min(100, $progress);
                            $days_remaining = floor((strtotime($goal['target_date']) - time()) / (60 * 60 * 24));
                            $is_completed = $goal['completed'] || $progress >= 100;
                            ?>
                            <div class="goal-card <?php echo $is_completed ? 'completed' : ''; ?>">
                                <div class="goal-header">
                                    <h3><?php echo htmlspecialchars($goal['goal_name']); ?></h3>
                                    <div class="goal-actions">
                                        <button class="action-btn add-money"
                                            onclick="openProgressModal(<?php echo $goal['id']; ?>, '<?php echo htmlspecialchars($goal['goal_name']); ?>')"
                                            <?php echo $is_completed ? 'disabled' : ''; ?>>
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        <a href="?delete_id=<?php echo $goal['id']; ?>" class="action-btn delete"
                                            onclick="return confirm('Bạn có chắc muốn xóa mục tiêu này?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>

                                <div class="goal-description">
                                    <?php echo htmlspecialchars($goal['description'] ?: 'Không có mô tả'); ?>
                                </div>

                                <div class="goal-progress">
                                    <div class="progress-info">
                                        <span
                                            class="current-amount"><?php echo number_format($goal['current_amount'], 0, ',', '.'); ?>₫</span>
                                        <span class="target-amount">/
                                            <?php echo number_format($goal['target_amount'], 0, ',', '.'); ?>₫</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                    </div>
                                    <div class="progress-stats">
                                        <span class="progress-percent"><?php echo number_format($progress, 1); ?>%</span>
                                        <?php if (!$is_completed): ?>
                                            <span class="days-left">Còn <?php echo max(0, $days_remaining); ?> ngày</span>
                                        <?php else: ?>
                                            <span class="completed-badge">Đã hoàn thành</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="goal-footer">
                                    <div class="goal-date">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('d/m/Y', strtotime($goal['target_date'])); ?>
                                    </div>
                                    <?php if ($is_completed): ?>
                                        <div class="completion-date">
                                            <i class="fas fa-check"></i>
                                            Hoàn thành: <?php echo date('d/m/Y', strtotime($goal['updated_at'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-bullseye"></i>
                        <h3>Chưa có mục tiêu nào</h3>
                        <p>Bắt đầu tiết kiệm bằng cách tạo mục tiêu đầu tiên của bạn.</p>
                        <button class="button" onclick="openModal()">
                            <i class="fas fa-plus"></i> Tạo mục tiêu đầu tiên
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Modal thêm mục tiêu mới -->
    <div id="goalModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Thêm mục tiêu mới</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" class="modal-form">
                <input type="hidden" name="add_goal" value="1">

                <div class="form-group">
                    <label for="goal_name">Tên mục tiêu *</label>
                    <input type="text" id="goal_name" name="goal_name" required
                        placeholder="Ví dụ: Mua laptop mới, Du lịch Đà Lạt...">
                </div>

                <div class="form-group">
                    <label for="target_amount">Số tiền mục tiêu *</label>
                    <input type="number" id="target_amount" name="target_amount" required min="1000" step="1000"
                        placeholder="Ví dụ: 10000000">
                </div>

                <div class="form-group">
                    <label for="target_date">Ngày hoàn thành mục tiêu *</label>
                    <input type="date" id="target_date" name="target_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label for="description">Mô tả</label>
                    <textarea id="description" name="description" rows="3"
                        placeholder="Mô tả chi tiết về mục tiêu của bạn..."></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="button secondary" onclick="closeModal()">Hủy</button>
                    <button type="submit" class="button">Thêm mục tiêu</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal thêm tiền vào mục tiêu -->
    <div id="progressModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Thêm tiền vào mục tiêu</h2>
                <button class="close-btn" onclick="closeProgressModal()">&times;</button>
            </div>
            <form method="POST" class="modal-form">
                <input type="hidden" name="update_progress" value="1">
                <input type="hidden" id="progress_goal_id" name="goal_id">

                <div class="form-group">
                    <label id="progress_goal_name">Mục tiêu: </label>
                </div>

                <div class="form-group">
                    <label for="amount_to_add">Số tiền thêm vào *</label>
                    <input type="number" id="amount_to_add" name="amount_to_add" required min="1000" step="1000"
                        placeholder="Nhập số tiền bạn muốn thêm">
                </div>

                <div class="form-actions">
                    <button type="button" class="button secondary" onclick="closeProgressModal()">Hủy</button>
                    <button type="submit" class="button">Thêm tiền</button>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Quản Lý Chi Tiêu</h3>
                <p>Ứng dụng giúp bạn quản lý tài chính cá nhân một cách hiệu quả và thông minh.</p>
            </div>
            <div class="footer-section">
                <h3>Liên kết nhanh</h3>
                <ul>
                    <li><a href="../../dashboard.php">Trang chủ</a></li>
                    <li><a href="../user/transactions.php">Chi tiêu</a></li>
                    <li><a href="../chart/index.php">Biểu đồ</a></li>
                    <li><a href="index.php">Mục tiêu</a></li>
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
        // Modal functions
        function openModal() {
            document.getElementById('goalModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('goalModal').style.display = 'none';
        }

        function openProgressModal(goalId, goalName) {
            document.getElementById('progress_goal_id').value = goalId;
            document.getElementById('progress_goal_name').textContent = 'Mục tiêu: ' + goalName;
            document.getElementById('progressModal').style.display = 'flex';
        }

        function closeProgressModal() {
            document.getElementById('progressModal').style.display = 'none';
        }

        // Đóng modal khi click bên ngoài
        window.onclick = function (event) {
            const modals = document.getElementsByClassName('modal');
            for (let modal of modals) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            }
        }

        // Đặt ngày mặc định cho input date
        document.getElementById('target_date').valueAsDate = new Date(new Date().setMonth(new Date().getMonth() + 3));
    </script>

</body>

</html>