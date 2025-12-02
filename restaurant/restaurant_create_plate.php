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
    $available_from   = $_POST['available_from'] ?? '';
    $available_until  = $_POST['available_until'] ?? '';

    if ($named === '' || $plate_type === '' || $price === null || $quantity === null || $available_from === '' || $available_until === '') {
        die('Missing required fields.');
    }

    if (strtotime($available_until) < strtotime($available_from)) {
        die("Available Until cannot be before Available From.");
    }

    // Convert HTML datetime-local ("2025-12-01T13:00") to MySQL DATETIME ("2025-12-01 13:00:00")
    $available_from_mysql = str_replace('T', ' ', $available_from) . ':00';
    $available_until_mysql = str_replace('T', ' ', $available_until) . ':00';

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
            INSERT INTO On_Sale (pid, quantity, available_from, available_until)
            VALUES (:pid, :quantity, :available_from, :available_until)
        ');
        $stmt2->execute([
            ':pid'            => $pid,
            ':quantity'       => $quantity,
            ':available_from' => $available_from_mysql,
            ':available_until'=> $available_until_mysql,
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
