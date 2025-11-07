<?php
require $_SERVER['DOCUMENT_ROOT'] . '/Quanlychitieu/function/db_connection.php';
$conn = getDbConnection(); // ✅ THÊM DÒNG NÀY

if (!isset($_GET['id'])) {
    header("Location: admin.php?page=users");
    exit;
}

$id = intval($_GET['id']); // tránh injection

$sql = "SELECT * FROM users WHERE id = $id LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "<p>Không tìm thấy người dùng!</p>";
    exit;
}

$user = $result->fetch_assoc();
?>

<h2>Sửa người dùng</h2>

<form action="/Quanlychitieu/handle/edit_user_process.php" method="POST" style="max-width:400px;">

    <input type="hidden" name="id" value="<?= $user['id'] ?>">

    <label>Tên tài khoản:</label><br>
    <input type="text" name="username" class="input-text" value="<?= $user['username'] ?>" required><br><br>

    <label>Mật khẩu mới (để trống nếu không đổi):</label><br>
    <input type="password" name="password" class="input-text"><br><br>

    <label>Vai trò:</label><br>
    <select name="role" class="input-text">
        <option value="user" <?= $user['role']=='user' ? 'selected' : '' ?>>Thành viên</option>
        <option value="admin" <?= $user['role']=='admin' ? 'selected' : '' ?>>Quản trị viên</option>
    </select><br><br>

    <button type="submit" class="btn btn-add">Lưu</button>
    <a href="admin.php?page=users" class="btn cancel-btn">Hủy</a>

</form>
