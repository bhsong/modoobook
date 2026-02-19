<?php
// src/Database/JournalRepository.php
namespace App\Database;

use App\Core\Database;
use PDO;
use Exception;

class JournalRepository extends BaseRepository
{
    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    protected function getTable(): string
    {
        return 'transactions';
    }

    // ----------------------------------------------------------
    // 전표는 반드시 SP를 통해 저장해야 하므로 기본 CRUD 비활성화
    // ----------------------------------------------------------
    public function create(array $data): int
    {
        throw new \LogicException('전표는 Service를 통해 저장하세요. saveComplexTransaction()을 사용하십시오.');
    }

    public function update(int $id, array $data): bool
    {
        throw new \LogicException('전표는 Service를 통해 수정하세요.');
    }

    public function delete(int $id): bool
    {
        throw new \LogicException('전표는 Service를 통해 삭제하세요.');
    }

    // ----------------------------------------------------------
    // 계정과목-관리항목 매핑 정보 조회 (JS 조달용)
    // getAllItems()와 동일하게 시스템 항목(isSystem=1)도 포함.
    // AccountSetupController에서 시스템 항목도 매핑 가능하므로
    // 전표 입력 화면에서도 시스템 항목이 표시되어야 함.
    // ----------------------------------------------------------
    public function getAccountItemMap(int $user_id): array
    {
        return $this->db->query(
            "SELECT m.accountId, m.itemId, i.itemName
             FROM accountItemMap m
             JOIN managementItems i ON m.itemId = i.itemId
             WHERE i.userId = ? OR i.isSystem = 1",
            [$user_id]
        )->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
    }

    // ----------------------------------------------------------
    // 복합 전표 저장 (SP 호출)
    // ----------------------------------------------------------
    public function saveComplexTransaction(int $user_id, string $date, string $description, string $jsonData): void
    {
        try {
            $this->db->call('sp_save_complex_transaction', [
                $user_id, $date, $description, $jsonData
            ]);
        } catch (Exception $e) {
            throw new Exception("전표 저장 실패: " . $e->getMessage());
        }
    }
}
