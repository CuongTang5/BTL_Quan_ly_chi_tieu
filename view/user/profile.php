<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/Quanlychitieu/function/db_connection.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: /Quanlychitieu/view/auth/login.php");
    exit();
}

$conn = getDbConnection();
$user_id = $_SESSION['user_id'];
$message = "";
$msg_type = "";
$current_tab = 'info'; // Tab mặc định hiển thị

// --- 1. XỬ LÝ ĐỔI MẬT KHẨU ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_tab = 'security'; // Nếu submit form, giữ lại tab bảo mật
    
    $current_pass = $_POST['current_pass'];
    $new_pass = $_POST['new_pass'];
    $confirm_pass = $_POST['confirm_pass'];

    if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        $message = "Vui lòng điền đầy đủ thông tin!";
        $msg_type = "error";
    } elseif ($new_pass !== $confirm_pass) {
        $message = "Mật khẩu mới không khớp!";
        $msg_type = "error";
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (password_verify($current_pass, $user['password'])) {
            $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($update_stmt->execute()) {
                $message = "Đổi mật khẩu thành công!";
                $msg_type = "success";
            } else {
                $message = "Lỗi hệ thống.";
                $msg_type = "error";
            }
        } else {
            $message = "Mật khẩu hiện tại không đúng!";
            $msg_type = "error";
        }
    }
}

// --- 2. LẤY THÔNG TIN USER ---
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_info = $stmt->get_result()->fetch_assoc();
    $username = htmlspecialchars($user_info['username']);
    $avatar_letter = strtoupper(substr($username, 0, 1));
}
$conn->close();

$page_title = 'Cài đặt tài khoản';
include $_SERVER['DOCUMENT_ROOT'] . '/Quanlychitieu/includes/header.php';
?>

