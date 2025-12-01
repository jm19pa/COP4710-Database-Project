<?php
// login.php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['login_identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        echo '<p style="color:red;">Please enter your username/email and password.</p>';
        echo '<p><a href="index.html">Back to login</a></p>';
        exit;
    }

    $stmt = $pdo->prepare('
        SELECT mid, username, email, pwd, m_type
        FROM Members
        WHERE username = :id OR email = :id
        LIMIT 1
    ');
    $stmt->execute([':id' => $identifier]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['pwd'])) {
        $_SESSION['mid']      = $user['mid'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['m_type']   = $user['m_type'];

        // later: redirect based on m_type
        echo 'Login successful as ' . htmlspecialchars($user['m_type']) . '.';
        echo '<br><a href="dashboard.php">Go to dashboard</a>';
    } else {
        echo '<p style="color:red;">Invalid credentials.</p>';
        echo '<p><a href="index.html">Back to login</a></p>';
    }
} else {
    header('Location: index.html');
    exit;
}
