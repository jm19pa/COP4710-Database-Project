<?php
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

try {
    $pdo->beginTransaction();

    // Temporarily disable FK checks to control deletion order
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

    // Delete state tables first, excluding rows related to admin (mid=1) where applicable
    // Picked_up: keep rows where mid=1 or donor_id=1 (none expected typically), else delete
    $pdo->exec('DELETE FROM Picked_up WHERE mid <> 1 AND (donor_id IS NULL OR donor_id <> 1)');

    // Purchased: remove all rows where buyer is not admin
    $pdo->exec('DELETE FROM Purchased WHERE mid <> 1');

    // Reserved: remove all rows where reserver is not admin
    $pdo->exec('DELETE FROM Reserved WHERE mid <> 1');

    // On_Sale: remove all rows for plates that are not owned by admin
    // (admin is not a restaurant in seed; this will effectively clear On_Sale)
    $pdo->exec('DELETE os FROM On_Sale os JOIN Plates p ON os.pid = p.pid WHERE p.mid <> 1');

    // Images tied to plates not owned by admin
    $pdo->exec('DELETE i FROM Images i JOIN Plates p ON i.pid = p.pid WHERE p.mid <> 1');

    // Cards for non-admin members
    $pdo->exec('DELETE FROM Cards WHERE mid <> 1');

    // Plates: remove all plates not owned by admin
    $pdo->exec('DELETE FROM Plates WHERE mid <> 1');

    // Members: remove everyone except admin MID 1
    $pdo->exec('DELETE FROM Members WHERE mid <> 1');

    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    $pdo->commit();
    echo json_encode(['status'=>'success','message'=>'Cleared all data except admin MID 1']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    echo json_encode(['status'=>'error','error'=>$e->getMessage()]);
}
