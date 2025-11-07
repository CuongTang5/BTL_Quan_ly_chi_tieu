<?php session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập | Quản Lý Chi Tiêu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/login.css?v=<?php echo time(); ?>">

</head>
<body>

    <div class="login-container">
        <div class="image-section">
            <div class="image-content">
                <h1>Quản Lý Tài Chính Thông Minh</h1>
                <p>Theo dõi chi tiêu, lập ngân sách và đạt được mục tiêu tài chính.</p>

                <div class="features">
                    <div class="feature">
                        <i class="fas fa-chart-line"></i>
                        <span>Theo dõi chi tiêu theo danh mục</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-bullseye"></i>
                        <span>Thiết lập mục tiêu tiết kiệm</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span>Báo cáo tài chính chi tiết</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <div class="logo">
                <i class="fas fa-chart-pie"></i>
                <span>Quản Lý Chi Tiêu</span>
            </div>
            
            <div class="form-header">
                <h2>Đăng Nhập</h2>
                <p>Chào mừng trở lại! Vui lòng đăng nhập để tiếp tục.</p>
            </div>

            <?php if(isset($_GET['error'])): ?>
            <div class="alert error show" id="errorAlert">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $_GET['error']; ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="../../handle/login_process.php" class="login-form" id="loginForm">
                <div class="input-group">
                    <div class="input-icon"><i class="fas fa-user"></i></div>
                    <input type="text" name="username" placeholder="Tên đăng nhập" required>
                </div>
                
                <div class="input-group">
                    <div class="input-icon"><i class="fas fa-lock"></i></div>
                    <input type="password" name="password" placeholder="Mật khẩu" required id="password">
                    <button type="button" class="toggle-password" id="togglePassword"><i class="fas fa-eye"></i></button>
                </div>
                
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember"><span class="checkmark"></span> Ghi nhớ đăng nhập
                    </label>
                    <a href="#" class="forgot-password">Quên mật khẩu?</a>
                </div>
                
                <button type="submit" class="login-btn">
                    <span>Đăng Nhập</span>
                    <span class="btn-icon"><i class="fas fa-arrow-right"></i></span>
                </button>
                <p class="account-text">Chưa có tài khoản? 
<a href="register.php">Đăng ký</a>
</p>

            </form>
        </div>
    </div>

<script>
document.getElementById('togglePassword').addEventListener('click', function(){
    const passwordInput = document.getElementById('password');
    const icon = this.querySelector('i');
    passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
    icon.classList.toggle('fa-eye');
    icon.classList.toggle('fa-eye-slash');
});
</script>

</body>
</html>
