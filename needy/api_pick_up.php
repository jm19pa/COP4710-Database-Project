<?php
require_once __DIR__ . '/../db.php';
session_start();
header('Content-Type: application/json');
if (empty($_SESSION['mid']) || ($_SESSION['user_type'] ?? '') !== 'needy') {
    http_response_code(403);
    echo json_encode(['status'=>'error','error'=>'Needy required']);
    exit;
}
$pid = isset($_GET['pid']);
$rows = [];
$pdo->beginTransaction();

$stmt = $pdo->prepare("
    SELECT SUM(P.quantity) 
    FROM Picked_Up P
    WHERE P.mid = ?;
");
$stmt->execute([$pid, $_SESSION['mid']]);
$row = $stmt->fetch();

if($row[1]<2){

    $stmt = $pdo->prepare("
        SELECT quantity
        FROM Reserved
        WHERE pid = ? AND mid = ? AND quantity > 0
        FOR UPDATE
    ");
    $stmt->execute([$pid, $_SESSION['mid']]);
    $row = $stmt->fetch();

    if ($row) {
        // Decrease quantity
        $stmt = $pdo->prepare("
            UPDATE Reserved 
            SET quantity = quantity - 1
            WHERE pid = ? AND mid = ?
        ");
        $stmt->execute([$pid, $_SESSION['mid']]);

        // Insert pickup
        $stmt = $pdo->prepare("
            INSERT INTO Picked_up (pid, mid, quantity)
            VALUES (?, ?, 1)
        ");
        $stmt->execute([$pid, $_SESSION['mid']]);
    }
}

else {
    echo "Not Needy any more!";
}

$pdo->commit();

echo json_encode(['status'=>'success','rows'=>$rows], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);