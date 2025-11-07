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

$stmt = $conn->prepare("DELETE FROM transactions WHERE id=? AND user_id=?");
$stmt->bind_param("ii",$id,$user_id);
$stmt->execute();

header("Location: transactions.php");
exit();
?>
