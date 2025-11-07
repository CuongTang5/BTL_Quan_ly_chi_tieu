<?php session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký | Quản Lý Chi Tiêu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/login.css">
</head>
<body>

<div class="login-container">

    <div class="image-section">
        <div class="image-content">
            <h1>Tạo Tài Khoản Mới</h1>
            <p>Bắt đầu theo dõi chi tiêu và tối ưu hóa tài chính ngay hôm nay!</p>
        </div>
    </div>

    <div class="form-section">

        <div class="logo">
            <i class="fas fa-wallet"></i>
            <span>Quản Lý Chi Tiêu</span>
        </div>

        <div class="form-header">
            <h2>Đăng Ký</h2>
            <p>Điền thông tin bên dưới để tạo tài khoản.</p>
        </div>

        <?php if(isset($_GET['error'])): ?>
            <div class="alert error show">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $_GET['error']; ?></span>
            </div>
        <?php endif; ?>

        <form action="../../handle/register_process.php" method="POST" class="login-form">

            <div class="input-group">
                <div class="input-icon"><i class="fas fa-user"></i></div>
                <input type="text" name="username" placeholder="Tên đăng nhập" required>
            </div>

            <div class="input-group">
                <div class="input-icon"><i class="fas fa-lock"></i></div>
                <input type="password" name="password" placeholder="Mật khẩu" required>
            </div>

            <div class="input-group">
                <div class="input-icon"><i class="fas fa-lock"></i></div>
                <input type="password" name="confirm_password" placeholder="Nhập lại mật khẩu" required>
            </div>

            <button type="submit" class="login-btn">
                <span>Đăng Ký</span>
                <span class="btn-icon"><i class="fas fa-user-plus"></i></span>
            </button>

            <p class="account-text">Đã có tài khoản? 
                <a href="login.php">Đăng nhập</a>
            </p>

        </form>
    </div>
</div>

</body>
</html>
