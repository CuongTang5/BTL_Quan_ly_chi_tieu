<?php
// handle/edit_user_process.php

// Hiển thị lỗi (chỉ dev)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// include file kết nối DB (đi lên 1 cấp từ handle -> function)
require_once __DIR__ . '/../function/db_connection.php';

// lấy kết nối
$conn = getDbConnection();

// Lấy dữ liệu POST (đảm bảo có dữ liệu)
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$role = isset($_POST['role']) ? trim($_POST['role']) : 'user';
$password_raw = $_POST['password'] ?? ''; // có thể rỗng (không đổi)

// Kiểm tra cơ bản
if ($id <= 0 || $username === '') {
    // Quay về trang quản lý với lỗi (đơn giản)
    header("Location: /Quanlychitieu/view/user/admin.php?page=users&error=invalid_input");
    exit;
}

try {
    if ($password_raw !== '') {
        // cập nhật cả mật khẩu (hash)
        $password_hashed = password_hash($password_raw, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $username, $password_hashed, $role, $id);
    } else {
        // cập nhật không đổi mật khẩu
        $sql = "UPDATE users SET username = ?, role = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $username, $role, $id);
    }

    $stmt->execute();
    $stmt->close();

    // redirect về trang quản lý người dùng (admin layout)
    header("Location: /Quanlychitieu/view/user/admin.php?page=users&success=1");
    exit;

} catch (Exception $e) {
    // Trong dev, in lỗi; trong production nên log và hiện thông báo chung
    // echo "Lỗi: " . $e->getMessage();
    header("Location: /Quanlychitieu/view/user/admin.php?page=users&error=update_failed");
    exit;
}
