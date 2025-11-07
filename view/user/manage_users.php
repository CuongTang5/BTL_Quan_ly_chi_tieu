<?php
session_start();
require "../../function/db_connection.php";

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$conn = getDbConnection();
$result = $conn->query("SELECT id, username, role FROM users ORDER BY id ASC");
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Quản lý người dùng</title>
    <link rel="stylesheet" href="../../css/admin.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }

        th {
            background: #4CAF50;
            color: white;
        }

        .btn-danger {
            padding: 6px 12px;
            background: #e74c3c;
            color: white;
            border-radius: 6px;
            text-decoration: none;
        }

        .btn-edit {
            padding: 6px 12px;
            background: #3498db;
            color: white;
            border-radius: 6px;
            text-decoration: none;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <h3>Quản trị</h3>
        <a href="admin.php">Bảng điều khiển</a>
        <a href="manage_users.php" class="active">Quản lý người dùng</a>
        <a href="../auth/logout.php">Đăng xuất</a>
    </div>

    <div class="main">
        <h2>Danh sách người dùng</h2>

        <a href="add_user.php" class="btn-add">+ Thêm người dùng</a>

        <table>
            <tr>
                <th>ID</th>
                <th>Tài khoản</th>
                <th>Vai trò</th>
                <th>Hành động</th>
            </tr>

            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= $row['username'] ?></td>
                    <td>
                        <form action="../../handle/update_role.php" method="post" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <select name="role" onchange="this.form.submit()">
                                <option value="user" <?= $row['role'] == 'user' ? 'selected' : '' ?>>Thành viên</option>
                                <option value="admin" <?= $row['role'] == 'admin' ? 'selected' : '' ?>>Quản trị viên</option>
                            </select>
                        </form>
                    </td>
                    <td>
                        <?php if ($row['username'] !== $_SESSION['username']): ?>
                            <a class="btn-edit" href="edit_user.php?id=<?= $row['id'] ?>">Sửa</a>
                            <a class="btn-danger" href="../../handle/delete_user.php?id=<?= $row['id'] ?>"
                                onclick="return confirm('Xóa người dùng này?')">Xóa</a>
                        <?php else: ?>
                            (Bạn)
                        <?php endif; ?>
                    </td>

                </tr>
            <?php endwhile; ?>

        </table>

    </div>

</body>

</html>