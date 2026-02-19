<?php
// src/Core/ReportBuilder.php
namespace App\Core;

use App\Core\Database;
use PDO;

class ReportBuilder
{
    private Database $db;
    private array    $selectColumns  = [];
    private string   $fromTable      = '';
    private string   $fromAlias      = '';
    private array    $joins          = [];
    private array    $conditions     = [];
    private array    $params         = [];
    private array    $groupByColumns = [];
    private string   $orderByColumn  = '';
    private string   $orderDirection = 'ASC';
    private ?int     $limitCount     = null;

    // SQL Injection 방지 화이트리스트
    // ※ 별칭(alias)은 별도 정규식 /^[a-zA-Z_][a-zA-Z0-9_]*$/ 으로 검증하므로
    //    여기에 't', 'je' 같은 단문자 별칭을 포함하면 테이블 검증이 무의미해짐.
    private const ALLOWED_TABLES = [
        'transactions', 'journalEntries', 'accounts', 'managementItems',
        'accountItemMap', 'entryItemValues', 'users',
    ];

    private const ALLOWED_DIRECTIONS = ['ASC', 'DESC'];
    private const ALLOWED_JOIN_TYPES = ['INNER', 'LEFT', 'RIGHT'];
    private const ALLOWED_OPERATORS  = ['=', '!=', '>', '<', '>=', '<='];

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    // ----------------------------------------------------------
    // SELECT 컬럼 지정
    // 집계 함수(SUM, COUNT, AVG, COALESCE), 별칭(AS), * 허용
    // 정규식: /^[a-zA-Z0-9_\.\(\)\s\*,]+$/
    // ----------------------------------------------------------
    public function select(array $columns): self
    {
        foreach ($columns as $col) {
            if (!preg_match('/^[a-zA-Z0-9_\.\(\)\s\*,]+$/', $col)) {
                throw new \InvalidArgumentException("허용되지 않는 SELECT 표현식: {$col}");
            }
        }
        $this->selectColumns = $columns;
        return $this;
    }

