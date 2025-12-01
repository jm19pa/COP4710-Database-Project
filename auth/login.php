<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../util/auth_utils.php';
require_once __DIR__ . '/../util/jwt_utils.php';

require_post();
$data = read_input();

$identifier = sanitize_string(
    $data['identifier']
        ?? $data['login_identifier']
        ?? $data['username']
        ?? $data['email']
        ?? null
);
$password = $data['password'] ?? null;
$wantJwt  = !empty($data['include_jwt']);

if (!$identifier || !$password) {
    json_response(422, ['status' => 'error', 'error' => 'identifier and password required']);
}

// Use PDO connection from db.php ($pdo)
$stmt = $pdo->prepare(
    'SELECT mid, username, email, pwd, user_type
     FROM Members
     WHERE username = ? OR email = ?
     LIMIT 1'
);
$stmt->execute([$identifier, $identifier]);
$user = $stmt->fetch();

if (!$user) {
    json_response(401, ['status' => 'error', 'error' => 'Invalid credentials']);
}
if (!password_verify($password, $user['pwd'])) {
    json_response(401, ['status' => 'error', 'error' => 'Invalid credentials']);
}

session_start();
session_regenerate_id(true);
$_SESSION['mid'] = (int)$user['mid'];
$_SESSION['user_type'] = $user['user_type'];

$response = [
    'status' => 'success',
    'mid' => (int)$user['mid'],
    'username' => $user['username'],
    'email' => $user['email'],
    'user_type' => $user['user_type'],
    'session_id' => session_id(),
];

if ($wantJwt) {
    $response['jwt'] = issue_jwt([
        'mid' => (int)$user['mid'],
        'user_type' => $user['user_type'],
        'username' => $user['username'],
    ], 3600);
}

json_response(200, $response);
?>