<style>
    /* Layout chính dạng Grid 2 cột */
    .settings-container {
        display: grid;
        grid-template-columns: 280px 1fr; /* Menu trái 280px, Nội dung phải tự co giãn */
        gap: 30px;
        min-height: 500px;
        align-items: start;
    }

    /* --- 1. SIDEBAR (MENU TRÁI) --- */
    .settings-sidebar {
        background: white; border-radius: 16px; overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #edf2f7;
    }
    
    /* Phần user nhỏ trên menu */
    .mini-profile {
        padding: 25px; text-align: center; border-bottom: 1px solid #edf2f7; background: #fcfcfc;
    }
    .mini-avatar {
        width: 70px; height: 70px; margin: 0 auto 10px;
        background: linear-gradient(135deg, #2f855a 0%, #48bb78 100%);
        color: white; font-size: 28px; font-weight: 700;
        display: flex; align-items: center; justify-content: center; border-radius: 50%;
    }
    .mini-name { font-weight: 700; color: #2d3748; font-size: 16px; }
    .mini-role { font-size: 12px; color: #718096; background: #edf2f7; padding: 2px 8px; border-radius: 10px; display: inline-block; margin-top: 5px; }

    /* Danh sách menu */
    .menu-list { list-style: none; padding: 10px; margin: 0; }
    .menu-item {
        padding: 12px 15px; margin-bottom: 5px; border-radius: 8px; cursor: pointer;
        color: #4a5568; font-weight: 500; transition: all 0.2s; display: flex; align-items: center; gap: 10px;
    }
    .menu-item:hover { background: #f7fafc; color: #2d3748; }
    
    /* Trạng thái đang chọn */
    .menu-item.active {
        background: #e6fffa; color: #2f855a; font-weight: 600;
    }
    .menu-item.logout { color: #e53e3e; margin-top: 10px; border-top: 1px solid #edf2f7; border-radius: 0; }
    .menu-item.logout:hover { background: #fff5f5; }

    /* --- 2. CONTENT (NỘI DUNG PHẢI) --- */
    .settings-content {
        background: white; border-radius: 16px; padding: 30px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #edf2f7;
        min-height: 400px;
    }
    
    /* Tiêu đề của từng tab */
    .tab-header { margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #edf2f7; }
    .tab-header h2 { margin: 0; font-size: 20px; color: #2d3748; }
    .tab-header p { margin: 5px 0 0; color: #718096; font-size: 14px; }

    /* Các Tab nội dung (Mặc định ẩn hết) */
    .tab-pane { display: none; animation: fadeIn 0.3s ease; }
    .tab-pane.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

    /* Form Styles */
    .form-group { margin-bottom: 20px; max-width: 500px; }
    .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #4a5568; font-size: 14px; }
    .form-input {
        width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px;
        outline: none; transition: 0.2s; font-size: 14px;
    }
    .form-input:focus { border-color: #2f855a; box-shadow: 0 0 0 3px rgba(47, 133, 90, 0.1); }
    .form-input[readonly] { background: #f7fafc; color: #718096; cursor: not-allowed; }

    .btn-save {
        background: #2f855a; color: white; border: none; padding: 12px 24px;
        border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s;
    }
    .btn-save:hover { background: #276749; }

    /* Alert */
    .alert-box { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; display: flex; gap: 10px; align-items: center; }
    .alert-success { background: #f0fff4; color: #276749; border: 1px solid #c6f6d5; }
    .alert-error { background: #fff5f5; color: #c53030; border: 1px solid #fed7d7; }

    @media (max-width: 768px) { 
        .settings-container { grid-template-columns: 1fr; } 
        .settings-sidebar { margin-bottom: 20px; }
    }
</style>

<section class="profile-page">
    <div class="container">
        
        <div class="page-header" style="margin-bottom: 30px;">
            <h1 class="page-title">Cài đặt tài khoản</h1>
        </div>

        <div class="settings-container">
            
            <aside class="settings-sidebar">
                <div class="mini-profile">
                    <div class="mini-avatar"><?php echo $avatar_letter; ?></div>
                    <div class="mini-name"><?php echo $username; ?></div>
                    <div class="mini-role">Thành viên</div>
                </div>
                <ul class="menu-list">
                    <li class="menu-item <?php echo $current_tab == 'info' ? 'active' : ''; ?>" onclick="switchTab('info', this)">
                        <i class="far fa-user-circle"></i> Thông tin chung
                    </li>
                    <li class="menu-item <?php echo $current_tab == 'security' ? 'active' : ''; ?>" onclick="switchTab('security', this)">
                        <i class="fas fa-lock"></i> Mật khẩu & Bảo mật
                    </li>
                    <a href="/Quanlychitieu/view/auth/logout.php" style="text-decoration: none;">
                        <li class="menu-item logout">
                            <i class="fas fa-sign-out-alt"></i> Đăng xuất
                        </li>
                    </a>
                </ul>
            </aside>

            <main class="settings-content">
                
                <div id="tab-info" class="tab-pane <?php echo $current_tab == 'info' ? 'active' : ''; ?>">
                    <div class="tab-header">
                        <h2>Thông tin chung</h2>
                        <p>Xem và quản lý thông tin cơ bản của bạn.</p>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Tên tài khoản</label>
                        <input type="text" class="form-input" value="<?php echo $username; ?>" readonly>
                        <small style="color: #a0aec0; font-style: italic;">Không thể thay đổi tên đăng nhập.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Vai trò</label>
                        <input type="text" class="form-input" value="Thành viên chính thức" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Trạng thái</label>
                        <span style="color: #38a169; font-weight: 600;"><i class="fas fa-check-circle"></i> Đang hoạt động</span>
                    </div>
                </div>

                <div id="tab-security" class="tab-pane <?php echo $current_tab == 'security' ? 'active' : ''; ?>">
                    <div class="tab-header">
                        <h2>Đổi mật khẩu</h2>
                        <p>Vui lòng sử dụng mật khẩu mạnh để bảo vệ tài khoản.</p>
                    </div>

                    <?php if (!empty($message)): ?>
                        <div class="alert-box alert-<?php echo $msg_type; ?>">
                            <i class="fas fa-<?php echo $msg_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="change_password" value="1">

                        <div class="form-group">
                            <label class="form-label">Mật khẩu hiện tại</label>
                            <input type="password" name="current_pass" class="form-input" placeholder="••••••••" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Mật khẩu mới</label>
                            <input type="password" name="new_pass" class="form-input" placeholder="Nhập mật khẩu mới" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nhập lại mật khẩu mới</label>
                            <input type="password" name="confirm_pass" class="form-input" placeholder="Xác nhận lại" required>
                        </div>

                        <button type="submit" class="btn-save">Lưu thay đổi</button>
                    </form>
                </div>

            </main>

        </div>
    </div>
</section>

<script>
    function switchTab(tabName, element) {
        // 1. Ẩn tất cả các tab content
        document.querySelectorAll('.tab-pane').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // 2. Bỏ active ở tất cả menu item
        document.querySelectorAll('.menu-item').forEach(item => {
            item.classList.remove('active');
        });

        // 3. Hiện tab được chọn
        document.getElementById('tab-' + tabName).classList.add('active');
        
        // 4. Active menu item được click
        element.classList.add('active');
    }
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/Quanlychitieu/includes/footer.php'; ?>