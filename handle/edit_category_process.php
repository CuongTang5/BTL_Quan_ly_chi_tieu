<?php
require "../../function/db_connection.php";
$conn = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';

    if ($id !== '' && $name !== '') {
        $stmt = $conn->prepare("UPDATE categories SET name_categories = ?, description = ? WHERE id_categories = ?");
        $stmt->bind_param("ssi", $name, $description, $id);
        $stmt->execute();
    }

    header("Location: ../../view/user/admin.php?page=categories");
    exit();
}
