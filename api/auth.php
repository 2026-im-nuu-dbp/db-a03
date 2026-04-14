<?php
/**
 * API: 使用者認證相關（登入、註冊、登出）
 */

require_once '../config.php';
session_start();

// 設定回應格式為 JSON
header('Content-Type: application/json; charset=utf-8');

// 取得請求方式與活動
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

/**
 * 註冊新使用者
 */
if ($method === 'POST' && $action === 'register') {
    $account = $_POST['account'] ?? '';
    $nickname = $_POST['nickname'] ?? '';
    $password = $_POST['password'] ?? '';
    $gender = $_POST['gender'] ?? 'other';
    $interests = $_POST['interests'] ?? '';

    // 基本驗證
    if (!$account || !$nickname || !$password) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '請完整填寫帳號、暱稱與密碼。']);
        exit;
    }

    // 檢查帳號是否存在
    $stmt = $conn->prepare("SELECT id FROM dbusers WHERE account = ?");
    $stmt->bind_param("s", $account);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => '此帳號已存在，請換一個帳號。']);
        exit;
    }

    // 密碼加密儲存
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // 插入新使用者
    $stmt = $conn->prepare("INSERT INTO dbusers (account, nickname, password, gender, interests) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $account, $nickname, $hashed_password, $gender, $interests);

    if ($stmt->execute()) {
        // 記錄註冊事件
        $user_id = $conn->insert_id;
        recordLog($conn, $user_id, $account, 'register', true, '成功註冊');

        http_response_code(201);
        echo json_encode(['success' => true, 'message' => '註冊完成，請登入。']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '註冊失敗，請稍後重試。']);
    }

    $stmt->close();
    exit;
}

/**
 * 使用者登入
 */
if ($method === 'POST' && $action === 'login') {
    $account = $_POST['account'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!$account || !$password) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '請輸入帳號與密碼。']);
        exit;
    }

    // 查詢使用者
    $stmt = $conn->prepare("SELECT id, account, nickname, password, gender, interests FROM dbusers WHERE account = ?");
    $stmt->bind_param("s", $account);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        recordLog($conn, null, $account, 'login', false, '帳號或密碼錯誤');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => '帳號或密碼錯誤，請重新輸入。']);
        exit;
    }

    $user = $result->fetch_assoc();

    // 驗證密碼
    if (!password_verify($password, $user['password'])) {
        recordLog($conn, $user['id'], $account, 'login', false, '帳號或密碼錯誤');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => '帳號或密碼錯誤，請重新輸入。']);
        exit;
    }

    // 登入成功，建立 session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['account'] = $user['account'];
    $_SESSION['nickname'] = $user['nickname'];
    $_SESSION['gender'] = $user['gender'];
    $_SESSION['interests'] = $user['interests'];

    recordLog($conn, $user['id'], $account, 'login', true, '登入成功');

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => '登入成功。',
        'user' => [
            'id' => $user['id'],
            'account' => $user['account'],
            'nickname' => $user['nickname'],
            'gender' => $user['gender'],
            'interests' => $user['interests']
        ]
    ]);

    $stmt->close();
    exit;
}

/**
 * 使用者登出
 */
if ($method === 'POST' && $action === 'logout') {
    if (isset($_SESSION['user_id'])) {
        recordLog($conn, $_SESSION['user_id'], $_SESSION['account'], 'logout', true, '登出');
    }
    
    session_destroy();
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => '已登出。']);
    exit;
}

/**
 * 取得目前登入使用者資訊
 */
if ($method === 'GET' && $action === 'current') {
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'account' => $_SESSION['account'],
                'nickname' => $_SESSION['nickname'],
                'gender' => $_SESSION['gender'],
                'interests' => $_SESSION['interests']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'user' => null]);
    }
    exit;
}

/**
 * 記錄使用者操作事件
 */
function recordLog($conn, $user_id, $account, $event, $success, $notes) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $success_flag = $success ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO dblog (user_id, account, event, success, ip_address, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isisis", $user_id, $account, $event, $success_flag, $ip, $notes);
    $stmt->execute();
    $stmt->close();
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => '無效的請求。']);
