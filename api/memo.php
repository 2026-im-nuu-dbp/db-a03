<?php
/**
 * API: 作品相關（新增、編輯、刪除、查詢）
 */

require_once '../config.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

/**
 * 新增或編輯作品
 */
if ($method === 'POST' && $action === 'save') {
    // 須登入才能操作
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => '請先登入。']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $memo_id = $_POST['id'] ?? null;
    $title = $_POST['title'] ?? '';
    $category = $_POST['category'] ?? '';
    $content = $_POST['content'] ?? '';
    $inspiration = $_POST['inspiration'] ?? '';

    // 基本驗證
    if (!$title || !$content) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '標題與內容為必填項目。']);
        exit;
    }

    $image_path = null;
    $thumbnail_path = null;

    // 處理圖片上傳
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array(strtolower($file_ext), $allowed_ext)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '只允許上傳 JPG、PNG 或 GIF 格式的圖片。']);
            exit;
        }

        // 建立上傳目錄
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // 儲存原圖與縮圖
        $filename = 'memo_' . $user_id . '_' . time() . '.' . $file_ext;
        $image_path = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $image_path)) {
            // 產生縮圖
            $thumbnail = generateThumbnail($image_path, 480, 320);
            $thumbnail_path = $upload_dir . 'thumb_' . $filename;
            file_put_contents($thumbnail_path, $thumbnail);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '圖片上傳失敗。']);
            exit;
        }
    }

    // 編輯既有作品
    if ($memo_id) {
        // 檢查作品是否屬於此使用者
        $stmt = $conn->prepare("SELECT user_id FROM dbmemo WHERE id = ?");
        $stmt->bind_param("i", $memo_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0 || $result->fetch_assoc()['user_id'] != $user_id) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '您無權編輯此作品。']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE dbmemo SET title=?, category=?, content=?, inspiration=?" . ($image_path ? ", image_path=?, thumbnail_path=?" : "") . " WHERE id=? AND user_id=?");

        if ($image_path) {
            $stmt->bind_param("ssssssii", $title, $category, $content, $inspiration, $image_path, $thumbnail_path, $memo_id, $user_id);
        } else {
            $stmt->bind_param("ssssii", $title, $category, $content, $inspiration, $memo_id, $user_id);
        }

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => '作品已更新。']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '更新失敗。']);
        }

        $stmt->close();
    } else {
        // 新增新作品
        $stmt = $conn->prepare("INSERT INTO dbmemo (user_id, title, category, content, inspiration, image_path, thumbnail_path, is_public) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("isssss", $user_id, $title, $category, $content, $inspiration, $image_path, $thumbnail_path);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(['success' => true, 'message' => '作品已新增。']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '新增失敗。']);
        }

        $stmt->close();
    }

    exit;
}

/**
 * 刪除作品
 */
if ($method === 'DELETE' && $action === 'delete') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => '請先登入。']);
        exit;
    }

    parse_str(file_get_contents("php://input"), $_DELETE);
    $memo_id = $_DELETE['id'] ?? null;

    if (!$memo_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '無效的作品 ID。']);
        exit;
    }

    $user_id = $_SESSION['user_id'];

    // 檢查作品是否屬於此使用者
    $stmt = $conn->prepare("SELECT image_path, thumbnail_path FROM dbmemo WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $memo_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => '您無權刪除此作品。']);
        exit;
    }

    $memo = $result->fetch_assoc();

    // 刪除資料庫記錄
    $stmt = $conn->prepare("DELETE FROM dbmemo WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $memo_id, $user_id);

    if ($stmt->execute()) {
        // 刪除上傳的圖檔
        if ($memo['image_path'] && file_exists($memo['image_path'])) {
            unlink($memo['image_path']);
        }
        if ($memo['thumbnail_path'] && file_exists($memo['thumbnail_path'])) {
            unlink($memo['thumbnail_path']);
        }

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => '作品已刪除。']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '刪除失敗。']);
    }

    $stmt->close();
    exit;
}

/**
 * 查詢所有公開作品（含作者資訊）
 */
if ($method === 'GET' && $action === 'list') {
    $stmt = $conn->prepare("
        SELECT 
            m.id, m.user_id, u.nickname, m.title, m.category, m.content, 
            m.inspiration, m.image_path, m.thumbnail_path, m.created_at, m.updated_at
        FROM dbmemo m
        JOIN dbusers u ON m.user_id = u.id
        WHERE m.is_public = 1
        ORDER BY m.created_at DESC
    ");

    $stmt->execute();
    $result = $stmt->get_result();
    $memos = [];

    while ($row = $result->fetch_assoc()) {
        $memos[] = $row;
    }

    echo json_encode(['success' => true, 'memos' => $memos]);
    $stmt->close();
    exit;
}

/**
 * 查詢使用者自己的作品
 */
if ($method === 'GET' && $action === 'own') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => '請先登入。']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("
        SELECT 
            id, user_id, title, category, content, inspiration, 
            image_path, thumbnail_path, created_at, updated_at
        FROM dbmemo
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $memos = [];

    while ($row = $result->fetch_assoc()) {
        $memos[] = $row;
    }

    echo json_encode(['success' => true, 'memos' => $memos]);
    $stmt->close();
    exit;
}

/**
 * 用 GD Library 產生縮圖
 */
function generateThumbnail($image_path, $max_width, $max_height) {
    $image_info = getimagesize($image_path);
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

http_response_code(400);
echo json_encode(['success' => false, 'message' => '無效的請求。']);
