<?php
// src/Core/Database.php
namespace App\Core;

use PDO;
use PDOException;

class Database {
    private $pdo;

    public function __construct(array $config) {
        // DSN (Data Source Name) 생성
        $dsn = "mysql:host={$config['DB_HOST']};dbname={$config['DB_NAME']};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE    => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES      => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASS'], $options);
        } catch (PDOException $e) {
            // 연결 실패 시 로그를 남기고 종료 (보안상 상세 에러는 숨김)
            error_log("DB Connection Error: " . $e->getMessage());
            throw new \Exception("데이터베이스 연결에 실패했습니다.");
        }
    }

    /**
     * Stored Procedure 호출을 위한 표준 메서드
     * 사용법: $db->call('sp_name', [$arg1, $arg2]);
     */
    public function call(string $spName, array $params = []): array {
        try {
            // 파라미터 개수만큼 물음표(?) 생성 (예: "?, ?, ?")
            $placeholders = [];
            if (!empty($params)) {
                $placeholders = array_fill(0, count($params), '?');
            }
            $placeholderStr = implode(', ', $placeholders);

            // SQL 준비 (CALL sp_name(?, ?))
            $sql = "CALL {$spName}({$placeholderStr})";
            $stmt = $this->pdo->prepare($sql);

            // 실행
            $stmt->execute($params);

            // 결과 가져오기 (SELECT 결과가 있으면 반환)
            // 프로시저가 결과를 반환하지 않는 경우(INSERT/UPDATE 등)에는 빈 배열 반환 가능
            $results = [];

            // 결과셋이 있는지 확인 (INSERT/UPDATE만 하는 sp는 columnCount가 0일 수 있음)
            if ($stmt->columnCount() > 0) {
                // 그룹화 옵션 처리 등은 필요하면 여기서 확장 가능
                // 기본적으로 전체 Fetch
                $results = $stmt->fetchAll();
            }

            // [중요] 다음 쿼리를 위해 커서 닫기 (MariaDB 필수)
            $stmt->closeCursor();

            return $results ?: [];
        } catch (PDOException $e) {
            // 에러 발생 시 로그 기록 후 다시 던짐
            error_log("SP Execution Error [{$spName}]: " . $e->getMessage());
            throw $e;
        }
    }
    /**
     * 일반 SQL 쿼리 실행용 (필요할 경우 사용)
     */
    public function query(string $sql, array $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    // 트랜잭션 관련 메서드
    public function beginTransaction() { return $this->pdo->beginTransaction(); }
    public function commit() { return $this->pdo->commit(); }
    public function rollback() { return $this->pdo->rollBack(); }
    
    // 원본 PDO 객체가 필요할 때 (비상용)
    public function getPdo() { return $this->pdo; }

}