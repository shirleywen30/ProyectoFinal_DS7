<?php

/**
 * Controlador de autenticacion: login (admin y publico), registro publico,
 * logout y politica de bloqueo de cuentas (RNF-04).
 */
class AuthController extends BaseController
{
    private UserModel $userModel;
    private LogModel $logModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->logModel = new LogModel();
    }

    /**
     * Procesa el envio del formulario de login (sirve tanto para cuentas
     * administrativas como para cuentas publicas normales).
     * Devuelve un arreglo de errores para que la vista los muestre; si el
     * login es exitoso, redirige segun el rol del usuario.
     */
    public function login(): array
    {
        $this->requireCsrf();

        $identifier = Validator::sanitizeString($this->input('usuario'));
        $password = (string) $this->input('password');
        $ip = clientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $validator = new Validator();
        $validator->isRequired($identifier, 'usuario', 'usuario')
            ->isRequired($password, 'password', 'contraseña');

        if ($validator->fails()) {
            return $validator->getErrors();
        }

        $user = $this->userModel->findByUsernameOrEmail($identifier);

        if ($user === null) {
            $this->logModel->record($identifier, $ip, false, $userAgent);
            return ['login' => 'Usuario o contraseña incorrectos.'];
        }

        if ((int) $user['activo'] === 0) {
            $this->logModel->record($identifier, $ip, false, $userAgent);
            return ['login' => 'Esta cuenta se encuentra desactivada. Contacte al administrador.'];
        }

        if ($this->userModel->isLocked($user)) {
            $this->logModel->record($identifier, $ip, false, $userAgent);
            $minutosRestantes = (int) ceil((strtotime($user['bloqueado_hasta']) - time()) / 60);
            return ['login' => "Cuenta bloqueada por múltiples intentos fallidos. Intente nuevamente en {$minutosRestantes} minuto(s)."];
        }

        if (!Security::verifyPassword($password, $user['password'])) {
            $this->userModel->incrementFailedAttempts($user['id']);
            $this->logModel->record($identifier, $ip, false, $userAgent);

            $intentos = (int) $user['intentos_fallidos'] + 1;

            if ($intentos >= MAX_LOGIN_ATTEMPTS) {
                $this->userModel->lockAccount($user['id'], LOCKOUT_MINUTES);
                return ['login' => 'Ha superado el número máximo de intentos. Su cuenta ha sido bloqueada temporalmente.'];
            }

            $restantes = MAX_LOGIN_ATTEMPTS - $intentos;
            return ['login' => "Usuario o contraseña incorrectos. Le quedan {$restantes} intento(s)."];
        }

        // Login exitoso: reinicia contador de intentos y registra la sesion.
        $this->userModel->resetFailedAttempts($user['id']);
        $this->logModel->record($identifier, $ip, true, $userAgent);

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nombre'];
        $_SESSION['user_role'] = $user['rol'];
        $_SESSION['user_email'] = $user['email'];

        // El rol "usuario" (publico) va al sitio publico; el resto (admin,
        // editor, supervisor) va al panel administrativo, igual que antes.
        if ($user['rol'] === 'usuario') {
            $this->redirect(BASE_URL . '/index.php');
        }

        $this->redirect(BASE_URL . '/views/admin/dashboard.php');
    }

    /**
     * Registro publico de una cuenta normal (rol fijo "usuario"). No puede
     * usarse para crear cuentas administrativas: ese rol solo lo asigna un
     * administrador desde el panel (UserController).
     */
    public function register(): array
    {
        $this->requireCsrf();

        $nombre = Validator::sanitizeString($this->input('nombre'));
        $email = Validator::sanitizeEmail($this->input('email'));
        $password = (string) $this->input('password');
        $passwordConfirm = (string) $this->input('password_confirm');

        $validator = new Validator();
        $validator->isRequired($nombre, 'nombre', 'nombre')
            ->isLength($nombre, 'nombre', 'nombre', 3, 100)
            ->isRequired($email, 'email', 'correo electrónico')
            ->isEmail($email, 'email', 'correo electrónico')
            ->isRequired($password, 'password', 'contraseña')
            ->isValidPassword($password);

        if ($password !== $passwordConfirm) {
            $validator->addError('password_confirm', 'Las contraseñas no coinciden.');
        }

        if ($email !== '' && $this->userModel->emailExists($email)) {
            $validator->addError('email', 'Ya existe una cuenta registrada con ese correo electrónico.');
        }

        if ($validator->fails()) {
            return $validator->getErrors();
        }

        $userId = $this->userModel->create([
            'nombre'            => $nombre,
            'email'             => $email,
            'password'          => Security::hashPassword($password),
            'rol'               => 'usuario',
            'activo'            => 1,
            'intentos_fallidos' => 0,
            'created_at'        => date('Y-m-d H:i:s'),
        ]);

        // Inicia sesion automaticamente tras registrarse.
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $nombre;
        $_SESSION['user_role'] = 'usuario';
        $_SESSION['user_email'] = $email;

        $this->logModel->record($email, clientIp(), true, $_SERVER['HTTP_USER_AGENT'] ?? '');

        $this->redirect(BASE_URL . '/index.php');
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        redirectTo(BASE_URL . '/views/admin/login.php');
    }
}
