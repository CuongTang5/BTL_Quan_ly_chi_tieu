<?php
session_start();
include __DIR__ . '/../../function/db_connection.php';

if(!isset($_SESSION['user_id'])){
    header("Location: ../auth/login.php");
    exit();
}

$conn = getDbConnection();
$user_id = $_SESSION['user_id'];
$id = $_GET['id'] ?? 0;

// Lấy thông tin chi tiêu
$stmt = $conn->prepare("SELECT * FROM transactions WHERE id=? AND user_id=?");
$stmt->bind_param("ii",$id,$user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $date = $_POST['date'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];

    $stmt = $conn->prepare("UPDATE transactions SET date=?, category=?, description=?, amount=? WHERE id=? AND user_id=?");
    $stmt->bind_param("sssdi",$date,$category,$description,$amount,$id,$user_id);
    $stmt->execute();

    header("Location: transactions.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Sửa chi tiêu</title>
<link rel="stylesheet" href="../../css/style.css">
</head>
<body>
<h2 style="text-align:center;">Sửa chi tiêu</h2>
<form method="post" action="transactions_edit.php?id=<?= $id ?>" style="width:300px;margin:auto;">
    <label>Ngày:</label><br>
    <input type="date" name="date" value="<?= $row['date'] ?>" required><br><br>
    <label>Loại:</label><br>
    <input type="text" name="category" value="<?= htmlspecialchars($row['category']) ?>" required><br><br>
    <label>Mô tả:</label><br>
    <input type="text" name="description" value="<?= htmlspecialchars($row['description']) ?>"><br><br>
    <label>Số tiền:</label><br>
    <input type="number" name="amount" step="0.01" value="<?= $row['amount'] ?>" required><br><br>
    <button type="submit">Cập nhật</button>
</form>
<p style="text-align:center;"><a href="transactions.php">← Quay lại</a></p>
</body>
</html>
