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

    // ----------------------------------------------------------
    // 전체 계정 목록 (level, isSystem, parentAccountId 포함)
    // 본인 계정 + 시스템 계정 모두 반환
    // ----------------------------------------------------------
    public function getAllAccounts(int $userId): array {
        return $this->db->query(
            'SELECT * FROM accounts
             WHERE userId = ? OR isSystem = 1
             ORDER BY accountLevel ASC, parentAccountId ASC, accountId ASC',
            [$userId]
        )->fetchAll();
    }

    // ----------------------------------------------------------
    // 트리 구조로 전체 계정 조회 (재무제표, 계정 목록 화면용)
    // 반환: [ { ...l1_account, children: [ { ...l2_account, children: [...l3] } ] } ]
    // ----------------------------------------------------------
    public function getAccountTree(int $userId): array {
        $all = $this->getAllAccounts($userId);
        return $this->buildTree($all, null);
    }

    private function buildTree(array $flatList, ?int $parentId): array {
        $branch = [];
        foreach ($flatList as $item) {
            $itemParentId = ($item['parentAccountId'] !== null)
                ? (int)$item['parentAccountId']
                : null;
            if ($itemParentId === $parentId) {
                $item['children'] = $this->buildTree($flatList, (int)$item['accountId']);
                $branch[] = $item;
            }
        }
        return $branch;
    }

    // ----------------------------------------------------------
    // 전표 입력용 계정 목록 (level=3만, 본인 + 시스템)
    // parentName 컬럼 포함 → optgroup 그룹핑용
    // ----------------------------------------------------------
    public function getLeafAccounts(int $userId): array {
        return $this->db->query(
            'SELECT a.*, p.accountName AS parentName
             FROM accounts a
             LEFT JOIN accounts p ON a.parentAccountId = p.accountId
             WHERE a.accountLevel = 3
               AND (a.userId = ? OR a.isSystem = 1)
             ORDER BY a.accountType ASC, a.parentAccountId ASC, a.accountId ASC',
            [$userId]
        )->fetchAll();
    }

    // ----------------------------------------------------------
    // 특정 계정의 모든 하위 계정 ID 목록 (원장 집계용)
    // BFS 방식으로 자신 포함 전체 자손 ID 반환
    // ----------------------------------------------------------
    public function getDescendantIds(int $accountId): array {
        $rows = $this->db->query(
            'SELECT accountId, parentAccountId FROM accounts',
            []
        )->fetchAll();

        // parentId -> children IDs 맵 생성
        $childrenMap = [];
        foreach ($rows as $row) {
            if ($row['parentAccountId'] !== null) {
                $childrenMap[(int)$row['parentAccountId']][] = (int)$row['accountId'];
            }
        }

        // BFS
        $result = [];
        $queue  = [$accountId];
        while (!empty($queue)) {
            $current  = array_shift($queue);
            $result[] = $current;
            if (isset($childrenMap[$current])) {
                foreach ($childrenMap[$current] as $childId) {
                    $queue[] = $childId;
                }
            }
        }
        return $result;
    }

    // ----------------------------------------------------------
    // 계정과목 생성 (level, parentAccountId 포함)
    // accountType은 상위 계정에서 자동 상속 — 컨트롤러에서 결정
    // ----------------------------------------------------------
    public function createAccount(int $userId, string $name, string $type, int $level, ?int $parentAccountId): void {
        $this->db->query(
            'INSERT INTO accounts (userId, accountName, accountType, accountLevel, parentAccountId)
             VALUES (?, ?, ?, ?, ?)',
            [$userId, $name, $type, $level, $parentAccountId]
        );
    }

    // ----------------------------------------------------------
    // 계정과목 삭제
    // isSystem=1이면 거부 / 하위 계정 존재 시 거부
    // ----------------------------------------------------------
    public function deleteAccount(int $accountId): void {
        $account = $this->getAccountById($accountId);
        if (!$account) {
            throw new Exception("계정을 찾을 수 없습니다.");
        }
        if ((int)$account['isSystem'] === 1) {
            throw new Exception("시스템 계정은 삭제할 수 없습니다.");
        }

        $children = $this->db->query(
            'SELECT accountId FROM accounts WHERE parentAccountId = ?',
            [$accountId]
        )->fetchAll();

        if (!empty($children)) {
            throw new Exception("하위 계정이 존재하여 삭제할 수 없습니다. 하위 계정을 먼저 삭제하세요.");
        }

        $this->db->query(
            'DELETE FROM accounts WHERE accountId = ?',
            [$accountId]
        );
    }

    // ----------------------------------------------------------
    // 특정 계정 조회
    // ----------------------------------------------------------
    public function getAccountById($account_id) {
        return $this->db->query(
            "SELECT * FROM accounts WHERE accountId = ?",
            [$account_id]
        )->fetch();
    }

    // ----------------------------------------------------------
    // 매핑된 항목 ID 목록 조회 (체크박스용)
    // ----------------------------------------------------------
    public function getMappedItemIds($account_id) {
        return $this->db->query(
            "SELECT itemId FROM accountItemMap WHERE accountId = ?",
            [$account_id]
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    // ----------------------------------------------------------
    // 계정과목-관리항목 연결
    // ----------------------------------------------------------
    public function linkAccountItem($account_id, $item_id) {
        $this->db->query(
            "INSERT INTO accountItemMap (accountId, itemId) VALUES (?, ?)",
            [$account_id, $item_id]
        );
    }

    // ----------------------------------------------------------
    // 매핑 정보 일괄 업데이트 (SP 호출)
    // ----------------------------------------------------------
    public function updateAccountMappings($account_id, $item_ids) {
        $json_ids = json_encode($item_ids);
        try {
            $this->db->call('_SPUpdateAccountMappings', [$account_id, $json_ids]);
        } catch (Exception $e) {
            throw new Exception("매핑 저장 실패: " . $e->getMessage());
        }
    }
}
