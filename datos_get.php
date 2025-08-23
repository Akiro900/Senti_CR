<?php
// datos_get.php
if (session_status() === PHP_SESSION_NONE) {
  // Asegura cookie válida en todo el sitio
  session_set_cookie_params([
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}

require_once __DIR__ . '/db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

header('Content-Type: application/json; charset=utf-8');

$userId = $_SESSION['usuario_id'] ?? null;
if (!$userId) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'error' => 'NO_AUTH']);
  exit;
}

$stmt = $conn->prepare('SELECT nombre, edad, correo, genero FROM usuarios WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) {
  http_response_code(404);
  echo json_encode(['ok' => false, 'error' => 'NOT_FOUND']);
  exit;
}

echo json_encode(['ok' => true, 'data' => $res], JSON_UNESCAPED_UNICODE);
