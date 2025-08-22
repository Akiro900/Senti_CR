<?php
// foro/api/posts.php
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Costa_Rica');

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/_auth.php';

function json_ok($data){ echo json_encode(['ok'=>true,'data'=>$data], JSON_UNESCAPED_UNICODE); exit; }
function json_err($msg,$code=400){ http_response_code($code); echo json_encode(['ok'=>false,'error'=>$msg], JSON_UNESCAPED_UNICODE); exit; }

$method = $_SERVER['REQUEST_METHOD'];
$user = current_user();
$userId = $user['id'];

if ($method === 'GET') {
  $q = isset($_GET['q']) ? trim($_GET['q']) : '';
  $like = '%'.$q.'%';

  if ($q !== '') {
    if ($userId) {
      $stmt = $conn->prepare("
        SELECT p.id, p.user_id, p.user_name, p.anonymous, p.category, p.title, p.content, p.created_at,
               (SELECT COUNT(*) FROM replies r WHERE r.post_id = p.id) AS replies_count,
               (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.id) AS likes_count,
               EXISTS(SELECT 1 FROM post_likes pl WHERE pl.post_id = p.id AND pl.user_id = ?) AS liked
        FROM posts p
        WHERE p.title LIKE ? OR p.content LIKE ? OR p.category LIKE ?
        ORDER BY p.created_at DESC
      ");
      $stmt->bind_param('isss', $userId, $like, $like, $like);
    } else {
      $stmt = $conn->prepare("
        SELECT p.id, p.user_id, p.user_name, p.anonymous, p.category, p.title, p.content, p.created_at,
               (SELECT COUNT(*) FROM replies r WHERE r.post_id = p.id) AS replies_count,
               (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.id) AS likes_count,
               0 AS liked
        FROM posts p
        WHERE p.title LIKE ? OR p.content LIKE ? OR p.category LIKE ?
        ORDER BY p.created_at DESC
      ");
      $stmt->bind_param('sss', $like, $like, $like);
    }
  } else {
    if ($userId) {
      $stmt = $conn->prepare("
        SELECT p.id, p.user_id, p.user_name, p.anonymous, p.category, p.title, p.content, p.created_at,
               (SELECT COUNT(*) FROM replies r WHERE r.post_id = p.id) AS replies_count,
               (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.id) AS likes_count,
               EXISTS(SELECT 1 FROM post_likes pl WHERE pl.post_id = p.id AND pl.user_id = ?) AS liked
        FROM posts p
        ORDER BY p.created_at DESC
      ");
      $stmt->bind_param('i', $userId);
    } else {
      $stmt = $conn->prepare("
        SELECT p.id, p.user_id, p.user_name, p.anonymous, p.category, p.title, p.content, p.created_at,
               (SELECT COUNT(*) FROM replies r WHERE r.post_id = p.id) AS replies_count,
               (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.id) AS likes_count,
               0 AS liked
        FROM posts p
        ORDER BY p.created_at DESC
      ");
    }
  }

  if (!$stmt->execute()) json_err('Error al consultar publicaciones', 500);
  $res = $stmt->get_result();
  $rows = [];
  while ($row = $res->fetch_assoc()) { $rows[] = $row; }
  json_ok($rows);
}

if ($method === 'POST') {
  require_login_json();
  $input = json_decode(file_get_contents('php://input'), true);

  $category  = trim($input['category'] ?? '');
  $title     = trim($input['title'] ?? '');
  $content   = trim($input['content'] ?? '');
  $anonymous = !empty($input['anonymous']) ? 1 : 0;

  if ($category === '' || $title === '' || $content === '') json_err('Faltan campos requeridos');

  $allowed = ['Ansiedad','Depresión','Consejos','Otros'];
  if (!in_array($category, $allowed)) $category = 'Otros';

  // Nombre público: real u anónimo
  if ($anonymous) {
    $rand = random_int(1000, 9999);
    $public_name = "Usuario Anónimo #$rand";
  } else {
    $public_name = $user['nombre'] ?: 'Usuario';
  }

  $stmt = $conn->prepare("INSERT INTO posts (user_id, user_name, anonymous, category, title, content) VALUES (?, ?, ?, ?, ?, ?)");
  $stmt->bind_param('isisss', $userId, $public_name, $anonymous, $category, $title, $content);
  if (!$stmt->execute()) json_err('No se pudo crear la publicación', 500);

  $id = $stmt->insert_id;
  $get = $conn->prepare("
    SELECT p.id, p.user_id, p.user_name, p.anonymous, p.category, p.title, p.content, p.created_at,
           0 AS replies_count, 0 AS likes_count, 0 AS liked
    FROM posts p WHERE p.id = ?
  ");
  $get->bind_param('i', $id);
  $get->execute();
  $row = $get->get_result()->fetch_assoc();
  json_ok($row);
}

json_err('Método no soportado', 405);