<?php
require "../../function/db_connection.php";
$conn = getDbConnection();

$result = $conn->query("SELECT * FROM users");
?>

<h2>Danh sách người dùng</h2>

<table border="1" cellpadding="10" cellspacing="0" width="100%">
    <tr style="background:#4CAF50;color:white;">
        <th>ID</th>
        <th>Tài khoản</th>
        <th>Vai trò</th>
        <th>Hành động</th>
    </tr>

<?php while($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $row['id'] ?></td>
        <td><?= htmlspecialchars($row['username']) ?></td>
        <td>
            <form action="update_role.php" method="post" style="display:flex;gap:6px;">
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                <select name="role" onchange="this.form.submit()">
                    <option value="admin" <?= $row['role']=='admin'?'selected':'' ?>>Quản trị viên</option>
                    <option value="member" <?= $row['role']=='member'?'selected':'' ?>>Thành viên</option>
                </select>
            </form>
        </td>
        <td>
            <?php if($row['id'] == $_SESSION['user_id']): ?>
                (Bạn)
            <?php elseif($row['role'] == 'admin'): ?>
                -
            <?php else: ?>
                <form action="delete_user.php" method="post" onsubmit="return confirm('Bạn có chắc muốn xóa người dùng này?');">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <button style="background:#e53935;color:white;border:none;padding:6px 12px;border-radius:3px;cursor:pointer;">Xóa</button>
                </form>
            <?php endif; ?>
        </td>
    </tr>
<?php endwhile; ?>

</table>
