<?php
session_start();
header('Content-Type: application/json');
if (!empty($_SESSION['mid'])) {
  echo json_encode([
    'status'=>'success',
    'mid'=>$_SESSION['mid'],
    'user_type'=>$_SESSION['user_type'] ?? null
  ]);
} else {
  http_response_code(401);
  echo json_encode(['status'=>'error','error'=>'Not authenticated']);
}
