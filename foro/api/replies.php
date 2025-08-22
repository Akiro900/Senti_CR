<?php
// foro/api/replies.php
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Costa_Rica');

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/_auth.php';

function json_ok($data){ echo json_encode(['ok'=>true,'data'=>$data], JSON_UNESCAPED_UNICODE); exit; }
function json_err($msg,$code=400){ http_response_code($code); echo json_encode(['ok'=>false,'error'=>$msg], JSON_UNESCAPED_UNICODE); exit; }

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  $post_id = intval($_GET['post_id'] ?? 0);
  if ($post_id <= 0) json_err('post_id inválido');

  $stmt = $conn->prepare("SELECT id, post_id, user_id, user_name, content, created_at FROM replies WHERE post_id = ? ORDER BY created_at ASC, id ASC");
  $stmt->bind_param('i', $post_id);
  if (!$stmt->execute()) json_err('Error al obtener respuestas', 500);

  $res = $stmt->get_result();
  $rows = [];
  while ($row = $res->fetch_assoc()) { $rows[] = $row; }
  json_ok($rows);
}

if ($method === 'POST') {
  require_login_json();
  $user = current_user();

  $input = json_decode(file_get_contents('php://input'), true);
  $post_id = intval($input['post_id'] ?? 0);
  $content = trim($input['content'] ?? '');

  if ($post_id <= 0 || $content === '') json_err('Faltan campos requeridos');

  // valida existencia del post
  $chk = $conn->prepare("SELECT id FROM posts WHERE id = ?");
  $chk->bind_param('i', $post_id);
  $chk->execute();
  if (!$chk->get_result()->fetch_assoc()) json_err('Publicación no encontrada', 404);

  $public_name = $user['nombre'] ?: 'Usuario';

  $stmt = $conn->prepare("INSERT INTO replies (post_id, user_id, user_name, content) VALUES (?, ?, ?, ?)");
  $stmt->bind_param('iiss', $post_id, $user['id'], $public_name, $content);
  if (!$stmt->execute()) json_err('No se pudo agregar la respuesta', 500);

  $id = $stmt->insert_id;
  $get = $conn->prepare("SELECT id, post_id, user_id, user_name, content, created_at FROM replies WHERE id = ?");
  $get->bind_param('i', $id);
  $get->execute();
  $row = $get->get_result()->fetch_assoc();
  json_ok($row);
}

json_err('Método no soportado', 405);