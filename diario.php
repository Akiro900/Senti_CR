<?php
// diario.php
if (session_status() === PHP_SESSION_NONE) {
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: diario.html');
    exit;
}

$userId = $_SESSION['usuario_id'] ?? null;
if (!$userId) {
    header('Location: diario.html?error=' . rawurlencode('No hay usuario en sesión. Inicia sesión para guardar en tu diario.'));
    exit;
}

$chk = $conn->prepare('SELECT 1 FROM usuarios WHERE id = ? LIMIT 1');
$chk->bind_param('i', $userId);
$chk->execute();
if (!$chk->get_result()->fetch_row()) {
    $chk->close();
    header('Location: diario.html?error=' . rawurlencode('El usuario de la sesión no existe en la base de datos.'));
    exit;
}
$chk->close();

$fecha      = $_POST['fecha'] ?? '';
$estadoRaw  = strtolower(trim($_POST['estado'] ?? ''));
$comentario = trim($_POST['comentario'] ?? '');

$validos = ['feliz','triste','ansioso','estresado','neutral'];

if (!$fecha) {
    header('Location: diario.html?error=' . rawurlencode('La fecha es obligatoria.'));
    exit;
}
if (!in_array($estadoRaw, $validos, true)) {
    header('Location: diario.html?error=' . rawurlencode('Estado emocional inválido.'));
    exit;
}

$sql  = 'INSERT INTO diario (usuario_id, fecha, estado, comentario) VALUES (?, ?, ?, ?)';
$stmt = $conn->prepare($sql);
$stmt->bind_param('isss', $userId, $fecha, $estadoRaw, $comentario);
$stmt->execute();
$stmt->close();

header('Location: diario.html?ok=1');
exit;
