<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$dbname = "qlct";
$user = "root";
$pass = "ct103205"; // Nếu XAMPP mặc định để rỗng

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function checkLogin() {
  if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
  }
}
