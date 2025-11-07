<?php
session_start();
require "../../function/db_connection.php";
$conn = getDbConnection();

$id = $_POST['id'];
$role = $_POST['role'];

// Không cho admin tự hạ quyền chính mình
if($id == $_SESSION['user_id']){
    header("Location: admin.php?page=user_list");
    exit;
}

$stmt = $conn->prepare("UPDATE users SET role=? WHERE id=?");
$stmt->bind_param("si", $role, $id);
$stmt->execute();

header("Location: admin.php?page=user_list");
exit();
