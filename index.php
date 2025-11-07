<?php
session_start();
session_destroy(); // ← luôn xóa session khi vào trang index
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quản Lý Chi Tiêu - Trang Giới Thiệu</title>
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>

<body>

<!-- HEADER -->
<header>
    <div class="logo">💰 Quản Lý Chi Tiêu</div>
    <nav>
        <a href="index.php">Trang chủ</a>
        <a href="#">Tính năng</a>
        <a href="#">Về chúng tôi</a>
        <a href="view/auth/login.php" class="btn-login">Đăng nhập</a>
        <a href="view/auth/register.php" class="btn-register">Đăng ký</a>
    </nav>
</header>

<!-- HERO -->
<section class="hero">
    <div class="hero-content">
        <h1>Quản lý tài chính dễ dàng và thông minh</h1>
        <p>Theo dõi chi tiêu, lập ngân sách và đạt mục tiêu tiết kiệm một cách hiệu quả.</p>
        <a href="view/auth/login.php" class="cta-btn">Bắt đầu ngay</a>
    </div>
</section>

<!-- FEATURES -->
<section class="features">
    <div class="feature">
        <i class="fas fa-wallet"></i>
        <h3>Ghi nhận chi tiêu</h3>
        <p>Ghi nhanh mọi khoản chi để kiểm soát tốt hơn.</p>
    </div>

    <div class="feature">
        <i class="fas fa-chart-pie"></i>
        <h3>Biểu đồ phân tích</h3>
        <p>Xem báo cáo theo danh mục trực quan dễ hiểu.</p>
    </div>

    <div class="feature">
        <i class="fas fa-bullseye"></i>
        <h3>Mục tiêu tiết kiệm</h3>
        <p>Đặt mục tiêu tài chính và theo dõi tiến độ.</p>
    </div>
</section>


<!-- FOOTER -->
<footer>
    <p>© 2025 Quản Lý Chi Tiêu. Designed with ca</p>
</footer>

</body>
</html>
