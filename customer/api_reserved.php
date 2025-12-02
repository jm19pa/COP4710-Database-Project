<?php
require_once __DIR__ . '/../db.php';
session_start();
header('Content-Type: application/json');
if (empty($_SESSION['mid']) || ($_SESSION['user_type'] ?? '') !== 'customer') {
    http_response_code(403);
    echo json_encode(['status'=>'error','error'=>'Customer required']);
    exit;
}
$rows = [];
ini_set('display_errors', 1);
$stmt = $pdo->prepare('SELECT P.named, P.price, P.described, B.quantity, P.pid FROM Plates P, Reserved B WHERE B.quantity>0 AND P.pid = B.pid AND ? = B.mid;');
$stmt->execute([$_SESSION['mid']]);
$rows = $stmt->fetchAll();

echo json_encode(['status'=>'success','rows'=>$rows], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
