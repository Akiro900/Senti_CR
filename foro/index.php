<?php /* foro/index.php */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Foro - Sentí CR</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <header>
    <h1>Sentí CR</h1>
    <h2>Foro de Apoyo Comunitario</h2>
  </header>

  <aside class="notice" role="note">
    Este foro es de apoyo entre pares y no reemplaza atención profesional. Si estás en crisis, busca ayuda inmediata y revisa los <a href="../recursos.html">recursos</a>.
  </aside>

  <main>
    <div id="authBanner" class="notice" style="display:none;">
      Para publicar, responder o dar <em>me gusta</em>, iniciá sesión.
      <a href="../login.html">Iniciar sesión</a>
    </div>

    <div class="forum-controls">
      <div class="search-bar">
        <input type="text" placeholder="Buscar por título, contenido o categoría…" id="searchInput" autocomplete="off">
      </div>
      <button class="new-post-btn" id="btnNewPost" type="button">Nueva Publicación</button>
    </div>

    <section id="postsContainer" aria-live="polite" aria-busy="false"></section>

    <nav id="boton">
      <a href="../index.html">Inicio</a>
      <a href="../recursos.html">Recursos</a>
      <a href="../chat/index.php">Chat de Apoyo</a>
    </nav>

    <div id="volverInicio">
      <a href="../index.html">← Volver al inicio</a>
    </div>
  </main>

  <dialog id="postDialog" aria-labelledby="dlgTitle">
    <form id="postForm" class="dialog-inner" method="dialog" novalidate>
      <div class="dialog-title" id="dlgTitle">Crear nueva publicación</div>
      <div class="dialog-grid">
        <div>
          <label for="category">Categoría</label>
          <select id="category" class="filter-select" required>
            <option value="" selected disabled>Selecciona una categoría</option>
            <option>Ansiedad</option>
            <option>Depresión</option>
            <option>Consejos</option>
            <option>Otros</option>
          </select>
        </div>
        <div>
          <label for="title">Título</label>
          <input id="title" class="text-input" type="text" maxlength="120" placeholder="Escribe un título claro" required>
        </div>
        <div>
          <label for="content">Contenido</label>
          <textarea id="content" class="textarea" rows="6" maxlength="1200" placeholder="Comparte tu experiencia o pregunta" required></textarea>
        </div>
        <label><input type="checkbox" id="anonymous" checked> Publicar como anónimo</label>
      </div>
      <div class="dialog-actions">
        <button type="button" class="btn btn-secondary" id="btnCancel">Cancelar</button>
        <button type="submit" class="btn">Publicar</button>
      </div>
    </form>
  </dialog>

  <template id="postCardTemplate">
    <div class="post-card" data-id="">
      <div class="post-header">
        <div>
          <span class="post-user">Usuario</span>
          <span class="anonymous-badge">Anónimo</span>
        </div>
        <span class="post-date"></span>
      </div>
      <div class="post-title">
        <span class="category-tag"></span>
        <span class="post-title-text"></span>
      </div>
      <div class="post-content"></div>
      <div class="post-footer">
        <div class="post-stats">
          <span class="responses-stat">0 respuestas</span>
          <span class="likes-stat">0 me gusta</span>
        </div>
        <div style="display:flex; gap:8px;">
          <button class="btn-like" type="button" aria-pressed="false">Me gusta</button>
          <button class="reply-btn" type="button">Responder</button>
        </div>
      </div>
      <div class="replies" hidden>
        <div class="reply-list"></div>
        <form class="reply-form">
          <textarea class="textarea reply-text" rows="3" maxlength="600" placeholder="Escribe una respuesta respetuosa…" required></textarea>
          <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:8px;">
            <button type="button" class="btn btn-secondary btn-cancel-reply">Cancelar</button>
            <button type="submit" class="btn">Publicar respuesta</button>
          </div>
        </form>
      </div>
    </div>
  </template>

  <template id="replyItemTemplate">
    <div class="reply">
      <p class="reply-content"></p>
      <small><span class="reply-user">Usuario</span> · <time class="reply-date"></time></small>
    </div>
  </template>

  <script src="script.js"></script>
</body>
</html>
