
function newPost() {
  alert('Función de nueva publicación - Por implementar');
}

// Funcionalidad básica de búsqueda
document.getElementById('searchInput').addEventListener('input', function (e) {
  const searchTerm = e.target.value.toLowerCase();
  const posts = document.querySelectorAll('.post-card');

  posts.forEach(post => {
    const title = post.querySelector('.post-title').textContent.toLowerCase();
    const content = post.querySelector('.post-content').textContent.toLowerCase();

    if (title.includes(searchTerm) || content.includes(searchTerm)) {
      post.style.display = 'block';
    } else {
      post.style.display = 'none';
    }
  });
});

// Funcionalidad para botones de respuesta
document.querySelectorAll('.reply-btn').forEach(btn => {
  btn.addEventListener('click', function () {
    alert('Función de respuesta - Por implementar');
  });
});