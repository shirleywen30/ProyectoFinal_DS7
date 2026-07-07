// Confirmaciones de acciones sensibles y previsualización de imágenes.
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('submit', function (e) {
            if (!confirm(el.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    var fileInput = document.querySelector('input[name="imagenes[]"]');
    var preview = document.getElementById('image-preview');

    if (fileInput && preview) {
        fileInput.addEventListener('change', function () {
            preview.innerHTML = '';
            Array.from(fileInput.files).forEach(function (file) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    var img = document.createElement('img');
                    img.src = e.target.result;
                    preview.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        });
    }
});
