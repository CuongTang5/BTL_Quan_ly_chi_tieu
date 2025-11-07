<?php
require "../../function/db_connection.php";
$conn = getDbConnection();

$ids = $_POST['ids'] ?? [];

if (!empty($ids)) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $stmt = $conn->prepare("DELETE FROM categories WHERE id_categories IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
}

header("Location: ../../view/user/admin.php?page=categories");
exit();
