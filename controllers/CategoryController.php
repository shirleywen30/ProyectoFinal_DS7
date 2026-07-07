<?php

/** Controlador CRUD de categorías de noticias. */
class CategoryController extends BaseController
{
    private CategoryModel $categoryModel;

    public function __construct()
    {
        $this->requireAuth();
        $this->categoryModel = new CategoryModel();
    }

    public function listAll(): array
    {
        return $this->categoryModel->all();
    }

    public function find(int $id): ?array
    {
        return $this->categoryModel->find($id);
    }

    public function save(?int $id): array
    {
        $this->requireCsrf();

        $nombre = Validator::sanitizeString($this->input('nombre'));
        $descripcion = Validator::sanitizeString($this->input('descripcion'));
        $activo = $this->input('activo', '1') === '1' ? 1 : 0;

        $validator = new Validator();
        $validator->isRequired($nombre, 'nombre', 'nombre')
            ->isLength($nombre, 'nombre', 'nombre', 3, 60);

        if ($this->categoryModel->nameExists($nombre, $id)) {
            $validator->addError('nombre', 'Ya existe una categoría con ese nombre.');
        }

        if ($validator->fails()) {
            return $validator->getErrors();
        }

        $data = ['nombre' => $nombre, 'descripcion' => $descripcion, 'activo' => $activo];

        if ($id === null) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->categoryModel->create($data);
        } else {
            $this->categoryModel->update($id, $data);
        }

        return [];
    }

    /** Elimina la categoría solo si no tiene noticias asociadas (integridad referencial). */
    public function delete(int $id): bool
    {
        $this->requireCsrf();

        if ($this->categoryModel->hasNews($id)) {
            $this->setFlash('error', 'No se puede eliminar: existen noticias asociadas a esta categoría.');
            return false;
        }

        $this->categoryModel->delete($id);
        return true;
    }
}
