<?php
// Minimal HS256 JWT utilities
const JWT_SECRET = 'change_this_secret_to_a_long_random_value';

function b64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function issue_jwt(array $claims, int $ttlSeconds = 3600): string {
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $now = time();
    $payload = array_merge($claims, ['iat' => $now, 'exp' => $now + $ttlSeconds]);
    $h = b64url_encode(json_encode($header));
    $p = b64url_encode(json_encode($payload));
    $sig = hash_hmac('sha256', "$h.$p", JWT_SECRET, true);
    $s = b64url_encode($sig);
    return "$h.$p.$s";
}
