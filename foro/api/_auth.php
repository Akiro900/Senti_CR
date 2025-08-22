<?php
// foro/api/_auth.php
session_start();

function current_user() {
  return [
    'id' => $_SESSION['usuario_id'] ?? null,
    'nombre' => $_SESSION['nombre'] ?? null,
  ];
}

function require_login_json() {
  if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'NO_AUTH']);
    exit;
  }
}