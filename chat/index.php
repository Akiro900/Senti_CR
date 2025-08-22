<?php
date_default_timezone_set('America/Costa_Rica');

require_once __DIR__ . '/api/_auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$user   = current_user();
$userId = isset($user['id']) ? (int)$user['id'] : null;

$action = $_GET['action'] ?? null;
if ($method === 'POST' && !$action) {
  $raw = file_get_contents('php://input');
  $tmp = json_decode($raw, true);
  if (is_array($tmp) && isset($tmp['action'])) $action = $tmp['action'];
}

if ($action) {
  require_once __DIR__ . '/../db.php';
}

function json_ok($data)
{
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
  exit;
}
function json_err($msg, $code = 400)
{
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}


function is_agent(mysqli $conn, ?int $userId): bool
{
  if (!$userId) return false;
  $stmt = $conn->prepare("SELECT 1 FROM chat_agents WHERE user_id=?");
  $stmt->bind_param('i', $userId);
  $stmt->execute();
  return (bool)$stmt->get_result()->fetch_row();
}

function get_session(mysqli $conn, int $sessionId)
{
  $stmt = $conn->prepare("SELECT id, user_id, agent_id, specialist_name, status, started_at, closed_at FROM chat_sessions WHERE id=?");
  $stmt->bind_param('i', $sessionId);
  $stmt->execute();
  return $stmt->get_result()->fetch_assoc();
}

function create_session(mysqli $conn, ?int $userId)
{
  $agent = pick_agent($conn);
  $spec  = $agent ? ($agent['name'] ?: 'Especialista') : 'Especialista';

  if ($agent) {
    $stmt = $conn->prepare("INSERT INTO chat_sessions (user_id, agent_id, specialist_name) VALUES (?, ?, ?)");
    $uid  = $userId ?: NULL;
    $aid  = (int)$agent['id'];
    $stmt->bind_param('iis', $uid, $aid, $spec);
  } else {
    $stmt = $conn->prepare("INSERT INTO chat_sessions (user_id, specialist_name) VALUES (?, ?)");
    $uid  = $userId ?: NULL;
    $stmt->bind_param('is', $uid, $spec);
  }

  if (!$stmt->execute()) json_err('No se pudo crear la sesión', 500);
  $sessionId = $stmt->insert_id;

  $sys = "Bienvenido/a al chat de apoyo emocional de Sentí CR. Aquí puedes hablar de forma confidencial con nuestros especialistas.";
  $msg = $conn->prepare("INSERT INTO chat_messages (session_id, sender, content) VALUES (?, 'system', ?)");
  $msg->bind_param('is', $sessionId, $sys);
  $msg->execute();

  return get_session($conn, $sessionId);
}

function fetch_messages(mysqli $conn, int $sessionId, int $afterId = 0, int $limit = 1000)
{
  $sql = "SELECT id, sender, content, created_at FROM chat_messages WHERE session_id=? AND id>? ORDER BY id ASC LIMIT ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('iii', $sessionId, $afterId, $limit);
  $stmt->execute();
  $res = $stmt->get_result();
  $rows = [];
  while ($r = $res->fetch_assoc()) $rows[] = $r;
  return $rows;
}

function add_message(mysqli $conn, int $sessionId, string $sender, string $content)
{
  $stmt = $conn->prepare("INSERT INTO chat_messages (session_id, sender, content) VALUES (?, ?, ?)");
  $stmt->bind_param('iss', $sessionId, $sender, $content);
  if (!$stmt->execute()) json_err('No se pudo guardar el mensaje', 500);
  $id = $stmt->insert_id;

  $sel = $conn->prepare("SELECT id, sender, content, created_at FROM chat_messages WHERE id=?");
  $sel->bind_param('i', $id);
  $sel->execute();
  return $sel->get_result()->fetch_assoc();
}

function pick_agent(mysqli $conn): ?array
{
  $sql = "SELECT a.user_id AS id, COALESCE(u.nombre, 'Especialista') AS name
          FROM chat_agents a
          LEFT JOIN usuarios u ON u.id=a.user_id
          LEFT JOIN (
            SELECT agent_id, COUNT(*) cnt
            FROM chat_sessions
            WHERE status='open' AND agent_id IS NOT NULL
            GROUP BY agent_id
          ) s ON s.agent_id=a.user_id
          ORDER BY COALESCE(s.cnt,0) ASC, a.added_at ASC
          LIMIT 1";
  $res = $conn->query($sql);
  return ($res && $res->num_rows) ? $res->fetch_assoc() : null;
}

