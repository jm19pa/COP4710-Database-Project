<?php
session_start();
require "db.php";

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

    header("Location: account.php?updated=1");
    exit;

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
