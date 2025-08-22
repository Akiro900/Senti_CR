// foro/script.js
const API = {
  me: () => fetch("api/me.php").then(r => r.json()),
  list: (q = "") => fetch(`api/posts.php?q=${encodeURIComponent(q)}`).then(r => r.json()),
  create: (payload) => fetch("api/posts.php", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(payload) }).then(r => r.json()),
  listReplies: (postId) => fetch(`api/replies.php?post_id=${encodeURIComponent(postId)}`).then(r => r.json()),
  addReply: (payload) => fetch("api/replies.php", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(payload) }).then(r => r.json()),
  toggleLike: (postId) => fetch("api/like.php", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ post_id: postId }) }).then(r => r.json()),
};

const LOGIN_URL = "../login.html"; // <-- Ajustá a tu ruta/form de login

// Utils
const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
function timeAgo(iso) {
  const d = new Date(iso), now = new Date();
  const s = Math.floor((now - d) / 1000);
  if (s < 60) return `Hace ${s} s`;
  const m = Math.floor(s / 60);
  if (m < 60) return `Hace ${m} min`;
  const h = Math.floor(m / 60);
  if (h < 24) return `Hace ${h} ${h === 1 ? 'hora' : 'horas'}`;
  const dd = Math.floor(h / 24);
  if (dd < 30) return `Hace ${dd} ${dd === 1 ? 'día' : 'días'}`;
  return d.toLocaleDateString('es-CR', { day: '2-digit', month: 'short', year: 'numeric' });
}
const plural = (n, s, p) => `${n} ${n === 1 ? s : p}`;
function debounce(fn, ms = 200) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; }

// State
let ME = null;

const postsContainer = $("#postsContainer");
const searchInput = $("#searchInput");
const postDialog = $("#postDialog");
const postForm = $("#postForm");
const btnNewPost = $("#btnNewPost");
const btnCancel = $("#btnCancel");
const authBanner = $("#authBanner");

function guardLogged(action) {
  if (!ME) {
    if (confirm("Necesitás iniciar sesión para continuar. ¿Ir a iniciar sesión?")) {
      window.location.href = LOGIN_URL;
    }
    return false;
  }
  return true;
}

