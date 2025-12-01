<?php
function require_post(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'error' => 'Method Not Allowed']);
        exit;
    }
}

function read_input(): array {
    // Supports form data or raw JSON
    $data = $_POST;
    if (empty($data)) {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $data = $json;
        }
    }
    return is_array($data) ? $data : [];
}

function sanitize_string(?string $v): ?string {
    if ($v === null) return null;
    $v = trim($v);
    return $v === '' ? null : $v;
}

function json_response(int $code, $payload): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function validate_enum(string $value, array $allowed): bool {
    return in_array($value, $allowed, true);
}
