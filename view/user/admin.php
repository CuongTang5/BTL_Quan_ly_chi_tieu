<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$page = $_GET['page'] ?? 'dashboard_admin';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Quản Lý Chi Tiêu</title>
    <link rel="stylesheet" href="../../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <i class="fas fa-cogs"></i>
                <span>Admin Panel</span>
            </div>
            <ul class="menu">
                <li class="<?php echo $page === 'dashboard_admin' ? 'active' : ''; ?>">
                    <a href="admin.php?page=dashboard_admin">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Tổng quan</span>
                    </a>
                </li>
                <li class="<?php echo $page === 'users' ? 'active' : ''; ?>">
                    <a href="admin.php?page=users">
                        <i class="fas fa-users"></i>
                        <span>Quản lý người dùng</span>
                    </a>
                </li>
                <li class="<?php echo $page === 'categories' ? 'active' : ''; ?>">
                    <a href="admin.php?page=categories">
                        <i class="fas fa-folder"></i>
                        <span>Quản lý danh mục</span>
                    </a>
                </li>
                <li class="<?php echo $page === 'transactions' ? 'active' : ''; ?>">
                    <a href="admin.php?page=transactions">
                        <i class="fas fa-receipt"></i>
                        <span>Giao dịch</span>
                    </a>
                </li>
                <li class="<?php echo $page === 'reports' ? 'active' : ''; ?>">
                    <a href="admin.php?page=reports">
                        <i class="fas fa-chart-bar"></i>
                        <span>Thống kê</span>
                    </a>
                </li>
            </ul>

            <div class="logout">
                <a href="../auth/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Đăng xuất</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <h1>
                    <?php
                    $titles = [
                        'dashboard_admin' => 'Tổng quan hệ thống',
                        'users' => 'Quản lý người dùng',
                        'categories' => 'Quản lý danh mục',
                        'transactions' => 'Quản lý giao dịch',
                        'reports' => 'Thống kê hệ thống'
                    ];
                    echo $titles[$page] ?? 'Admin Panel';
                    ?>
                </h1>
                <div class="user-info">
                    <span>Xin chào, <?php echo $_SESSION['username'] ?? 'Admin'; ?></span>
                    <div class="user-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                </div>
            </div>

            <div class="content-body">
                <?php
                $file = __DIR__ . "/pages/" . $page . ".php";
                if (file_exists($file)) {
                    include $file;
                } else {
                    echo '
                    <div class="error-page">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h2>Trang không tồn tại</h2>
                        <p>Trang "' . htmlspecialchars($page) . '" không được tìm thấy.</p>
                        <a href="admin.php?page=dashboard_admin" class="btn btn-primary">
                            <i class="fas fa-home"></i> Về trang tổng quan
                        </a>
                    </div>';
                }
                ?>
            </div>
        </div>
    </div>

    <script>
    // Xác nhận xóa
    function confirmDelete(message = 'Bạn có chắc muốn xóa mục này?') {
        return confirm(message);
    }

    // Check all functionality
    function toggleCheckAll(source) {
        const checkboxes = document.querySelectorAll('input[name="selected[]"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = source.checked;
        });
        toggleDeleteButton();
    }

    function toggleDeleteButton() {
        const checkboxes = document.querySelectorAll('input[name="selected[]"]:checked');
        const deleteBtn = document.getElementById('deleteSelectedBtn');
        if (deleteBtn) {
            deleteBtn.style.display = checkboxes.length > 0 ? 'inline-block' : 'none';
        }
    }
    </script>
</body>
</html>