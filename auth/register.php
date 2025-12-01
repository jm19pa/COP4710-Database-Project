<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../util/auth_utils.php';

require_post();
$data = read_input();

$username  = sanitize_string($data['username'] ?? null);
$email     = sanitize_string($data['email'] ?? null);
$password  = $data['password'] ?? null; // keep raw for hashing
$user_type = sanitize_string($data['user_type'] ?? null);
$name      = sanitize_string($data['name'] ?? $data['named'] ?? null);
$phone     = sanitize_string($data['phone'] ?? null);

$errors = [];
if (!$username) $errors['username'] = 'Username required';
if ($username && strlen($username) > 50) $errors['username'] = 'Username too long';
if (!$email) $errors['email'] = 'Email required';
elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email';
if (!$password) $errors['password'] = 'Password required';
elseif (strlen($password) < 8) $errors['password'] = 'Minimum 8 characters';
$allowed_types = ['admin','restaurant','customer','donor','needy'];
if (!$user_type) $errors['user_type'] = 'user_type required';
elseif (!validate_enum($user_type, $allowed_types)) $errors['user_type'] = 'Invalid user_type';
// Phone required unless needy (per table constraint logic)
if ($user_type && $user_type !== 'needy' && !$phone) $errors['phone'] = 'Phone required for non-needy users';
if ($phone && strlen($phone) > 20) $errors['phone'] = 'Phone too long';
if ($name && strlen($name) > 100) $errors['name'] = 'Name too long';

if ($errors) {
    json_response(422, ['status' => 'error', 'errors' => $errors]);
}

// Duplicate checks (username/email)
$stmt = $pdo->prepare('SELECT mid, username, email FROM Members WHERE username = ? OR email = ? LIMIT 1');
$stmt->execute([$username, $email]);
$row = $stmt->fetch();
if ($row) {
    $dupeErrors = [];
    if (strcasecmp($row['username'], $username) === 0) $dupeErrors['username'] = 'Username already taken';
    if (strcasecmp($row['email'], $email) === 0) $dupeErrors['email'] = 'Email already registered';
    json_response(409, ['status' => 'error', 'errors' => $dupeErrors]);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$ins = $pdo->prepare('INSERT INTO Members (username,email,pwd,user_type,named,phone) VALUES (?,?,?,?,?,?)');
$ok = $ins->execute([$username, $email, $hash, $user_type, $name, $phone]);
if (!$ok) {
    json_response(500, ['status' => 'error', 'error' => 'Insert failed']);
}
$newId = (int)$pdo->lastInsertId();

json_response(201, [
    'status' => 'success',
    'mid' => $newId,
    'username' => $username,
    'email' => $email,
    'user_type' => $user_type
]);
?>
