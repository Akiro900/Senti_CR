const chatMessages = document.getElementById('chatMessages');
const messageInput = document.getElementById('messageInput');

function sendMessage() {
  const message = messageInput.value.trim();
  if (message === '') return;

  // Agregar mensaje del usuario
  addMessage('user', message);
  messageInput.value = '';

  // Simular respuesta después de medio segundo
  setTimeout(() => {
    generateResponse(message);
  }, 500);
}

function sendQuickMessage(message) {
  messageInput.value = message;
  sendMessage();
}

function addMessage(type, content) {
  const messageDiv = document.createElement('div');
  messageDiv.className = `message ${type}`;

  const currentTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

  let avatar = '';
  if (type === 'user') {
    avatar = '<div class="message-avatar">TU</div>';
  } else if (type === 'support') {
    avatar = '<div class="message-avatar">PS</div>';
  }

  messageDiv.innerHTML = `
        ${avatar}
        <div>
          <div class="message-content">${content}</div>
          <div class="message-time">${currentTime}</div>
        </div>
      `;

  chatMessages.appendChild(messageDiv);
  chatMessages.scrollTop = chatMessages.scrollHeight;
}

function generateResponse(userMessage) {
  // Respuestas generales
  let responses = [
    "Gracias por compartir esto conmigo. Es completamente normal sentirse así.",
    "Entiendo lo que me dices. ¿Te gustaría que exploremos algunas técnicas que podrían ayudarte?",
    "Tu bienestar es importante. Hablemos sobre estrategias que podrían ser útiles para ti.",
    "Aprecio tu confianza al compartir esto. ¿Hay algo específico que te gustaría trabajar?",
    "Es valiente de tu parte buscar ayuda. ¿Cómo te has estado cuidando últimamente?"
  ];

  // Respuestas específicas por palabra clave
  if (userMessage.toLowerCase().includes('ansi')) {
    responses = [
      "La ansiedad puede ser muy abrumadora. Una técnica que suele ayudar es la respiración profunda: inhala por 4 segundos, mantén por 4, exhala por 6. ¿Te gustaría que practiquemos juntos?",
      "Entiendo lo difícil que puede ser la ansiedad. ¿Has notado qué situaciones específicas la desencadenan más?",
      "La ansiedad es tratable y hay muchas herramientas que pueden ayudarte. ¿Te interesaría conocer algunas técnicas de relajación?"
    ];
  } else if (userMessage.toLowerCase().includes('estrés')) {
    responses = [
      "El estrés es muy común hoy en día. Una buena forma de manejarlo es dividir tus tareas en pequeños pasos. ¿Qué está causando más estrés en tu vida ahora?",
      "Para el estrés, la actividad física moderada puede ser muy efectiva. ¿Tienes alguna actividad física que disfrutes?",
      "El estrés puede afectar tanto física como emocionalmente. ¿Has notado cómo se manifiesta en tu cuerpo?"
    ];
  } else if (userMessage.toLowerCase().includes('deprimi') || userMessage.toLowerCase().includes('triste')) {
    responses = [
      "Es importante que hayas dado este paso para buscar apoyo. Los sentimientos de tristeza son válidos. ¿Te gustaría hablar sobre qué ha estado pasando?",
      "Gracias por confiar en mí. La tristeza profunda puede ser muy difícil de manejar solo/a. ¿Hay algo que normalmente te ayuda a sentirte mejor, aunque sea un poco?",
      "Tu bienestar emocional es importante. ¿Has podido mantener alguna rutina diaria o actividad que te dé cierta estructura?"
    ];
  }

  const randomResponse = responses[Math.floor(Math.random() * responses.length)];
  addMessage('support', randomResponse);
}

// Permitir envío con Enter
messageInput.addEventListener('keypress', function (event) {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault();
    sendMessage();
  }
});

// Scroll inicial
chatMessages.scrollTop = chatMessages.scrollHeight;