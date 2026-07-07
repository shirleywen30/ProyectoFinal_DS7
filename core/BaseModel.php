<?php

/**
 * Modelo base abstracto: centraliza el acceso a datos genérico (DRY)
 * y obliga a los modelos concretos a implementar CrudInterface (SOLID - LSP/DIP).
 */
abstract class BaseModel implements CrudInterface
{
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function all(array $conditions = [], int $limit = 0, int $offset = 0): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $clauses = [];
            foreach ($conditions as $column => $value) {
                $clauses[] = "{$column} = :{$column}";
                $params[$column] = $value;
            }
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }

        $sql .= " ORDER BY {$this->primaryKey} DESC";

        if ($limit > 0) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function create(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($c) => ":{$c}", $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $assignments = array_map(fn($c) => "{$c} = :{$c}", array_keys($data));
        $sql = "UPDATE {$this->table} SET " . implode(', ', $assignments) . " WHERE {$this->primaryKey} = :id";

        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) AS total FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $clauses = [];
            foreach ($conditions as $column => $value) {
                $clauses[] = "{$column} = :{$column}";
                $params[$column] = $value;
            }
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch()['total'];
    }
}
