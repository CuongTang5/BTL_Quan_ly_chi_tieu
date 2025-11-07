<?php
require "../function/db_connection.php"; // ✅ đúng đường dẫn
$conn = getDbConnection();

$username = trim($_POST['username']);
$password = trim($_POST['password']);
$role = $_POST['role'];

// Thêm vào DB
$sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $username, $password, $role);
$stmt->execute();

// Quay về trang quản lý người dùng
header("Location: ../view/user/admin.php?page=users");
exit();
