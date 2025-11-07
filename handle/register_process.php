<?php
session_start();
require_once "../function/db_connection.php";

$conn = getDbConnection();

// Nhận dữ liệu
$username = $_POST['username'];
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];

// Kiểm tra mật khẩu khớp
if ($password !== $confirm_password) {
    header("Location: ../view/auth/register.php?error=Mật khẩu không khớp");
    exit;
}

// Kiểm tra trùng username
$sql_check = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    header("Location: ../view/auth/register.php?error=Tên đăng nhập đã tồn tại");
    exit;
}

// ✅ Không hash → lưu mật khẩu thường
$sql_insert = "INSERT INTO users (username, password) VALUES (?, ?)";
$stmt = $conn->prepare($sql_insert);
$stmt->bind_param("ss", $username, $password);

if ($stmt->execute()) {
    header("Location: ../view/auth/login.php?success=Đăng ký thành công! Vui lòng đăng nhập");
    exit;
} else {
    header("Location: ../view/auth/register.php?error=Lỗi hệ thống, thử lại sau");
    exit;
}
