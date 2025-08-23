<?php
// datos.php
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
  header('Location: datos.html');
  exit;
}

$userId = $_SESSION['usuario_id'] ?? null;
if (!$userId) {
  header('Location: datos.html?error=' . rawurlencode('No hay usuario en sesión.'));
  exit;
}

$nombre = trim($_POST['nombre'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$edad   = (isset($_POST['edad']) && $_POST['edad'] !== '') ? (int)$_POST['edad'] : null;
$genero_raw = strtolower(trim($_POST['genero'] ?? ''));
$genero = in_array($genero_raw, ['masculino','femenino','otro'], true) ? $genero_raw : null;

if ($nombre === '') {
  header('Location: datos.html?error=' . rawurlencode('El nombre es obligatorio.'));
  exit;
}
if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
  header('Location: datos.html?error=' . rawurlencode('Correo inválido.'));
  exit;
}
if ($edad !== null && ($edad < 10 || $edad > 120)) {
  header('Location: datos.html?error=' . rawurlencode('La edad debe estar entre 10 y 120.'));
  exit;
}

try {
  $sql = 'UPDATE usuarios SET nombre = ?, edad = ?, correo = ?, genero = ? WHERE id = ?';
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('sissi', $nombre, $edad, $correo, $genero, $userId);
  $stmt->execute();
  $stmt->close();

  header('Location: datos.html?ok=1');
  exit;
} catch (mysqli_sql_exception $e) {
  if ($e->getCode() === 1062) {
    header('Location: datos.html?error=' . rawurlencode('Ese correo ya está registrado.'));
  } else {
    header('Location: datos.html?error=' . rawurlencode('Error al actualizar: ' . $e->getMessage()));
  }
  exit;
}
