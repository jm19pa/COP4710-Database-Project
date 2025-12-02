<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['mid'])) {
    header("Location: auth/login.php");
    exit;
}

$mid = $_SESSION['mid'];

$username = trim($_POST["username"]);
$email    = trim($_POST["email"]);
$named    = trim($_POST["named"]);
$phone    = trim($_POST["phone"]);


if ($username === "" || $email === "" || $named === "") {
    die("All fields except phone are required.");
}

try {
    $stmt = $pdo->prepare("
        UPDATE Members
        SET username = :username,
            email = :email,
            named = :named,
            phone = :phone
        WHERE mid = :mid
    ");

    $stmt->execute([
        ":username" => $username,
        ":email" => $email,
        ":named" => $named,
        ":phone" => ($phone === "" ? null : $phone),
        ":mid" => $mid
    ]);

    // Redirect back to previous page if provided
    $returnTo = isset($_POST['return_to']) ? trim($_POST['return_to']) : '';

    // Safety: only allow relative paths starting with '/' (no schema/host)
    $isSafe = false;
    if ($returnTo !== '') {
        // Disallow CRLF and control characters
        if (preg_match('/^[\x20-\x7E]+$/', $returnTo) === 1 && str_starts_with($returnTo, '/')) {
            $isSafe = true;
        }
    }

    $target = $isSafe ? $returnTo : '/index.html';
    header("Location: $target", true, 302);
    exit;

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
