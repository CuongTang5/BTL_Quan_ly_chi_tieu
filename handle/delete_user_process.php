<?php
// handle/delete_user_process.php

require_once __DIR__ . '/../function/db_connection.php';
$conn = getDbConnection();

if (!isset($_GET['id']) || intval($_GET['id']) <= 0) {
    header("Location: /Quanlychitieu/view/user/admin.php?page=users&error=invalid_id");
    exit;
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("Location: /Quanlychitieu/view/user/admin.php?page=users&success=deleted");
exit;
