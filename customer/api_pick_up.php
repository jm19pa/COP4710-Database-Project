<?php
require_once __DIR__ . '/../db.php';
session_start();
header('Content-Type: application/json');
if (empty($_SESSION['mid']) || ($_SESSION['user_type'] ?? '') !== 'customer') {
    http_response_code(403);
    echo json_encode(['status'=>'error','error'=>'Customer required']);
    exit;
}
$pid = isset($_GET['pid']);
$rows = [];
$stmt = $pdo->prepare('CALL Customer_Pick_Up(?,?)');
$stmt->execute([$_SESSION['mid'], $pid]);
$rows = $stmt->fetchAll();

echo json_encode(['status'=>'success','rows'=>$rows], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);