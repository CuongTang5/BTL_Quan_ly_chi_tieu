<?php
// handle/delete_multi_user_process.php

require_once __DIR__ . '/../function/db_connection.php';
$conn = getDbConnection();

if (!isset($_POST['ids']) || count($_POST['ids']) == 0) {
    header("Location: /Quanlychitieu/view/user/admin.php?page=users&error=no_selection");
    exit;
}

$ids = $_POST['ids'];
$idStr = implode(",", array_map("intval", $ids));

$conn->query("DELETE FROM users WHERE id IN ($idStr)");

header("Location: /Quanlychitieu/view/user/admin.php?page=users&success=multi_deleted");
exit;
