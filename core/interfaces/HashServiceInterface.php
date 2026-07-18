<?php

/**
 * Contrato común para servicios criptográficos de transformación de datos
 * (hashing). Unifica bajo la misma interfaz el hashing de contraseñas y la
 * firma digital de integridad, permitiendo intercambiar la implementación
 * sin afectar a quienes la consumen (SOLID: Inversión de Dependencias).
 */
interface HashServiceInterface
{
    /** Transforma el dato de entrada en su representación "hasheada". */
    public function hash(string $data): string;

    /** Verifica que un dato coincida con un hash previamente generado. */
    public function verify(string $data, string $hash): bool;
}
