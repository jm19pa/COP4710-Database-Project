<?php
require_once __DIR__ . '/../db.php';
session_start();
header('Content-Type: application/json');
if (empty($_SESSION['mid']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['status'=>'error','error'=>'Admin required']);
    exit;
}
$memberTypes = ['admin','restaurant','customer','donor','needy'];
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
if ($type !== '' && !in_array($type, $memberTypes, true)) {
    http_response_code(400);
    echo json_encode(['status'=>'error','error'=>'Invalid member type']);
    exit;
}
$rows = [];
if ($type !== '' && $q !== '') {
    $stmt = $pdo->prepare('SELECT mid,username,email,m_type AS user_type,named,phone FROM Members WHERE m_type=? AND (username LIKE ? OR email LIKE ?) ORDER BY username LIMIT 200');
    $like = "%$q%"; $stmt->execute([$type, $like, $like]);
} elseif ($type !== '') {
    $stmt = $pdo->prepare('SELECT mid,username,email,m_type AS user_type,named,phone FROM Members WHERE m_type=? ORDER BY username LIMIT 200');
    $stmt->execute([$type]);
} else {
    $stmt = $pdo->prepare('SELECT mid,username,email,m_type AS user_type,named,phone FROM Members WHERE username LIKE ? OR email LIKE ? ORDER BY username LIMIT 200');
    $like = "%$q%"; $stmt->execute([$like, $like]);
}
$rows = $stmt->fetchAll();

echo json_encode(['status'=>'success','rows'=>$rows], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
