<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/Quanlychitieu/function/db_connection.php";
$conn = getDbConnection();
?>

<h2>Thêm danh mục chi tiêu</h2>

<form method="POST" action="/Quanlychitieu/handle/add_category_process.php">
    <div>
        <label for="name">Tên danh mục:</label>
        <input type="text" id="name" name="name" required>
    </div>

    <div>
        <label for="description">Mô tả:</label>
        <textarea id="description" name="description"></textarea>
    </div>

    <div style="margin-top:10px;">
        <button type="submit" class="btn add-btn">➕ Thêm</button>
        <button type="button" class="btn" onclick="window.location.href='../../view/user/admin.php?page=categories'">❌ Hủy</button>
    </div>
</form>
