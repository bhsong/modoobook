<?php

/**
 * tests/Feature/FeatureTestCase.php
 * Feature(통합) 테스트 베이스 클래스
 *
 * 각 테스트를 트랜잭션으로 감싸고 tearDown에서 롤백하여
 * 테스트 간 데이터 오염을 방지합니다.
 *
 * 사용법:
 *   class JournalDeleteTest extends FeatureTestCase {
 *       public function test전표_삭제_성공(): void {
 *           // $this->db 사용 가능
 *           // 테스트 종료 시 자동 롤백
 *       }
 *   }
 */

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Database;
use PHPUnit\Framework\TestCase;

abstract class FeatureTestCase extends TestCase
{
    protected Database $db;

    protected int $testUserId = 9999; // Feature 테스트 전용 userId

    protected function setUp(): void
    {
        parent::setUp();

        $config = [
            'DB_HOST' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'DB_NAME' => $_ENV['DB_NAME'] ?? 'test',
            'DB_USER' => $_ENV['DB_USER'] ?? 'root',
            'DB_PASS' => $_ENV['DB_PASS'] ?? '',
        ];
        $this->db = new Database($config);
        $this->db->getPdo()->beginTransaction();

        // 테스트 전용 세션 설정
        $_SESSION['userId'] = $this->testUserId;

        // 테스트 전용 유저가 없으면 생성
        $this->ensureTestUser();
    }

    protected function tearDown(): void
    {
        // 항상 롤백하여 테스트 데이터 정리
        if ($this->db->getPdo()->inTransaction()) {
            $this->db->getPdo()->rollBack();
        }

        $_SESSION = [];
        parent::tearDown();
    }

    /**
     * 테스트 전용 유저 확인/생성
     * 트랜잭션 내에서 생성되므로 tearDown 시 자동 삭제됨
     */
    private function ensureTestUser(): void
    {
        $exists = $this->db->query(
            'SELECT userId FROM users WHERE userId = ?',
            [$this->testUserId]
        )->fetch();

        if (! $exists) {
            $this->db->query(
                'INSERT INTO users (userId, userName) VALUES (?, ?)',
                [$this->testUserId, '__test_user__']
            );
        }
    }

    /**
     * 특정 테이블에서 레코드 존재 확인 헬퍼
     */
    protected function assertDatabaseHas(string $table, array $conditions): void
    {
        $wheres = implode(' AND ', array_map(fn ($k) => "$k = ?", array_keys($conditions)));
        $row = $this->db->query(
            "SELECT 1 FROM $table WHERE $wheres",
            array_values($conditions)
        )->fetch();

        $this->assertNotFalse($row, "테이블 '$table'에서 레코드를 찾을 수 없습니다: ".json_encode($conditions));
    }

    /**
     * 특정 테이블에서 레코드 부재 확인 헬퍼
     */
    protected function assertDatabaseMissing(string $table, array $conditions): void
    {
        $wheres = implode(' AND ', array_map(fn ($k) => "$k = ?", array_keys($conditions)));
        $row = $this->db->query(
            "SELECT 1 FROM $table WHERE $wheres",
            array_values($conditions)
        )->fetch();

        $this->assertFalse($row, "테이블 '$table'에 레코드가 존재합니다: ".json_encode($conditions));
    }
}
