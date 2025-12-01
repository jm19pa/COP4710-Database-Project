<?php
require_once __DIR__ . '/../db.php';
session_start();
header('Content-Type: application/json');
if (empty($_SESSION['mid']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['status'=>'error','error'=>'Admin required']);
    exit;
}
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$mid = isset($_GET['mid']) ? intval($_GET['mid']) : 0;
if ($mid <= 0) { http_response_code(400); echo json_encode(['status'=>'error','error'=>'mid required']); exit; }
$start = sprintf('%d-01-01 00:00:00', $year);
$end   = sprintf('%d-12-31 23:59:59', $year);
$stmt = $pdo->prepare('SELECT COALESCE(SUM(pr.quantity),0) AS items, COALESCE(SUM(pr.quantity*p.price),0) AS amount FROM Purchased pr JOIN Plates p ON pr.pid=p.pid WHERE pr.mid=? AND pr.purchased_at BETWEEN ? AND ?');
$stmt->execute([$mid, $start, $end]); $sum = $stmt->fetch();
$items = (int)($sum['items'] ?? 0); $amount = (float)($sum['amount'] ?? 0.0);
$stmt = $pdo->prepare('SELECT COALESCE(SUM(pk.quantity),0) AS picked, COALESCE(SUM(pk.quantity*p.price),0) AS value FROM Picked_up pk JOIN Plates p ON pk.pid=p.pid WHERE pk.donor_id=? AND pk.picked_up_at BETWEEN ? AND ?');
$stmt->execute([$mid, $start, $end]); $f = $stmt->fetch();
$picked = (int)($f['picked'] ?? 0); $value = (float)($f['value'] ?? 0.0);

echo json_encode(['status'=>'success','items'=>$items,'amount'=>$amount,'picked'=>$picked,'value'=>$value]);
