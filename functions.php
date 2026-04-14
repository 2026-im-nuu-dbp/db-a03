<?php
/**
 * 共用函式、巨集、重導向工具
 */

/**
 * 取得全域資料庫連線
 */
function getDb() {
    global $conn;
    if (!isset($conn) || !$conn) {
        require_once __DIR__ . '/config.php';
    }
    return $conn;
}

/**
 * 檢查使用者是否已登入
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

/**
 * 取得目前登入的使用者
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'account' => $_SESSION['account'],
        'nickname' => $_SESSION['nickname'],
        'gender' => $_SESSION['gender'],
        'interests' => $_SESSION['interests']
    ];
}

/**
 * 防止 XSS：將文字進行 HTML 編碼
 */
function h($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * 重導向至指定頁面
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * 紀錄操作事件
 */
function recordLog($user_id, $account, $event, $success, $notes = '') {
    $conn = getDb();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $success_flag = $success ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO dblog (user_id, account, event, success, ip_address, notes) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ississ", $user_id, $account, $event, $success_flag, $ip, $notes);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * 若檔案存在則刪除
 */
function removeFileIfExists($relativePath) {
    if (!$relativePath) {
        return;
    }
    $fullPath = __DIR__ . '/' . ltrim($relativePath, '/');
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }
}

/**
 * 產生縮圖（使用 GD Library）
 */
function generateThumbnail($image_path, $max_width, $max_height) {
    $image_info = getimagesize($image_path);
    if (!$image_info) {
        return null;
    }

    $width = $image_info[0];
    $height = $image_info[1];
    $type = $image_info[2];

    // 建立圖像資源
    switch ($type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($image_path);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($image_path);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($image_path);
            break;
        default:
            return null;
    }

    if (!$image) {
        return null;
    }

    // 計算縮圖尺寸（保持比例）
    $aspect_ratio = $width / $height;
    if ($width > $height) {
        $new_width = $max_width;
        $new_height = round($max_width / $aspect_ratio);
    } else {
        $new_height = $max_height;
        $new_width = round($max_height * $aspect_ratio);
    }

    // 產生縮圖
    $thumbnail = imagecreatetruecolor($new_width, $new_height);
    imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    // 輸出為 JPEG
    ob_start();
    imagejpeg($thumbnail, null, 80);
    $thumb_data = ob_get_clean();

    imagedestroy($image);
    imagedestroy($thumbnail);

    return $thumb_data;
}
