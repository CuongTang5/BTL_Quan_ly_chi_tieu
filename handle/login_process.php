<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require "../function/db_connection.php"; // ✅ gọi file kết nối DB

$conn = getDbConnection(); // ✅ lấy kết nối

// Lấy dữ liệu từ form
$username = trim($_POST['username']);
$password = trim($_POST['password']);

// ✅ Truy vấn lấy user theo username
$sql = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // ✅ Kiểm tra mật khẩu (không hash)
    if ($password === $user['password']) {

        // Lưu session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // ✅ Phân quyền chuyển hướng
        if ($user['role'] === 'admin') {
            header("Location: ../view/user/admin.php"); // 👉 Trang quản trị
        } else {
            header("Location: ../dashboard.php"); // 👉 Trang người dùng
        }
        exit();
    }
}

// ❌ Sai → quay lại login và báo lỗi
header("Location: ../view/auth/login.php?error=Sai tên đăng nhập hoặc mật khẩu");
exit();
