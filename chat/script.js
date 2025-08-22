const chatMessages = document.getElementById('chatMessages');
const messageInput = document.getElementById('messageInput');
const sendButton = document.getElementById('sendButton');

// Usar endpoint del MISMO archivo PHP
const API = 'index.php';
const LS_SESSION = 'senti_cr_chat_session';

let SESSION_ID = null;
let lastId = 0;
let es = null;

function escapeHtml(s) { return s.replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m])); }
function formatTime(ts) { try { const d = new Date(ts.replace(' ', 'T')); return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); } catch { return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); } }

function addMessage(type, content, created_at = null, id = null) {
  if (id && document.querySelector(`[data-mid="${id}"]`)) return;
  const wrap = document.createElement('div');
  wrap.className = `message ${type}`;
  if (id) wrap.dataset.mid = id;
  const time = created_at ? formatTime(created_at) : new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  let avatar = '';
  if (type === 'user') avatar = '<div class="message-avatar">TU</div>';
  if (type === 'support') avatar = '<div class="message-avatar">PS</div>';
  if (type === 'system') avatar = '';
  wrap.innerHTML = `
    ${avatar}
    <div>
      <div class="message-content">${escapeHtml(content)}</div>
      <div class="message-time">${time}</div>
    </div>`;
  chatMessages.appendChild(wrap);
  chatMessages.scrollTop = chatMessages.scrollHeight;
}

function setTyping(on) {
  let tip = document.getElementById('typingBubble');
  if (on) {
    if (!tip) {
      tip = document.createElement('div');
      tip.id = 'typingBubble';
      tip.className = 'message support typing';
      tip.innerHTML = `
        <div class="message-avatar">PS</div>
        <div>
          <div class="message-content"><span class="dots"><i></i><i></i><i></i></span></div>
          <div class="message-time">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
        </div>`;
      chatMessages.appendChild(tip);
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }
  } else if (tip) tip.remove();
}

async function initSession() {
  try {
    const existing = localStorage.getItem(LS_SESSION);
    const url = existing ? `${API}?action=init&session_id=${encodeURIComponent(existing)}` : `${API}?action=init`;
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    const data = await res.json();

    if (!data.ok) {
      if (res.status === 404 && existing) {
        localStorage.removeItem(LS_SESSION);
        return initSession();
      }
      throw new Error(data.error || 'Error al iniciar');
    }


    const isAgent = !!(data?.data?.viewer && data.data.viewer.is_agent);
    document.body.classList.toggle('agent-view', isAgent);

    const session = data.data.session;
    const msgs = data.data.messages || [];

    SESSION_ID = session ? session.id : null;
    if (SESSION_ID) localStorage.setItem(LS_SESSION, SESSION_ID);

    chatMessages.innerHTML = '';
    msgs.forEach(m => { addMessage(m.sender, m.content, m.created_at, m.id); lastId = Math.max(lastId, m.id); });

    if (SESSION_ID) connectStream();
    else addMessage('system', 'No hay conversaciones asignadas por el momento.');
  } catch (e) {
    console.error(e);
    addMessage('system', 'No se pudo iniciar el chat. Reintenta.');
  }
}

function connectStream() {
  if (!SESSION_ID) return;
  if (es) { es.close(); es = null; }
  es = new EventSource(`${API}?action=stream&session_id=${encodeURIComponent(SESSION_ID)}&after_id=${encodeURIComponent(lastId)}`);
  es.addEventListener('message', (ev) => {
    try {
      const m = JSON.parse(ev.data);
      lastId = Math.max(lastId, m.id);
      addMessage(m.sender, m.content, m.created_at, m.id);
      setTyping(false);
    } catch (e) { console.warn('msg parse', e); }
  });
  es.addEventListener('ping', () => { });
  es.onerror = () => { /* el navegador reintenta solo */ };
}

async function sendMessage() {
  const text = messageInput.value.trim();
  if (!text) return;
  messageInput.value = '';
  messageInput.style.height = '40px';

  setTyping(true);
  sendButton.disabled = true;
  try {
    const res = await fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ action: 'send', session_id: SESSION_ID, message: text })
    });
    const data = await res.json();
    if (!data.ok) throw new Error(data.error || 'Error al enviar');

  } catch (e) {
    console.error(e);
    addMessage('system', 'No se pudo enviar el mensaje. Revisa tu conexión.');
  } finally {
    setTyping(false);
    sendButton.disabled = false;
  }
}

function sendQuickMessage(text) { messageInput.value = text; sendMessage(); }

// auto-resize
messageInput.addEventListener('input', () => {
  messageInput.style.height = '40px';
  messageInput.style.height = Math.min(messageInput.scrollHeight, 100) + 'px';
});
// Enter para enviar
messageInput.addEventListener('keypress', (e) => {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});

// Exponer a HTML
window.sendMessage = sendMessage;
window.sendQuickMessage = sendQuickMessage;

// Boot
initSession();
