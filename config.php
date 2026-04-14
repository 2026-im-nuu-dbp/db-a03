<?php
/**
 * 資料庫連線設定
 * 連接 Laragon 上的 MySQL
 */

// 資料庫連線參數
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Laragon 預設為空密碼
define('DB_NAME', 'db_a03');

// 建立 MySQLi 連線物件
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// 檢查連線是否成功
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['error' => '資料庫連線失敗: ' . $conn->connect_error]));
}

// 設定字元編碼為 UTF-8
$conn->set_charset('utf8mb4');
