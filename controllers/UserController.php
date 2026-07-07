<?php

/**
 * Controlador CRUD de usuarios administrativos.
 * Acceso restringido al rol "admin".
 */
class UserController extends BaseController
{
    private UserModel $userModel;
    private const PER_PAGE = 10;

    public function __construct()
    {
        $this->requireRole('admin');
        $this->userModel = new UserModel();
    }

    public function listPaginated(string $term, int $page): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * self::PER_PAGE;

        $items = $term !== ''
            ? $this->userModel->search($term, self::PER_PAGE, $offset)
            : $this->userModel->all([], self::PER_PAGE, $offset);

        $total = $term !== '' ? $this->userModel->countSearch($term) : $this->userModel->count();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'totalPages' => max(1, (int) ceil($total / self::PER_PAGE)),
        ];
    }

    public function find(int $id): ?array
    {
        return $this->userModel->find($id);
    }

    /** Procesa creación/edición. Devuelve arreglo de errores (vacío si fue exitoso). */
    public function save(?int $id): array
    {
        $this->requireCsrf();

        $nombre = Validator::sanitizeString($this->input('nombre'));
        $email = Validator::sanitizeEmail($this->input('email'));
        $password = (string) $this->input('password');
        $rol = Validator::sanitizeString($this->input('rol'));
        $activo = $this->input('activo', '1') === '1' ? 1 : 0;

        $validator = new Validator();
        $validator->isRequired($nombre, 'nombre', 'nombre')
            ->isLength($nombre, 'nombre', 'nombre', 3, 100)
            ->isRequired($email, 'email', 'correo electrónico')
            ->isEmail($email, 'email', 'correo electrónico')
            ->isInArray($rol, ['admin', 'editor'], 'rol', 'rol');

        // La contraseña solo es obligatoria al crear; en edición es opcional (se conserva si se deja vacía).
        if ($id === null || $password !== '') {
            $validator->isRequired($password, 'password', 'contraseña')
                ->isValidPassword($password);
        }

        if ($this->userModel->emailExists($email, $id)) {
            $validator->addError('email', 'Ya existe un usuario registrado con ese correo electrónico.');
        }

        if ($validator->fails()) {
            return $validator->getErrors();
        }

        $data = [
            'nombre' => $nombre,
            'email' => $email,
            'rol' => $rol,
            'activo' => $activo,
        ];

        if ($password !== '') {
            $data['password'] = Security::hashPassword($password);
        }

        if ($id === null) {
            $data['intentos_fallidos'] = 0;
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->userModel->create($data);
        } else {
            $this->userModel->update($id, $data);
        }

        return [];
    }

    public function toggleActive(int $id): void
    {
        $this->requireCsrf();
        $user = $this->userModel->find($id);
        if ($user !== null) {
            $this->userModel->toggleActive($id, !((bool) $user['activo']));
        }
    }
}