function can_access(array $session, ?int $userId): bool
{
  // Invitado con sesión invitada:
  if (!$userId && !$session['user_id']) return true;
  if ($userId && (int)$session['user_id'] === (int)$userId) return true;
  if ($userId && !empty($session['agent_id']) && (int)$session['agent_id'] === (int)$userId) return true;
  return false;
}

// -------------------- SSE --------------------
function sse_headers()
{
  header('Content-Type: text/event-stream');
  header('Cache-Control: no-cache, no-transform');
  header('X-Accel-Buffering: no'); // Nginx
  @ini_set('output_buffering', 'off');
  @ini_set('zlib.output_compression', 0);
  while (ob_get_level() > 0) {
    ob_end_flush();
  }
  ob_implicit_flush(1);
}
function sse_send($event, $data)
{
  echo "event: {$event}\n";
  echo "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
  @flush();
}

// -------------------- Router --------------------
$method = $_SERVER['REQUEST_METHOD'];
$user = current_user();
$userId = isset($user['id']) ? (int)$user['id'] : null;
if (session_status() === PHP_SESSION_ACTIVE) {
  session_write_close();
}

$action = $_GET['action'] ?? null;
if ($method === 'POST' && !$action) {
  $raw = file_get_contents('php://input');
  $tmp = json_decode($raw, true);
  if (is_array($tmp) && isset($tmp['action'])) $action = $tmp['action'];
}

if ($method === 'GET' && $action === 'init') {
  $sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
  $session   = null;

  if ($sessionId > 0) {
    $session = get_session($conn, $sessionId);
    if (!$session) {
      $sessionId = 0;
    }
  }

  if ($sessionId === 0) {
    if ($userId && is_agent($conn, $userId)) {
      $stmt = $conn->prepare("SELECT id FROM chat_sessions WHERE status='open' AND agent_id=? ORDER BY started_at DESC LIMIT 1");
      $stmt->bind_param('i', $userId);
      $stmt->execute();
      $row = $stmt->get_result()->fetch_assoc();

      if ($row) {
        $session = get_session($conn, (int)$row['id']);
      } else {
        $q = $conn->query("SELECT id FROM chat_sessions WHERE status='open' AND agent_id IS NULL ORDER BY started_at ASC LIMIT 1");
        $row2 = $q ? $q->fetch_assoc() : null;

        if ($row2) {
          $agentName = $user['nombre'] ?? 'Especialista';
          $sid = (int)$row2['id'];
          $upd = $conn->prepare("UPDATE chat_sessions SET agent_id=?, specialist_name=? WHERE id=? AND status='open' AND agent_id IS NULL");
          $upd->bind_param('isi', $userId, $agentName, $sid);
          $upd->execute();
          $session = get_session($conn, $sid);
        } else {
          json_ok(['session' => null, 'messages' => []]);
        }
      }
    } else {
      if ($userId) {
        $stmt = $conn->prepare("SELECT id FROM chat_sessions WHERE user_id=? AND status='open' ORDER BY started_at DESC LIMIT 1");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $session = $row ? get_session($conn, (int)$row['id']) : create_session($conn, $userId);
      } else {
        $session = create_session($conn, null);
      }
    }
  } else {
    if ($userId && is_agent($conn, $userId) && $session['status'] === 'open' && empty($session['agent_id'])) {
      $agentName = $user['nombre'] ?? 'Especialista';
      $upd = $conn->prepare("UPDATE chat_sessions SET agent_id=?, specialist_name=? WHERE id=? AND status='open' AND agent_id IS NULL");
      $upd->bind_param('isi', $userId, $agentName, $session['id']);
      $upd->execute();
      $session = get_session($conn, (int)$session['id']);
    }
  }

  if ($session && !can_access($session, $userId)) json_err('No autorizado', 403);

  $msgs = $session ? fetch_messages($conn, (int)$session['id'], 0, 200) : [];


  json_ok([
    'session' => $session,
    'messages' => $msgs,
    'viewer' => ['is_agent' => ($userId ? is_agent($conn, $userId) : false)]
  ]);
}

