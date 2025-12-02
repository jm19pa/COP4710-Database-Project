<?php
// restaurant_create_plate.php
session_start();
require_once '../db.php';    
require_once '../auth_utils.php';


if (empty($_SESSION['mid']) || ($_SESSION['user_type'] ?? '') !== 'restaurant') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'error' => 'Restaurant login required']);
    exit;
}

$mid = (int)$_SESSION['mid'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $named       = trim($_POST['named'] ?? '');
    $plate_type  = trim($_POST['plate_type'] ?? '');
    $price       = $_POST['price'] ?? null;
    $quantity    = $_POST['quantity'] ?? null;
    $described   = trim($_POST['described'] ?? '');
    $starts_at   = $_POST['starts_at'] ?? '';
    $ends_at     = $_POST['ends_at'] ?? '';

    if ($named === '' || $plate_type === '' || $price === null || $quantity === null || $starts_at === '' || $ends_at === '') {
        die('Missing required fields.');
    }

    // Convert HTML datetime-local ("2025-12-01T13:00") to MySQL DATETIME ("2025-12-01 13:00:00")
    $starts_at_mysql = str_replace('T', ' ', $starts_at) . ':00';
    $ends_at_mysql   = str_replace('T', ' ', $ends_at) . ':00';

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('
            INSERT INTO Plates (mid, price, named, plate_type, described)
            VALUES (:mid, :price, :named, :plate_type, :described)
        ');
        $stmt->execute([
            ':mid'        => $mid,
            ':price'      => $price,
            ':named'      => $named,
            ':plate_type' => $plate_type,
            ':described'  => $described,
        ]);

        $pid = $pdo->lastInsertId();

        $stmt2 = $pdo->prepare('
            INSERT INTO On_Sale (pid, quantity, starts_at, ends_at)
            VALUES (:pid, :quantity, :starts_at, :ends_at)
        ');
        $stmt2->execute([
            ':pid'       => $pid,
            ':quantity'  => $quantity,
            ':starts_at' => $starts_at_mysql,
            ':ends_at'   => $ends_at_mysql,
        ]);

        $pdo->commit();
        header('Location: restaurant.php?created=1');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die('Error creating plate: ' . $e->getMessage());
    }
} else {
    header('Location: restaurant.php');
    exit;
}
