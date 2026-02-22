<?php

// src/Database/BaseRepository.php

namespace App\Database;

use App\Core\Database;

abstract class BaseRepository
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * 테이블명 반환. 하위 클래스에서 반드시 구현.
     */
    abstract protected function getTable(): string;

    /**
     * PK 컬럼명 반환.
     * 기본 규칙: 테이블명 끝 's' 제거 + 'Id'
     *   accounts       → accountId
     *   transactions   → transactionId
     * 규칙이 맞지 않는 경우 하위 클래스에서 오버라이드.
     */
    protected function getPrimaryKey(): string
    {
        $table = $this->getTable();
        if (str_ends_with($table, 's')) {
            return substr($table, 0, -1).'Id';
        }

        return $table.'Id';
    }

    /**
     * ORDER BY 허용 컬럼 화이트리스트. 기본값 빈 배열.
     * 하위 클래스에서 오버라이드.
     */
    protected function getAllowedColumns(): array
    {
        return [];
    }

    /**
     * isSystem 컬럼 존재 여부. 기본값 false.
     * isSystem=1인 레코드 삭제를 거부할 경우 true 반환.
     */
    protected function hasSystemFlag(): bool
    {
        return false;
    }

    // ----------------------------------------------------------
    // PK로 단건 조회. 없으면 null 반환.
    // ----------------------------------------------------------
    public function findById(int $id): ?array
    {
        $table = $this->getTable();
        $pk = $this->getPrimaryKey();

        $result = $this->db->query(
            "SELECT * FROM {$table} WHERE {$pk} = ?",
            [$id]
        )->fetch();

        return $result ?: null;
    }

    // ----------------------------------------------------------
    // 조건부 목록 조회.
    // $where : ['userId' => 1, 'isSystem' => 0] → AND 조건 연결
    // $orderBy : getAllowedColumns() 화이트리스트 검증
    // ----------------------------------------------------------
    public function findAll(array $where = [], string $orderBy = ''): array
    {
        $table = $this->getTable();
        $sql = "SELECT * FROM {$table}";
        $params = [];

        if (! empty($where)) {
            $conditions = [];
            foreach ($where as $col => $val) {
                if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $col)) {
                    throw new \InvalidArgumentException("허용되지 않는 컬럼명: {$col}");
                }
                $conditions[] = "{$col} = ?";
                $params[] = $val;
            }
            $sql .= ' WHERE '.implode(' AND ', $conditions);
        }

        if ($orderBy !== '') {
            $allowed = $this->getAllowedColumns();
            if (! in_array($orderBy, $allowed, true)) {
                throw new \InvalidArgumentException("허용되지 않는 정렬 컬럼: {$orderBy}");
            }
            $sql .= " ORDER BY {$orderBy}";
        }

        return $this->db->query($sql, $params)->fetchAll();
    }

    // ----------------------------------------------------------
    // INSERT. PDO lastInsertId() 반환.
    // ----------------------------------------------------------
    public function create(array $data): int
    {
        $table = $this->getTable();
        $cols = [];
        $placeholders = [];
        $params = [];

        foreach ($data as $col => $val) {
            if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $col)) {
                throw new \InvalidArgumentException("허용되지 않는 컬럼명: {$col}");
            }
            $cols[] = $col;
            $placeholders[] = '?';
            $params[] = $val;
        }

        $col_list = implode(', ', $cols);
        $ph_list = implode(', ', $placeholders);

        $this->db->query("INSERT INTO {$table} ({$col_list}) VALUES ({$ph_list})", $params);

        return (int) $this->db->getPdo()->lastInsertId();
    }

    // ----------------------------------------------------------
    // UPDATE. 변경된 행이 있으면 true 반환.
    // ----------------------------------------------------------
    public function update(int $id, array $data): bool
    {
        $table = $this->getTable();
        $pk = $this->getPrimaryKey();
        $sets = [];
        $params = [];

        foreach ($data as $col => $val) {
            if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $col)) {
                throw new \InvalidArgumentException("허용되지 않는 컬럼명: {$col}");
            }
            $sets[] = "{$col} = ?";
            $params[] = $val;
        }

        $params[] = $id;
        $set_str = implode(', ', $sets);
        $stmt = $this->db->query("UPDATE {$table} SET {$set_str} WHERE {$pk} = ?", $params);

        return $stmt->rowCount() > 0;
    }

    // ----------------------------------------------------------
    // DELETE. hasSystemFlag()=true면 isSystem=1 레코드 거부.
    // ----------------------------------------------------------
    public function delete(int $id): bool
    {
        $table = $this->getTable();
        $pk = $this->getPrimaryKey();

        if ($this->hasSystemFlag()) {
            $record = $this->findById($id);
            if ($record && (int) ($record['isSystem'] ?? 0) === 1) {
                throw new \RuntimeException('시스템 레코드는 삭제할 수 없습니다.');
            }
        }

        $stmt = $this->db->query("DELETE FROM {$table} WHERE {$pk} = ?", [$id]);

        return $stmt->rowCount() > 0;
    }
}