    // ----------------------------------------------------------
    // FROM 테이블 지정 (화이트리스트 검증)
    // ----------------------------------------------------------
    public function from(string $table, string $alias = ''): self
    {
        if (!in_array($table, self::ALLOWED_TABLES, true)) {
            throw new \InvalidArgumentException("허용되지 않는 테이블명: {$table}");
        }
        if ($alias !== '' && !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $alias)) {
            throw new \InvalidArgumentException("허용되지 않는 테이블 별칭: {$alias}");
        }
        $this->fromTable = $table;
        $this->fromAlias = $alias;
        return $this;
    }

    // ----------------------------------------------------------
    // JOIN 절 추가
    // type : 'INNER', 'LEFT', 'RIGHT'
    // on   : 'je.transactionId = t.transactionId' 패턴만 허용
    // ----------------------------------------------------------
    public function join(string $type, string $table, string $alias, string $on): self
    {
        $type = strtoupper($type);
        if (!in_array($type, self::ALLOWED_JOIN_TYPES, true)) {
            throw new \InvalidArgumentException("허용되지 않는 JOIN 타입: {$type}");
        }
        if (!in_array($table, self::ALLOWED_TABLES, true)) {
            throw new \InvalidArgumentException("허용되지 않는 테이블명: {$table}");
        }
        if ($alias !== '' && !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $alias)) {
            throw new \InvalidArgumentException("허용되지 않는 별칭: {$alias}");
        }
        // ON 절: 'alias.column = alias.column' 패턴만 허용
        if (!preg_match('/^[a-zA-Z0-9_.]+\s*=\s*[a-zA-Z0-9_.]+$/', $on)) {
            throw new \InvalidArgumentException("허용되지 않는 ON 절: {$on}");
        }

        $alias_str     = $alias ? " {$alias}" : '';
        $this->joins[] = "{$type} JOIN {$table}{$alias_str} ON {$on}";
        return $this;
    }

    // ----------------------------------------------------------
    // WHERE 조건 추가 (값은 PDO 바인딩 처리)
    // ----------------------------------------------------------
    public function where(string $column, mixed $value, string $operator = '='): self
    {
        if (!$this->validateIdentifier($column)) {
            throw new \InvalidArgumentException("허용되지 않는 컬럼명: {$column}");
        }
        if (!in_array($operator, self::ALLOWED_OPERATORS, true)) {
            throw new \InvalidArgumentException("허용되지 않는 연산자: {$operator}");
        }
        $this->conditions[] = "{$column} {$operator} ?";
        $this->params[]     = $value;
        return $this;
    }

    // ----------------------------------------------------------
    // BETWEEN 조건 (날짜 범위 등에 사용)
    // ----------------------------------------------------------
    public function whereBetween(string $column, mixed $from, mixed $to): self
    {
        if (!$this->validateIdentifier($column)) {
            throw new \InvalidArgumentException("허용되지 않는 컬럼명: {$column}");
        }
        $this->conditions[] = "{$column} BETWEEN ? AND ?";
        $this->params[]     = $from;
        $this->params[]     = $to;
        return $this;
    }

    // ----------------------------------------------------------
    // IN 조건
    // ----------------------------------------------------------
    public function whereIn(string $column, array $values): self
    {
        if (!$this->validateIdentifier($column)) {
            throw new \InvalidArgumentException("허용되지 않는 컬럼명: {$column}");
        }
        if (empty($values)) {
            $this->conditions[] = '1 = 0'; // 빈 IN() → 항상 false
            return $this;
        }
        $placeholders       = implode(', ', array_fill(0, count($values), '?'));
        $this->conditions[] = "{$column} IN ({$placeholders})";
        $this->params       = array_merge($this->params, array_values($values));
        return $this;
    }

    // ----------------------------------------------------------
    // GROUP BY
    // ----------------------------------------------------------
    public function groupBy(array $columns): self
    {
        foreach ($columns as $col) {
            if (!$this->validateIdentifier($col)) {
                throw new \InvalidArgumentException("허용되지 않는 GROUP BY 컬럼: {$col}");
            }
        }
        $this->groupByColumns = $columns;
        return $this;
    }

    // ----------------------------------------------------------
    // ORDER BY (화이트리스트 검증, 방향은 ASC/DESC만 허용)
    // ----------------------------------------------------------
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        if (!$this->validateIdentifier($column)) {
            throw new \InvalidArgumentException("허용되지 않는 ORDER BY 컬럼: {$column}");
        }
        $direction = strtoupper($direction);
        if (!in_array($direction, self::ALLOWED_DIRECTIONS, true)) {
            throw new \InvalidArgumentException("허용되지 않는 정렬 방향: {$direction}");
        }
        $this->orderByColumn  = $column;
        $this->orderDirection = $direction;
        return $this;
    }

    // ----------------------------------------------------------
    // LIMIT
    // ----------------------------------------------------------
    public function limit(int $count): self
    {
        $this->limitCount = $count;
        return $this;
    }

    // ----------------------------------------------------------
    // 최종 SQL 문자열 반환 (디버깅/로깅용)
    // ----------------------------------------------------------
    public function build(): string
    {
        $select_str = empty($this->selectColumns)
            ? '*'
            : implode(', ', $this->selectColumns);

        $from_str = $this->fromAlias
            ? "{$this->fromTable} {$this->fromAlias}"
            : $this->fromTable;

        $sql = "SELECT {$select_str} FROM {$from_str}";

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }
        if (!empty($this->conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->conditions);
        }
        if (!empty($this->groupByColumns)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupByColumns);
        }
        if ($this->orderByColumn !== '') {
            $sql .= " ORDER BY {$this->orderByColumn} {$this->orderDirection}";
        }
        if ($this->limitCount !== null) {
            $sql .= " LIMIT {$this->limitCount}";
        }

        return $sql;
    }

    // ----------------------------------------------------------
    // 쿼리 실행 → 결과 배열 반환
    // ----------------------------------------------------------
    public function get(): array
    {
        return $this->db->query($this->build(), $this->params)->fetchAll();
    }

    // ----------------------------------------------------------
    // PDO::FETCH_GROUP 방식으로 결과 반환
    // 주의: SELECT의 첫 번째 컬럼이 그룹 키가 됨
    // ----------------------------------------------------------
    public function getGrouped(): array
    {
        $stmt    = $this->db->query($this->build(), $this->params);
        $results = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $results;
    }

    // ----------------------------------------------------------
    // COUNT(*) 쿼리로 변환 후 실행 → 정수 반환
    // ----------------------------------------------------------
    public function count(): int
    {
        $from_str = $this->fromAlias
            ? "{$this->fromTable} {$this->fromAlias}"
            : $this->fromTable;

        $sql = "SELECT COUNT(*) FROM {$from_str}";

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }
        if (!empty($this->conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->conditions);
        }

        return (int) $this->db->query($sql, $this->params)->fetchColumn();
    }

    // ----------------------------------------------------------
    // 테이블명/컬럼명 허용 패턴 검증
    // 정규식: /^[a-zA-Z_][a-zA-Z0-9_.]*$/ (별칭.컬럼 형태 허용)
    // ----------------------------------------------------------
    private function validateIdentifier(string $identifier): bool
    {
        return (bool)preg_match('/^[a-zA-Z_][a-zA-Z0-9_.]*$/', $identifier);
    }
}
