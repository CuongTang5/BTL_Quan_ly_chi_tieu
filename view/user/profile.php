<?php
session_start();

// Tạo CSRF token nếu chưa có
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Dummy dữ liệu mẫu — sẽ thay bằng DB nếu có
$success = $success ?? '';
$error = $error ?? '';
$userData = $userData ?? ['username' => 'user', 'role' => 'Thành viên'];

$username = htmlspecialchars($userData['username'], ENT_QUOTES, 'UTF-8');
$role = htmlspecialchars($userData['role'], ENT_QUOTES, 'UTF-8');
$avatarLetter = mb_strtoupper(mb_substr($userData['username'], 0, 1, 'UTF-8'));
$csrfToken = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ sơ người dùng</title>

    <!-- ✅ Đúng đường dẫn CSS -->
    <link rel="stylesheet" href="../../css/profile.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>Hồ sơ người dùng</h2>
            <p>Quản lý thông tin và bảo mật tài khoản</p>
        </div>

        <div class="content">

            <?php if ($success): ?>
                <div class="message success"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="message error"><?= $error ?></div>
            <?php endif; ?>

            <div class="user-info">
                <div class="avatar"><?= $avatarLetter ?></div>
                <div class="user-details">
                    <p><strong>Tài khoản:</strong> <span><?= $username ?></span></p>
                    <p><strong>Vai trò:</strong> <span><?= $role ?></span> <span class="role-badge"><?= $role ?></span></p>
                </div>
            </div>

            <div class="form-section">
                <h3>Đổi mật khẩu</h3>

                <form action="../../controller/update_password.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                    <div class="form-group">
                        <label>Mật khẩu hiện tại</label>
                        <input type="password" name="current_pass" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Mật khẩu mới</label>
                        <input type="password" name="new_pass" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Xác nhận mật khẩu</label>
                        <input type="password" name="confirm_pass" class="form-control" required>
                    </div>

                    <label class="toggle-pass">
                        <input type="checkbox" id="showPass"> Hiển thị mật khẩu
                    </label>

                    <button type="submit" class="btn">Cập nhật mật khẩu</button>
                </form>
            </div>

            <div class="logout">
                <a href="../../logout.php">Đăng xuất</a>
            </div>
        </div>
    </div>

</body>
</html>
