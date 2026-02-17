<?php
// src/Database/AccountRepository.php
namespace App\Database;

use App\Core\Database;
use Exception;
use PDO;

class AccountRepository {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }
    // 계정과목 목록 조회
    public function getAccounts($user_id) {
        return $this->db->query(
            'SELECT * FROM accounts WHERE userId = ? ORDER BY accountId ASC',
            [$user_id]
        )->fetchAll();
    }

    // 계정과목 생성
    public function createAccount($user_id, $name, $type) {
        $this->db->query(
            'INSERT INTO accounts (userId, accountName, accountType) VALUES (?, ?, ?)',
            [$user_id, $name, $type]
        );
    }    

    // 계정과목-관리항목 연결
    public function linkAccountItem($account_id, $item_id) {
        // 중복 연결 방지를 위해 INSERT IGNORE를 쓰거나, 로직으로 체크할 수 있지만
        // 지금은 단순하게 INSERT만 구현
        $this->db->query(
            "INSERT INTO accountItemMap (accountId, itemId) VALUES (?, ?)",
            [$account_id, $item_id]
        );
    }

    // 특정 계정 조회
    public function getAccountById($account_id) {
        return $this->db->query(
            "SELECT * FROM accounts WHERE accountId = ?",
            [$account_id]
        )->fetch();
    }

    // 매핑된 항목 ID 목록 조회 (체크박스용)
    public function getMappedItemIds($account_id) {
        return $this->db->query(
            "SELECT itemId FROM accountItemMap WHERE accountId=?",
            [$account_id]
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    // 매핑 정보 일괄 업데이트 (SP 호출)
    public function updateAccountMappings($account_id, $item_ids) {
        // PHP 배열을 JSON 문자열로 변환 (ex. [1,3] -> "[1.3]")
        // 만약 빈 배열이면 "[]" 
        $json_ids = json_encode($item_ids);

        // SP 호출
        try {
            $stmt = $this->db->call('_SPUpdateAccountMappings', [
                $account_id, $json_ids
            ]);
        } catch (Exception $e) {
            // SP 내부에서 에러가 나면 여기서 잡힘
            throw new Exception("매핑 저장 실패: " . $e->getMessage());
        }
    }
}