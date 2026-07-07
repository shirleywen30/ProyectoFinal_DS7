<?php

/**
 * Controlador de autenticación: login, logout y política de bloqueo de
 * cuentas (RNF-04). Registra cada intento (éxito o fallo) con IP y fecha.
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
     * Procesa el envío del formulario de login.
     * Devuelve un arreglo de errores para que la vista los muestre; si el
     * login es exitoso, redirige directamente al dashboard.
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

        // Login exitoso: reinicia contador de intentos y registra la sesión.
        $this->userModel->resetFailedAttempts($user['id']);
        $this->logModel->record($identifier, $ip, true, $userAgent);

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nombre'];
        $_SESSION['user_role'] = $user['rol'];

        $this->redirect(BASE_URL . '/views/admin/dashboard.php');
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
