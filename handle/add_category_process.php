<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/Quanlychitieu/function/db_connection.php";
$conn = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';

    if ($name !== '') {
        $stmt = $conn->prepare("INSERT INTO categories (name_categories, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        $stmt->execute();
    }

    header("Location: /Quanlychitieu/view/user/admin.php?page=categories");
    exit();
}
