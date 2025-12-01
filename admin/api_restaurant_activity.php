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
$stmt = $pdo->prepare('SELECT COALESCE(SUM(os.quantity),0) AS listed FROM On_Sale os JOIN Plates p ON os.pid=p.pid WHERE p.mid=? AND os.listed_at BETWEEN ? AND ?');
$stmt->execute([$mid, $start, $end]); $listed = (int)($stmt->fetch()['listed'] ?? 0);
$stmt = $pdo->prepare('SELECT COALESCE(SUM(r.quantity),0) AS reserved FROM Reserved r JOIN Plates p ON r.pid=p.pid WHERE p.mid=? AND r.reserved_at BETWEEN ? AND ?');
$stmt->execute([$mid, $start, $end]); $reserved = (int)($stmt->fetch()['reserved'] ?? 0);
$stmt = $pdo->prepare('SELECT COALESCE(SUM(pr.quantity),0) AS purchased, COALESCE(SUM(pr.quantity*p.price),0) AS revenue FROM Purchased pr JOIN Plates p ON pr.pid=p.pid WHERE p.mid=? AND pr.purchased_at BETWEEN ? AND ?');
$stmt->execute([$mid, $start, $end]); $r3 = $stmt->fetch();
$purchased = (int)($r3['purchased'] ?? 0); $revenue = (float)($r3['revenue'] ?? 0.0);
$stmt = $pdo->prepare('SELECT COALESCE(SUM(pk.quantity),0) AS picked FROM Picked_up pk JOIN Plates p ON pk.pid=p.pid WHERE p.mid=? AND pk.picked_up_at BETWEEN ? AND ?');
$stmt->execute([$mid, $start, $end]); $picked = (int)($stmt->fetch()['picked'] ?? 0);

echo json_encode(['status'=>'success','listed'=>(int)$listed,'reserved'=>(int)$reserved,'purchased'=>(int)$purchased,'picked'=>(int)$picked,'revenue'=>(float)$revenue]);
