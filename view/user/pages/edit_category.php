<?php
require "../../function/db_connection.php";
$conn = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';

    if ($name !== '') {
        $stmt = $conn->prepare("INSERT INTO categories (name_categories, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        $stmt->execute();
        header("Location: ../../view/user/admin.php?page=categories");
        exit();
    }
}
?>

<h2>Thêm danh mục chi tiêu</h2>
<form method="POST">
    <label>Tên danh mục:</label><br>
    <input type="text" name="name" class="input-text" required><br>
    <label>Mô tả:</label><br>
    <textarea name="description" class="input-text"></textarea><br><br>
    <button type="submit" class="btn add-btn">Thêm</button>
</form>
