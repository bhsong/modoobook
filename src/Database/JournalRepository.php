<?php
// src/Database/JournalRepository.php
namespace App\Database;

use App\Core\Database;
use PDO;
use Exception;

class JournalRepository {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // 계정과목-관리항목 매핑 정보 조회 (JS 조달용)
    public function getAccountItemMap($user_id) {
        return $this->db->query("
            SELECT m.accountId, m.itemId, i.itemName
            FROM accountItemMap m
            JOIN managementItems i ON m.itemId = i.itemId
            WHERE i.userId = ?
            ",[$user_id]
        )->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
    }

    // 복합 전표 저장 (SP호출)
    public function saveComplexTransaction($user_id, $date, $description, $jsonData) {
        try {
            // SP 호출 (트랜잭션 처리는 SP 내부에서 수행)
            $this->db->call('sp_save_complex_transaction', [
                $user_id, $date, $description, $jsonData
            ]);
        } catch (Exception $e) {
            throw new Exception("전표 저장 실패: " . $e->getMessage());
        }
    }
}