function renderPosts(items) {
  postsContainer.innerHTML = "";
  if (!items.length) {
    const empty = document.createElement("div");
    empty.className = "empty";
    empty.textContent = "No hay publicaciones que coincidan con tu búsqueda.";
    postsContainer.appendChild(empty);
    return;
  }
  const tpl = $("#postCardTemplate");

  items.forEach(p => {
    const node = tpl.content.firstElementChild.cloneNode(true);
    node.dataset.id = p.id;

    // Mostrar nombre público tal cual viene de backend
    $(".post-user", node).textContent = p.user_name || "Usuario";
    if (!(p.user_name || "").toLowerCase().includes("anónimo")) {
      const badge = $(".anonymous-badge", node);
      if (badge) badge.style.display = "none";
    }
    const dateEl = $(".post-date", node);
    dateEl.textContent = timeAgo(p.created_at);
    dateEl.dataset.iso = p.created_at;

    $(".category-tag", node).textContent = p.category;
    $(".post-title-text", node).textContent = p.title;
    $(".post-content", node).textContent = p.content;

    $(".responses-stat", node).textContent = plural(Number(p.replies_count || 0), "respuesta", "respuestas");
    $(".likes-stat", node).textContent = plural(Number(p.likes_count || 0), "me gusta", "me gusta");

    // Like
    const btnLike = $(".btn-like", node);
    btnLike.setAttribute("aria-pressed", p.liked ? "true" : "false");
    btnLike.addEventListener("click", async () => {
      if (!guardLogged()) return;
      try {
        const res = await API.toggleLike(p.id);
        if (!res.ok) throw new Error(res.error || "Error");
        btnLike.setAttribute("aria-pressed", res.data.liked ? "true" : "false");
        $(".likes-stat", node).textContent = plural(Number(res.data.likes_count || 0), "me gusta", "me gusta");
      } catch (e) {
        alert("No se pudo actualizar el Me gusta");
      }
    });

    // Respuestas
    const repliesWrap = $(".replies", node);
    const replyList = $(".reply-list", node);
    const replyBtn = $(".reply-btn", node);
    const replyForm = $(".reply-form", node);
    const replyText = $(".reply-text", node);
    const btnCancelReply = $(".btn-cancel-reply", node);

    let repliesLoaded = false;
    replyBtn.addEventListener("click", async () => {
      if (!guardLogged()) return;
      const hidden = repliesWrap.hasAttribute("hidden");
      if (hidden) {
        repliesWrap.removeAttribute("hidden");
        replyText.focus();
        if (!repliesLoaded) {
          try {
            const res = await API.listReplies(p.id);
            if (res.ok) {
              res.data.forEach(r => replyList.appendChild(renderReply(r)));
              repliesLoaded = true;
            }
          } catch {}
        }
      } else {
        repliesWrap.setAttribute("hidden", "");
      }
    });

    replyForm.addEventListener("submit", async (ev) => {
      ev.preventDefault();
      if (!guardLogged()) return;
      const text = replyText.value.trim();
      if (!text) return;
      try {
        const res = await API.addReply({ post_id: p.id, content: text });
        if (!res.ok) throw new Error(res.error || "Error");
        replyText.value = "";
        replyList.appendChild(renderReply(res.data));
        const n = Number(p.replies_count || 0) + 1;
        p.replies_count = n;
        $(".responses-stat", node).textContent = plural(n, "respuesta", "respuestas");
      } catch {
        alert("No se pudo publicar la respuesta");
      }
    });

    btnCancelReply.addEventListener("click", () => {
      repliesWrap.setAttribute("hidden", "");
      replyForm.reset();
    });

    postsContainer.appendChild(node);
  });
}

function renderReply(r) {
  const tpl = $("#replyItemTemplate");
  const node = tpl.content.firstElementChild.cloneNode(true);
  $(".reply-content", node).textContent = r.content;
  $(".reply-user", node).textContent = r.user_name || "Usuario";
  const t = $(".reply-date", node);
  t.textContent = timeAgo(r.created_at);
  t.dataset.iso = r.created_at;
  return node;
}

async function load(q = "") {
  postsContainer.setAttribute("aria-busy", "true");
  try {
    const res = await API.list(q);
    renderPosts(res.ok ? res.data : []);
  } catch {
    postsContainer.innerHTML = `<div class="empty">No se pudieron cargar las publicaciones.</div>`;
  } finally {
    postsContainer.removeAttribute("aria-busy");
  }
}

btnNewPost.addEventListener("click", () => {
  if (!guardLogged()) return;
  postDialog.showModal();
});
btnCancel.addEventListener("click", () => { postDialog.close(); postForm.reset(); });

postForm.addEventListener("submit", async (ev) => {
  ev.preventDefault();
  if (!guardLogged()) return;
  const category = $("#category").value;
  const title = $("#title").value.trim();
  const content = $("#content").value.trim();
  const anonymous = $("#anonymous").checked;

  if (!category || !title || !content) return;

  try {
    const res = await API.create({ category, title, content, anonymous });
    if (!res.ok) throw new Error(res.error || "Error");
    postDialog.close();
    postForm.reset();
    await load(searchInput.value);
  } catch {
    alert("No se pudo crear la publicación");
  }
});

searchInput.addEventListener("input", debounce(() => load(searchInput.value), 150));

// Actualizar tiempos cada minuto
setInterval(() => {
  $$(".post-date, .reply-date").forEach(el => {
    const iso = el.dataset.iso;
    if (iso) el.textContent = timeAgo(iso);
  });
}, 60000);

// Init: detectar sesión y cargar posts
(async () => {
  const me = await API.me();
  ME = me.ok ? me.data : null;
  authBanner.style.display = ME ? "none" : "block";
  await load();
})();
