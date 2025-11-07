<?php
require "../../function/db_connection.php";
$conn = getDbConnection();

$id = $_GET['id'] ?? '';
if ($id !== '') {
    $stmt = $conn->prepare("DELETE FROM categories WHERE id_categories = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}
header("Location: ../../view/user/admin.php?page=categories");
exit();
