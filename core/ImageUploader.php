<?php

/**
 * Clase responsable exclusivamente de procesar la subida de imágenes de
 * noticias y generar sus miniaturas (thumbnails) automáticamente (SRP - SOLID).
 */
class ImageUploader
{
    private array $errors = [];

    /**
     * Procesa un arreglo $_FILES (input múltiple) y devuelve una lista de
     * rutas [ruta_imagen, ruta_thumbnail] relativas a /public/uploads/.
     */
    public function processMultiple(array $filesInput): array
    {
        $results = [];
        $count = count($filesInput['name'] ?? []);

        for ($i = 0; $i < $count; $i++) {
            if (($filesInput['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $file = [
                'name'     => $filesInput['name'][$i],
                'type'     => $filesInput['type'][$i],
                'tmp_name' => $filesInput['tmp_name'][$i],
                'error'    => $filesInput['error'][$i],
                'size'     => $filesInput['size'][$i],
            ];

            $processed = $this->processSingle($file);
            if ($processed !== null) {
                $results[] = $processed;
            }
        }

        return $results;
    }

    /** Valida y guarda una sola imagen, generando su thumbnail. Devuelve null si falla. */
    public function processSingle(array $file): ?array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = "Error al subir el archivo {$file['name']}.";
            return null;
        }

        if ($file['size'] > MAX_IMAGE_SIZE_BYTES) {
            $this->errors[] = "La imagen {$file['name']} supera el tamaño máximo permitido (5MB).";
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, ALLOWED_IMAGE_MIMES, true)) {
            $this->errors[] = "El archivo {$file['name']} no es una imagen permitida (jpg, png, webp).";
            return null;
        }

        $extensionMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $extension = $extensionMap[$mime];
        $fileName = Security::randomFileName($extension);

        $destination = UPLOAD_NEWS_PATH . $fileName;
        $thumbDestination = UPLOAD_THUMB_PATH . $fileName;

        if (!is_uploaded_file($file['tmp_name']) || !move_uploaded_file($file['tmp_name'], $destination)) {
            $this->errors[] = "No fue posible almacenar el archivo {$file['name']}.";
            return null;
        }

        if (!$this->createThumbnail($destination, $thumbDestination, $mime)) {
            $this->errors[] = "No fue posible generar la miniatura de {$file['name']}.";
        }

        return [
            'ruta_imagen'    => 'news/' . $fileName,
            'ruta_thumbnail' => 'thumbnails/' . $fileName,
        ];
    }

    private function createThumbnail(string $sourcePath, string $destPath, string $mime): bool
    {
        [$width, $height] = getimagesize($sourcePath);

        $sourceImage = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($sourcePath),
            'image/png'  => imagecreatefrompng($sourcePath),
            'image/webp' => imagecreatefromwebp($sourcePath),
            default      => null,
        };

        if ($sourceImage === null) {
            return false;
        }

        $ratio = min(THUMBNAIL_WIDTH / $width, THUMBNAIL_HEIGHT / $height);
        $newWidth = (int) round($width * $ratio);
        $newHeight = (int) round($height * $ratio);

        $thumb = imagecreatetruecolor($newWidth, $newHeight);

        if ($mime === 'image/png') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
        }

        imagecopyresampled($thumb, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $saved = match ($mime) {
            'image/jpeg' => imagejpeg($thumb, $destPath, 85),
            'image/png'  => imagepng($thumb, $destPath),
            'image/webp' => imagewebp($thumb, $destPath, 85),
            default      => false,
        };

        imagedestroy($sourceImage);
        imagedestroy($thumb);

        return $saved;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }
}
