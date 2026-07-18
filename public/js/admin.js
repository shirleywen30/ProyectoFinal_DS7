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
        // Cada vez que se abre el selector nativo, su valor reemplaza la
        // selección anterior. Para poder agregar imágenes en varias tandas
        // (en vez de solo la última), acumulamos los archivos aquí y
        // reconstruimos fileInput.files con la lista completa.
        var pendingFiles = [];

        function syncInputFiles() {
            var dataTransfer = new DataTransfer();
            pendingFiles.forEach(function (file) {
                dataTransfer.items.add(file);
            });
            fileInput.files = dataTransfer.files;
        }

        function renderPreview() {
            preview.innerHTML = '';
            pendingFiles.forEach(function (file, index) {
                var wrapper = document.createElement('div');
                wrapper.className = 'image-preview-item';

                var reader = new FileReader();
                reader.onload = function (e) {
                    var img = document.createElement('img');
                    img.src = e.target.result;
                    wrapper.appendChild(img);
                };
                reader.readAsDataURL(file);

                var removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'image-preview-remove';
                removeBtn.textContent = '×';
                removeBtn.title = 'Quitar esta imagen';
                removeBtn.addEventListener('click', function () {
                    pendingFiles.splice(index, 1);
                    syncInputFiles();
                    renderPreview();
                });
                wrapper.appendChild(removeBtn);

                preview.appendChild(wrapper);
            });
        }

        fileInput.addEventListener('change', function () {
            pendingFiles = pendingFiles.concat(Array.from(fileInput.files));
            syncInputFiles();
            renderPreview();
        });
    }
});
