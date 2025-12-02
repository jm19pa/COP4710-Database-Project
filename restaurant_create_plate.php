<?php
// restaurant_create_plate.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/util/auth_utils.php';

session_start();
header('Content-Type: application/json');


if (empty($_SESSION['mid']) || ($_SESSION['user_type'] ?? '') !== 'restaurant') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'error' => 'Restaurant login required']);
    exit;
}

require_post();
$data = read_input();

$mid        = (int)$_SESSION['mid'];
$named      = sanitize_string($data['named'] ?? null);
$plate_type = sanitize_string($data['plate_type'] ?? null);
$price      = isset($data['price']) ? floatval($data['price']) : null;
$quantity   = isset($data['quantity']) ? intval($data['quantity']) : null;
$described  = sanitize_string($data['described'] ?? null);

$errors = [];

if (!$named)          $errors['named']       = 'Plate name required';
if (!$plate_type)     $errors['plate_type']  = 'Plate type required';
if ($price === null || $price < 0) $errors['price'] = 'Price must be >= 0';
if ($quantity === null || $quantity <= 0) $errors['quantity'] = 'Quantity must be > 0';
if (!$described)      $errors['described']   = 'Description required';

if ($errors) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'errors' => $errors]);
    exit;
}

try {
    $pdo->beginTransaction();

    $insertPlate = $pdo->prepare('
        INSERT INTO Plates (mid, price, named, plate_type, described)
        VALUES (?, ?, ?, ?, ?)
    ');
    $insertPlate->execute([$mid, $price, $named, $plate_type, $described]);
    $pid = (int)$pdo->lastInsertId();

    $insertSale = $pdo->prepare('
        INSERT INTO On_Sale (pid, quantity)
        VALUES (?, ?)
    ');
    $insertSale->execute([$pid, $quantity]);

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'pid' => $pid,
        'named' => $named
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'error' => 'Database error creating plate']);
}
