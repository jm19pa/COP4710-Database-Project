<?php
// restaurant_get_plates.php
require_once __DIR__ . '/../db.php';
session_start();
header('Content-Type: application/json');


if (empty($_SESSION['mid']) || ($_SESSION['user_type'] ?? '') !== 'restaurant') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'error' => 'Restaurant login required']);
    exit;
}

$mid = (int)$_SESSION['mid'];

try {
    
    $sql = '
        SELECT
            p.pid,
            p.named,
            p.plate_type,
            p.price,
            p.described,
            COALESCE(SUM(os.quantity), 0) AS on_sale_qty
        FROM Plates p
        LEFT JOIN On_Sale os ON p.pid = os.pid
        WHERE p.mid = ?
        GROUP BY p.pid, p.named, p.plate_type, p.price, p.described
        ORDER BY 
            p.display_order IS NULL, 
            p.display_order,
            p.named
    ';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$mid]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'plates' => $rows
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'error' => 'Database error']);
}
