<?php
require_once __DIR__ . '/../db.php';
session_start();
header('Content-Type: application/json');
if (empty($_SESSION['mid']) || ($_SESSION['user_type'] ?? '') !== 'needy') {
    http_response_code(403);
    echo json_encode(['status'=>'error','error'=>'Needy required']);
    exit;
}
$rows = [];
ini_set('display_errors', 1);
$stmt = $pdo->prepare('SELECT P.named, P.price, P.described, OS.quantity, P.pid FROM Plates P, Purchased OS WHERE OS.quantity>0 AND P.pid = OS.pid;');
$stmt->execute();
$rows = $stmt->fetchAll();

echo json_encode(['status'=>'success','rows'=>$rows], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
