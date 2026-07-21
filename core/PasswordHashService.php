<?php

/** Servicio de hashing de contraseñas (bcrypt) bajo el contrato HashServiceInterface. */
class PasswordHashService implements HashServiceInterface
{
    public function hash(string $data): string
    {
        return password_hash($data, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public function verify(string $data, string $hash): bool
    {
        return password_verify($data, $hash);
    }
}