if ($method === 'GET' && $action === 'stream') {
  if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
  }
  $sessionId = (int)($_GET['session_id'] ?? 0);
  $afterId   = (int)($_GET['after_id'] ?? 0);
  if (!$sessionId) {
    http_response_code(400);
    exit;
  }
  if (!get_session($conn, $sessionId)) {
    http_response_code(404);
    exit;
  }

  ignore_user_abort(true);
  set_time_limit(0);
  sse_headers();

  $start = time();
  $lastId = $afterId;
  $tick = 0;

  $session = get_session($conn, $sessionId);
  if (!$session) {
    http_response_code(404);
    exit;
  }
  if (!can_access($session, $userId)) {
    http_response_code(403);
    exit;
  }
  while (!connection_aborted() && (time() - $start) < 300) {
    $new = fetch_messages($conn, $sessionId, $lastId, 100);
    if (!empty($new)) {
      foreach ($new as $m) {
        sse_send('message', $m);
        $lastId = $m['id'];
      }
      $tick = 0;
    } else {
      if ($tick >= 15) {
        sse_send('ping', ['t' => date('H:i:s')]);
        $tick = 0;
      }
    }
    $tick++;
    usleep(500000); // 0.8s
  }
  exit;
}

// API: POST send / close
if ($method === 'POST' && in_array($action, ['send', 'close'], true)) {
  $input = json_decode(file_get_contents('php://input'), true) ?: [];

  if ($action === 'send') {
    $sessionId = (int)($input['session_id'] ?? 0);
    $text = trim($input['message'] ?? '');
    if (!$sessionId) json_err('session_id requerido');
    if ($text === '') json_err('Mensaje vacío');
    if (mb_strlen($text, 'UTF-8') > 5000) json_err('Mensaje muy largo (máx 5000)');

    $session = get_session($conn, $sessionId);
    if (!can_access($session, $userId)) json_err('No autorizado', 403);
    if (!$session) json_err('Sesión no encontrada', 404);
    if ($session['status'] !== 'open') json_err('La sesión está cerrada', 409);

    $sender = is_agent($conn, $userId) ? 'support' : 'user';
    $msg = add_message($conn, $sessionId, $sender, $text);
    json_ok(['message' => $msg]);
  }

  if ($action === 'close') {
    $sessionId = (int)($input['session_id'] ?? 0);
    if (!$sessionId) json_err('session_id requerido');
    $stmt = $conn->prepare("UPDATE chat_sessions SET status='closed', closed_at=CURRENT_TIMESTAMP WHERE id=? AND status='open'");
    $stmt->bind_param('i', $sessionId);
    $stmt->execute();
    json_ok(['closed' => true]);
  }
}

// -------------------- UI (HTML) --------------------
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>Chat de Apoyo - Sentí CR</title>
  <link rel="stylesheet" href="styles.css">
</head>

<body>
  <header>
    <h1>Sentí CR</h1>
    <h2>Chat de Apoyo Emocional</h2>
  </header>

  <div class="emergency-banner">
    🚨 En caso de emergencia, llama al 911 o contacta la
    <a href="tel:+50625479595">Línea Nacional de Prevención del Suicidio: 2547-9595</a>
  </div>

  <main>
    <div class="chat-header">
      <div class="chat-status">
        <div class="status-dot"></div>
        <span>Especialista en línea</span>
      </div>
      <div><span>Sesión segura y confidencial</span></div>
    </div>

    <div class="chat-container">
      <div class="chat-messages" id="chatMessages"></div>

      <div class="chat-input-container">
        <div class="chat-options">
          <div class="quick-option" onclick="sendQuickMessage('Me siento ansioso')">Me siento ansioso</div>
          <div class="quick-option" onclick="sendQuickMessage('Necesito hablar con alguien')">Necesito hablar</div>
          <div class="quick-option" onclick="sendQuickMessage('¿Cómo manejar el estrés?')">Manejo del estrés</div>
          <div class="quick-option" onclick="sendQuickMessage('Técnicas de relajación')">Técnicas de relajación</div>
        </div>

        <div class="chat-input-form">
          <textarea class="chat-input" id="messageInput" placeholder="Escribe tu mensaje aquí..." rows="1"></textarea>
          <button class="send-button" onclick="sendMessage()" id="sendButton">Enviar</button>
        </div>
      </div>
    </div>
  </main>

  <nav id="boton">
    <a href="../index.html">Inicio</a>
    <a href="../foro/index.php">Foro</a>
    <a href="../recursos.html">Recursos</a>
  </nav>

  <div id="volverInicio">
    <a href="../index.html">← Volver al inicio</a>
  </div>

  <script src="script.js"></script>
</body>

</html>