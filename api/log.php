<?php
/**
 * API: 登入紀錄相關
 */

require_once '../config.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

/**
 * 取得最近 100 筆紀錄
 */
if ($method === 'GET' && $action === 'list') {
    $limit = 100;

    $stmt = $conn->prepare("
        SELECT 
            id, account, event, success, ip_address, 
            occurred_at, notes
        FROM dblog
        ORDER BY occurred_at DESC
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $logs = [];

    while ($row = $result->fetch_assoc()) {
        $logs[] = [
            'id' => $row['id'],
            'account' => $row['account'],
            'event' => $row['event'],
            'success' => (int)$row['success'],
            'ip_address' => $row['ip_address'],
            'occurred_at' => $row['occurred_at'],
            'notes' => $row['notes']
        ];
    }

    echo json_encode(['success' => true, 'logs' => $logs]);
    $stmt->close();
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => '無效的請求。']);
