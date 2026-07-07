<?php

/**
 * Contrato que deben cumplir todos los modelos con operaciones CRUD.
 * Aplica el Principio de Segregación de Interfaces e Inversión de
 * Dependencias (SOLID): los controladores dependen de esta abstracción,
 * no de una implementación concreta.
 */
interface CrudInterface
{
    public function all(array $conditions = [], int $limit = 0, int $offset = 0): array;

    public function find(int $id): ?array;

    public function create(array $data): int;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;
}
