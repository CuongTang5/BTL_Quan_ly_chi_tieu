<?php
// Kiểm tra session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Lấy các biến hiển thị
$page_title = isset($page_title) ? $page_title : 'Quản Lý Chi Tiêu';
$active_page = isset($active_page) ? $active_page : '';
$search_keyword = isset($_GET['search']) ? $_GET['search'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'User';

// Lấy chữ cái đầu của tên để làm Avatar
$avatar_char = strtoupper(mb_substr($username, 0, 1, 'UTF-8'));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <link rel="stylesheet" href="/Quanlychitieu/css/dashboard.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* --- CSS CƠ BẢN & SEARCH --- */
        mark.highlight-text { background-color: #ffeeba; color: #856404; padding: 0 2px; border-radius: 2px; font-weight: bold; }
        .pagination-container { display: flex; justify-content: center; margin-top: 20px; gap: 5px; }
        .pagination-link { display: inline-block; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333; transition: all 0.3s; background: white; }
        .pagination-link:hover { background-color: #f0f0f0; border-color: #bbb; }
        .pagination-link.active { background-color: #007bff; color: white; border-color: #007bff; }
        .pagination-link.disabled { color: #ccc; pointer-events: none; border-color: #eee; }

        .header-search { position: relative; width: 100%; max-width: 450px; margin: 0 15px; }
        .header-search input {
            width: 100%; height: 42px; padding: 0 50px 0 20px;
            border-radius: 25px; background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2); color: white; font-size: 14px; outline: none;
            transition: all 0.3s ease; backdrop-filter: blur(4px);
        }
        .header-search input::placeholder { color: rgba(255, 255, 255, 0.75); }
        .header-search input:focus { background: rgba(255, 255, 255, 0.25); border-color: rgba(255, 255, 255, 0.5); box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.1); }
        .header-search button {
            position: absolute; right: 5px; top: 50%; transform: translateY(-50%);
            width: 34px; height: 34px; border-radius: 50%; background: transparent;
            border: none; color: white; font-size: 16px; cursor: pointer;
            display: flex; align-items: center; justify-content: center; transition: background 0.2s;
        }
        .header-search button:hover { background: rgba(255, 255, 255, 0.2); }
        .clear-search { position: absolute; right: 45px; top: 50%; transform: translateY(-50%); color: rgba(255, 255, 255, 0.6); font-size: 14px; cursor: pointer; padding: 5px; }
        .clear-search:hover { color: white; }

        /* --- CSS CHO PHẦN BÊN PHẢI (MENU & ACTIONS) --- */
        
        /* Canh chỉnh thanh menu */
        .right nav { display: flex; align-items: center; gap: 15px; }

        /* 1. Quả chuông */
        .btn-bell {
            position: relative; color: white; font-size: 20px; margin-left: 10px;
            cursor: pointer; transition: transform 0.2s; display: flex; align-items: center;
        }
        .btn-bell:hover { transform: scale(1.1); }
        
        .badge {
            position: absolute; top: -6px; right: -6px;
            background: #e53e3e; color: white; font-size: 10px; font-weight: bold;
            width: 16px; height: 16px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            border: 2px solid #2f855a;
        }

        /* 2. Avatar Tròn (Thay thế ảnh mặc định) */
        .btn-avatar {
            width: 38px; height: 38px; border-radius: 50%;
            background: white; color: #2f855a;
            font-weight: 700; font-size: 16px;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; /* Bỏ gạch chân */
            border: 2px solid rgba(255,255,255,0.4);
            transition: all 0.2s;
        }
        .btn-avatar:hover {
            background: #f0fdf4; transform: scale(1.05); border-color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-search { max-width: 100%; margin: 10px 0; }
            .header-search input { background: rgba(255, 255, 255, 0.2); }
            .right nav { gap: 10px; font-size: 13px; }
            .btn-bell, .btn-avatar { width: 32px; height: 32px; font-size: 14px; }
        }
    </style>
</head>

<body>

<header class="main-header">
    <div class="left">
        <a href="/Quanlychitieu/dashboard.php" style="text-decoration:none; color:white;">
            <div class="logo">💰 Quản Lý Chi Tiêu</div>
        </a>
    </div>

    <div class="center">
        <form method="GET" action="/Quanlychitieu/dashboard.php" class="header-search">
            <input 
                type="text" 
                name="search"
                value="<?php echo htmlspecialchars($search_keyword); ?>" 
                placeholder="Tìm giao dịch, danh mục..."
            >
            <button type="submit"><i class="fas fa-search"></i></button>

            <?php if (!empty($search_keyword)): ?>
                <a href="/Quanlychitieu/dashboard.php" class="clear-search"><i class="fas fa-times"></i></a>
            <?php endif; ?>
        </form>
    </div>

    <div class="right">
        <nav>
            <a href="/Quanlychitieu/dashboard.php" class="<?php echo ($active_page == 'dashboard') ? 'active' : ''; ?>">Trang Chủ</a>
            <a href="/Quanlychitieu/view/user/transactions.php" class="<?php echo ($active_page == 'transactions') ? 'active' : ''; ?>">Chi tiêu</a>
            <a href="/Quanlychitieu/view/chart.php" class="<?php echo ($active_page == 'chart') ? 'active' : ''; ?>">Biểu đồ</a>
            <a href="/Quanlychitieu/view/user/pages/goal.php" class="<?php echo ($active_page == 'goal') ? 'active' : ''; ?>">Mục tiêu</a>
            <a href="/Quanlychitieu/view/user/pages/investments.php" class="<?php echo ($active_page == 'investments') ? 'active' : ''; ?>">Đầu tư</a>
            
            <!-- 1. Quả chuông (Thay thế nút Đăng xuất cũ) -->
            <div class="btn-bell" title="Thông báo" onclick="alert('Bạn có 3 thông báo mới!')">
                <i class="fas fa-bell"></i>
                <span class="badge">3</span>
            </div>

            <!-- 2. Avatar (Link thẳng sang Profile) -->
            <a href="/Quanlychitieu/view/user/profile.php" class="btn-avatar" title="Trang cá nhân">
                <?php echo $avatar_char; ?>
            </a>

        </nav>
    </div>
</header>