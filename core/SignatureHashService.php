<?php

/**
 * Servicio de firma digital (HMAC-SHA256) bajo el contrato HashServiceInterface.
 * Usado para verificar la integridad del contenido de las noticias (RNF-06).
 */
class SignatureHashService implements HashServiceInterface
{
    public function hash(string $data): string
    {
        return hash_hmac('sha256', $data, SIGNATURE_SECRET_KEY);
    }

    public function verify(string $data, string $hash): bool
    {
        return hash_equals($this->hash($data), $hash);
    }
}
