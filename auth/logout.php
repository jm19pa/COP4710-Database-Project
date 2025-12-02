<?php
require_once __DIR__ . '/../util/auth_utils.php';
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }
// Allow GET or POST for logout to make it easy to trigger from browser
if (!in_array($_SERVER['REQUEST_METHOD'] ?? '', ['GET','POST'], true)) {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'error' => 'Method Not Allowed']);
    exit;
}

session_start();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']
    );
}
session_destroy();

// If this was called via fetch/AJAX, return JSON; otherwise redirect.
$accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
$isAjax = (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest')
          || str_contains($accept, 'application/json')
          || isset($_REQUEST['ajax']);

if ($isAjax) {
    json_response(200, ['status' => 'success']);
    exit;
}

header('Location: /index.html', true, 302);
exit;
?>