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

mysqli_report(MYSQLI_REPORT_OFF);

// 先連線到 MySQL 伺服器（不指定資料庫）
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($conn->connect_error) {
    http_response_code(500);
    die('資料庫伺服器連線失敗：' . $conn->connect_error);
}

// 確保資料庫存在；不存在就自動建立
$dbNameEscaped = $conn->real_escape_string(DB_NAME);
if (!$conn->query("CREATE DATABASE IF NOT EXISTS `{$dbNameEscaped}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    http_response_code(500);
    die('建立資料庫失敗：' . $conn->error);
}

if (!$conn->select_db(DB_NAME)) {
    http_response_code(500);
    die('選取資料庫失敗：' . $conn->error);
}

// 設定字元編碼為 UTF-8
$conn->set_charset('utf8mb4');

/**
 * 確保必要資料表存在；若缺少則由 SQL 檔自動建立
 */
function ensureTables(mysqli $conn) {
    $requiredTables = ['dbusers', 'dbmemo', 'dblog'];
    $missing = [];

    foreach ($requiredTables as $table) {
        $tableEscaped = $conn->real_escape_string($table);
        $check = $conn->query("SHOW TABLES LIKE '{$tableEscaped}'");
        if (!$check || $check->num_rows === 0) {
            $missing[] = $table;
        }
    }

    if (!$missing) {
        return;
    }

    $sqlFiles = [
        __DIR__ . '/dbusers.sql',
        __DIR__ . '/dbmemo.sql',
        __DIR__ . '/dblog.sql',
    ];

    foreach ($sqlFiles as $filePath) {
        if (!file_exists($filePath)) {
            continue;
        }

        $sql = trim((string)file_get_contents($filePath));
        if ($sql === '') {
            continue;
        }

        if ($conn->multi_query($sql)) {
            while ($conn->more_results() && $conn->next_result()) {
                // 清空 multi_query 結果
            }
        }
    }
}

ensureTables($conn);
