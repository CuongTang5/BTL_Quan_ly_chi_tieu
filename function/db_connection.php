<?php

function getDbConnection() {
    $host = "localhost";
    $user = "root";
    $pass = "ct103205";
    $dbname = "qlct";

    // Tạo kết nối
    $conn = new mysqli($host, $user, $pass, $dbname);

    // Kiểm tra lỗi
    if($conn->connect_error){
        die("Kết nối thất bại: " . $conn->connect_error);
    }

    // Thiết lập UTF-8 cho tiếng Việt
    $conn->set_charset("utf8");

    return $conn;
}

?>
