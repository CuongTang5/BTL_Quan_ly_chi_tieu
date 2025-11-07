<?php
// Đường dẫn đúng để quay về trang danh sách người dùng
$backUrl = "../admin.php?page=users";
?>

<h2>Thêm người dùng</h2>

<form action="../../../handle/add_user_process.php" method="POST" class="form-box">

    <label>Tên tài khoản:</label>
    <input type="text" name="username" required class="input-text">

    <label>Mật khẩu:</label>
    <input type="password" name="password" required class="input-text">

    <label>Vai trò:</label>
    <select name="role" class="input-text">
        <option value="user">Thành viên</option>
        <option value="admin">Quản trị viên</option>
    </select>

    <div class="form-actions">
        <button type="submit" class="btn btn-add">Thêm</button>
        <a href="<?= $backUrl ?>" class="btn btn-cancel">Hủy</a>
    </div>

</form>
