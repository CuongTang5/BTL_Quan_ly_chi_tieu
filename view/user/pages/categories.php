<?php

require __DIR__ . "/../../../function/db_connection.php";
$conn = getDbConnection();

// --- Tìm kiếm ---
$search = $_GET['search'] ?? '';

// --- Phân trang ---
$limit = 8;
$page = $_GET['p'] ?? 1;
$offset = ($page - 1) * $limit;

// --- Điều kiện SQL ---
$where = "WHERE 1";
if ($search !== '') $where .= " AND name_categories LIKE '%$search%'";

// --- Tổng số danh mục ---
$resultTotal = $conn->query("SELECT COUNT(*) AS total FROM categories $where");
$total = $resultTotal ? $resultTotal->fetch_assoc()['total'] : 0;
$pages = $total > 0 ? ceil($total / $limit) : 1;

// --- Lấy danh sách danh mục ---
$categories = $conn->query("SELECT * FROM categories $where ORDER BY id_categories DESC LIMIT $limit OFFSET $offset");
?>

<div class="table-container">

    <div class="top-bar">
        <h2>Quản lý danh mục chi tiêu</h2>

        <form method="GET" class="filter-form">
            <input type="hidden" name="page" value="categories">
            <input type="text" name="search" placeholder="Tìm danh mục..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn add-btn">Lọc</button>
            <button type="button" class="btn add-btn"
                onclick="window.location.href='../../view/user/pages/add_category.php'">➕ Thêm danh mục</button>
        </form>
    </div>

    <form method="POST" action="/Quanlychitieu/handle/delete_multi_category_process.php"
        onsubmit="return confirm('Xóa danh mục đã chọn?');">

        <table class="table">
            <tr>
                <th><input type="checkbox" id="checkAll"></th>
                <th>ID</th>
                <th>Tên danh mục</th>
                <th>Mô tả</th>
                <th>Hành động</th>
            </tr>

            <?php while ($c = $categories->fetch_assoc()): ?>
                <tr>
                    <td><input type="checkbox" class="chkbox" name="ids[]" value="<?= $c['id_categories'] ?>"></td>
                    <td><?= $c['id_categories'] ?></td>
                    <td><?= htmlspecialchars($c['name_categories']) ?></td>
                    <td><?= htmlspecialchars($c['description']) ?></td>
                    <td>
                        <a href="admin.php?page=edit_category&id=<?= $c['id_categories'] ?>" class="btn small edit" title="Sửa">✏️</a>
                        <a href="/Quanlychitieu/handle/delete_category_process.php?id=<?= $c['id_categories'] ?>"
                           class="btn small delete" onclick="return confirm('Xóa danh mục này?')" title="Xóa">🗑️</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>

        <button id="deleteBtn" type="submit" class="btn delete" style="display:none;">Xóa đã chọn</button>
    </form>

    <!-- Phân trang -->
    <div class="pagination">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a href="admin.php?page=categories&p=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</div>

<script>
const checkboxes = document.querySelectorAll(".chkbox");
const deleteBtn = document.getElementById("deleteBtn");

// Ẩn/hiện nút xóa nhiều
function toggleDeleteBtn() {
    const anyChecked = Array.from(checkboxes).some(ch => ch.checked);
    deleteBtn.style.display = anyChecked ? "inline-block" : "none";
}

checkboxes.forEach(ch => ch.addEventListener("change", toggleDeleteBtn));

document.getElementById("checkAll").addEventListener("change", function () {
    checkboxes.forEach(ch => ch.checked = this.checked);
    toggleDeleteBtn();
});
</script>
