<?php
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

try {
    $pdo->beginTransaction();
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

    // Remove dependent rows first
    $pdo->exec('DELETE FROM Picked_up WHERE mid = 1 OR donor_id = 1');
    $pdo->exec('DELETE FROM Purchased WHERE mid = 1');
    $pdo->exec('DELETE FROM Reserved WHERE mid = 1');
    $pdo->exec('DELETE FROM Cards WHERE mid = 1');

    // If admin owned any plates (unlikely), clean them up
    // Remove plate-related state for plates owned by admin
    $pdo->exec('DELETE os FROM On_Sale os JOIN Plates p ON os.pid = p.pid WHERE p.mid = 1');
    $pdo->exec('DELETE i FROM Images i JOIN Plates p ON i.pid = p.pid WHERE p.mid = 1');
    $pdo->exec('DELETE FROM Plates WHERE mid = 1');

    // Finally delete the admin member
    $pdo->exec('DELETE FROM Members WHERE mid = 1');

    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    $pdo->commit();
    echo json_encode(['status'=>'success','message'=>'Deleted admin MID 1']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    echo json_encode(['status'=>'error','error'=>$e->getMessage()]);
}
