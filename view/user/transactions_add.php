<?php
session_start();
include __DIR__ . '/../../function/db_connection.php';

if(!isset($_SESSION['user_id'])){
    header("Location: ../auth/login.php");
    exit();
}

$conn = getDbConnection();

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $date = $_POST['date'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO transactions (user_id,date,category,description,amount) VALUES (?,?,?,?,?)");
    $stmt->bind_param("isssd", $user_id, $date, $category, $description, $amount);
    $stmt->execute();

    header("Location: transactions.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Thêm chi tiêu</title>
<link rel="stylesheet" href="../../css/style.css">
</head>
<body>
<h2 style="text-align:center;">Thêm chi tiêu mới</h2>
<form method="post" action="transactions_add.php" style="width:300px;margin:auto;">
    <label>Ngày:</label><br>
    <input type="date" name="date" required><br><br>
    <label>Loại:</label><br>
    <input type="text" name="category" required><br><br>
    <label>Mô tả:</label><br>
    <input type="text" name="description"><br><br>
    <label>Số tiền:</label><br>
    <input type="number" name="amount" step="0.01" required><br><br>
    <button type="submit">Thêm</button>
</form>
<p style="text-align:center;"><a href="transactions.php">← Quay lại</a></p>
</body>
</html>
