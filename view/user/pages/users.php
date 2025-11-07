<?php
require "../../function/db_connection.php";
$conn = getDbConnection();

// --- Xử lý tìm kiếm & lọc ---
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';

// --- Phân trang ---
$limit = 8;
$page = $_GET['p'] ?? 1;
$offset = ($page - 1) * $limit;

// Điều kiện SQL
$where = "WHERE 1";
if ($search !== '')
    $where .= " AND username LIKE '%$search%'";
if ($roleFilter !== '')
    $where .= " AND role = '$roleFilter'";

$total = $conn->query("SELECT COUNT(*) AS total FROM users $where")->fetch_assoc()['total'];
$pages = ceil($total / $limit);

$users = $conn->query("SELECT * FROM users $where LIMIT $limit OFFSET $offset");
?>

<div class="top-bar">
    <h2>Quản lý người dùng</h2>

    <form method="GET" class="filter-form">
        <input type="hidden" name="page" value="users">

        <input type="text" name="search" placeholder="Tìm tên người dùng..." value="<?= htmlspecialchars($search) ?>">

        <select name="role">
            <option value="">Tất cả vai trò</option>
            <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Quản trị viên</option>
            <option value="user" <?= $roleFilter === 'user' ? 'selected' : '' ?>>Thành viên</option>
        </select>

        <button type="submit" class="btn">Lọc</button>

        <!-- 👉 CHUYỂN SANG TRANG THÊM MỚI -->
        <button type="button" class="btn add-btn" onclick="window.location.href='../../view/user/pages/add_user.php'">
            Thêm người dùng
        </button>

    </form>
</div>

<div class="table-container">
    <form method="POST" action="/Quanlychitieu/handle/delete_multi_user_process.php"
        onsubmit="return confirm('Xóa người dùng đã chọn?');">

        <table class="table">
            <tr>
                <th><input type="checkbox" id="checkAll"></th>
                <th>ID</th>
                <th>Tài khoản</th>
                <th>Vai trò</th>
                <th>Hành động</th>
            </tr>

            <?php while ($u = $users->fetch_assoc()): ?>
                <tr>
                    <td><input type="checkbox" class="chkbox" name="ids[]" value="<?= $u['id'] ?>"></td>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td>
                        <form action="/Quanlychitieu/handle/update_role_process.php" method="POST">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <select name="role" onchange="this.form.submit()">
                                <option value="admin" <?= $u['role'] == 'admin' ? 'selected' : '' ?>>Quản trị viên</option>
                                <option value="user" <?= $u['role'] == 'user' ? 'selected' : '' ?>>Thành viên</option>
                            </select>
                        </form>
                    </td>
                    <td>
                        <!-- Sửa -->
                        <a href="admin.php?page=edit_user&id=<?= $u['id'] ?>" class="btn small edit" title="Sửa">
                            ✍
                        </a>

                        <!-- Xóa -->
                        <a href="/Quanlychitieu/handle/delete_user_process.php?id=<?= $u['id'] ?>" class="btn small delete"
                            onclick="return confirm('Xóa người dùng này?')" title="Xóa">
                            🗑️
                        </a>
                    </td>

                </tr>
            <?php endwhile; ?>
        </table>

        <!-- Nút Xóa nhiều, mặc định ẩn -->
        <button id="deleteBtn" type="submit" class="btn delete">
            Xóa đã chọn
        </button>
    </form>
</div>


<!-- PHÂN TRANG -->
<div class="pagination">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="admin.php?page=users&p=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
</div>

<script>
    const checkboxes = document.querySelectorAll(".chkbox");
    const deleteBtn = document.getElementById("deleteBtn");

    // Hiển thị nút Xóa khi có ít nhất 1 checkbox được tích
    function toggleDeleteBtn() {
        const anyChecked = Array.from(checkboxes).some(ch => ch.checked);
        deleteBtn.style.display = anyChecked ? "inline-block" : "none";
    }

    // Gán sự kiện thay đổi cho từng checkbox
    checkboxes.forEach(ch => ch.addEventListener("change", toggleDeleteBtn));

    // Check/uncheck tất cả
    document.getElementById("checkAll").addEventListener("change", function () {
        checkboxes.forEach(ch => ch.checked = this.checked);
        toggleDeleteBtn();
    });
</script>