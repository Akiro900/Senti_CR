<?php
// foro/api/like.php
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Costa_Rica');

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/_auth.php';

function json_ok($data){ echo json_encode(['ok'=>true,'data'=>$data], JSON_UNESCAPED_UNICODE); exit; }
function json_err($msg,$code=400){ http_response_code($code); echo json_encode(['ok'=>false,'error'=>$msg], JSON_UNESCAPED_UNICODE); exit; }

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') json_err('Método no soportado', 405);

require_login_json();
$user = current_user();
$userId = (int)$user['id'];

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) $input = $_POST;

$postId = isset($input['post_id']) ? (int)$input['post_id'] : 0;
if ($postId <= 0) json_err('post_id inválido', 422);
$stmt = $conn->prepare('SELECT id FROM posts WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $postId);
$stmt->execute();
$res = $stmt->get_result();
if (!$res->fetch_assoc()) json_err('La publicación no existe', 404);
try {
    $conn->begin_transaction();
    $stmt = $conn->prepare('SELECT id FROM post_likes WHERE post_id = ? AND user_id = ? LIMIT 1');
    $stmt->bind_param('ii', $postId, $userId);
    $stmt->execute();
    $like = $stmt->get_result()->fetch_assoc();
    if ($like) {
        $stmt = $conn->prepare('DELETE FROM post_likes WHERE id = ?');
        $stmt->bind_param('i', $like['id']);
        $stmt->execute();
        $liked = 0;
    } else {
        $stmt = $conn->prepare('INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)');
        $stmt->bind_param('ii', $postId, $userId);
        $stmt->execute();
        $liked = 1;
    }
    $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM post_likes WHERE post_id = ?');
    $stmt->bind_param('i', $postId);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_assoc()['c'];

    $conn->commit();

    json_ok([
        'post_id'     => $postId,
        'liked'       => $liked,
        'likes_count' => $count
    ]);
} catch (Throwable $e) {
    if ($conn->errno) { /* noop */ }
    if ($conn->begin_transaction) { /* noop */ }
    $conn->rollback();
    json_err('No se pudo actualizar el me gusta', 500);
}
