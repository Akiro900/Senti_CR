<?php
// foro/api/me.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/_auth.php';

$user = current_user();
if ($user['id']) {
  echo json_encode(['ok' => true, 'data' => $user], JSON_UNESCAPED_UNICODE);
} else {
  echo json_encode(['ok' => true, 'data' => null], JSON_UNESCAPED_UNICODE);
}