<?php
require_once __DIR__ . '/../db.php';
session_start();
header('Content-Type: application/json');
if (empty($_SESSION['mid']) || ($_SESSION['user_type'] ?? '') !== 'donor') {
    http_response_code(403);
    echo json_encode(['status'=>'error','error'=>'Donor required']);
    exit;
}
$pid = isset($_GET['pid']);
$rows = [];
$pdo->beginTransaction();

// 1. Check On_Sale availability
$stmt = $pdo->prepare("
    SELECT quantity
    FROM On_Sale
    WHERE pid = ? AND quantity > 0
    FOR UPDATE
");
$stmt->execute([$pid]);
$row = $stmt->fetch();

if (!$row) {
    throw new Exception("Item not available.");
}

// 2. Decrease quantity
$stmt = $pdo->prepare("
    UPDATE On_Sale
    SET quantity = quantity - 1
    WHERE pid = ?
");
$stmt->execute([$pid]);

// 3. Insert purchase or increment quantity
$stmt = $pdo->prepare("
    INSERT INTO Purchased (pid, mid, quantity)
    VALUES (?, ?, 1)
    ON DUPLICATE KEY UPDATE quantity = quantity + 1
");
$stmt->execute([$pid, $_SESSION['mid']]);

// Commit if everything succeeded
$pdo->commit();

echo json_encode(['status'=>'success','rows'=>$rows], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);