// Comportamiento del sitio público: evita doble envío del formulario de comentarios.
document.addEventListener('DOMContentLoaded', function () {
    var commentForm = document.getElementById('comment-form');
    if (commentForm) {
        commentForm.addEventListener('submit', function () {
            var btn = commentForm.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Enviando...';
            }
        });
    }
});
