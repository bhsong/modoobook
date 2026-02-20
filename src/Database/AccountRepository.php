<?php
// src/Database/AccountRepository.php
namespace App\Database;

use App\Core\Database;
use Exception;
use PDO;

class AccountRepository extends BaseRepository
{
    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    protected function getTable(): string
    {
        return 'accounts';
    }

    protected function hasSystemFlag(): bool
    {
        return true;
    }

    protected function getAllowedColumns(): array
    {
        return ['accountId', 'accountName', 'accountLevel', 'accountType'];
    }

    // ----------------------------------------------------------
    // 전체 계정 목록 (level, isSystem, parentAccountId 포함)
    // 본인 계정 + 시스템 계정 모두 반환
    // ----------------------------------------------------------
    public function getAllAccounts(int $userId): array
    {
        return $this->db->query(
            'SELECT * FROM accounts
             WHERE userId = ? OR isSystem = 1
             ORDER BY accountLevel ASC, parentAccountId ASC, accountId ASC',
            [$userId]
        )->fetchAll();
    }

    // ----------------------------------------------------------
    // 트리 구조로 전체 계정 조회 (재무제표, 계정 목록 화면용)
    // ----------------------------------------------------------
    public function getAccountTree(int $userId): array
    {
        $all = $this->getAllAccounts($userId);
        return $this->buildTree($all, null);
    }

    private function buildTree(array $flat_list, ?int $parent_id): array
    {
        $branch = [];
        foreach ($flat_list as $item) {
            $item_parent_id = ($item['parentAccountId'] !== null)
                ? (int)$item['parentAccountId']
                : null;
            if ($item_parent_id === $parent_id) {
                $item['children'] = $this->buildTree($flat_list, (int)$item['accountId']);
                $branch[] = $item;
            }
        }
        return $branch;
    }

    // ----------------------------------------------------------
    // 전표 입력용 계정 목록 (level=3만, 본인 + 시스템)
    // parentName 컬럼 포함 → optgroup 그룹핑용
    // ----------------------------------------------------------
    public function getLeafAccounts(int $userId): array
    {
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
    public function getDescendantIds(int $accountId): array
    {
        $rows = $this->db->query(
            'SELECT accountId, parentAccountId FROM accounts',
            []
        )->fetchAll();

        $children_map = [];
        foreach ($rows as $row) {
            if ($row['parentAccountId'] !== null) {
                $children_map[(int)$row['parentAccountId']][] = (int)$row['accountId'];
            }
        }

        $result = [];
        $queue  = [$accountId];
        while (!empty($queue)) {
            $current  = array_shift($queue);
            $result[] = $current;
            if (isset($children_map[$current])) {
                foreach ($children_map[$current] as $child_id) {
                    $queue[] = $child_id;
                }
            }
        }
        return $result;
    }

    // ----------------------------------------------------------
    // 해당 계정에 연결된 journalEntries 존재 여부 확인
    // AccountService::deleteAccount()에서 삭제 가능 여부 판단에 사용
    //
    // [설계 노트] journalEntries는 LedgerRepository 도메인이나,
    // AccountService가 삭제 가능 여부 판단을 위해 최소 의존이 필요함.
    // LedgerRepository 추가 주입의 복잡도를 피하는 실용적 타협으로 이 위치에 배치.
    // 향후 규모 확장 시 별도 체크 서비스로 분리 가능.
    // ----------------------------------------------------------
    public function hasLinkedEntries(int $accountId): bool
    {
        $row = $this->db->query(
            'SELECT entryId FROM journalEntries WHERE accountId = ? LIMIT 1',
            [$accountId]
        )->fetch();
        return (bool)$row;
    }

    // ----------------------------------------------------------
    // 계정과목 생성 (기존 메서드 유지 — 하위 호환)
    // Phase 2 이후 AccountService는 부모의 create() 메서드를 사용
    //
    // @deprecated Phase 2 이후 AccountService::createAccount()로 대체됨.
    //             AccountSetupController 등 기존 호출 코드가 있을 경우에만 유지.
    // ----------------------------------------------------------
    public function createAccount(int $userId, string $name, string $type, int $level, ?int $parentAccountId): void
    {
        $this->db->query(
            'INSERT INTO accounts (userId, accountName, accountType, accountLevel, parentAccountId)
             VALUES (?, ?, ?, ?, ?)',
            [$userId, $name, $type, $level, $parentAccountId]
        );
    }

    // ----------------------------------------------------------
    // 계정과목 삭제 (기존 메서드 유지 — 하위 호환)
    // Phase 2 이후 AccountService는 부모의 delete() 메서드를 사용
    //
    // @deprecated Phase 2 이후 AccountService::deleteAccount()로 대체됨.
    //             isSystem 체크가 이 메서드, AccountService, BaseRepository::delete()
    //             세 곳에서 중복 발생. 이후 이 메서드 제거 시 중복 해소됨.
    // ----------------------------------------------------------
    public function deleteAccount(int $accountId): void
    {
        $account = $this->findById($accountId);
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

        $this->db->query('DELETE FROM accounts WHERE accountId = ?', [$accountId]);
    }

    // ----------------------------------------------------------
    // 특정 계정 조회 (기존 메서드 유지 — 하위 호환)
    // 내부적으로 BaseRepository::findById() 호출
    // ----------------------------------------------------------
    public function getAccountById($account_id): ?array
    {
        return $this->findById((int)$account_id);
    }

    // ----------------------------------------------------------
    // 매핑된 항목 ID 목록 조회 (체크박스용)
    // ----------------------------------------------------------
    public function getMappedItemIds($account_id): array
    {
        return $this->db->query(
            "SELECT itemId FROM accountItemMap WHERE accountId = ?",
            [$account_id]
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    // ----------------------------------------------------------
    // 계정과목-관리항목 연결
    // ----------------------------------------------------------
    public function linkAccountItem($account_id, $item_id): void
    {
        $this->db->query(
            "INSERT INTO accountItemMap (accountId, itemId) VALUES (?, ?)",
            [$account_id, $item_id]
        );
    }

    // ----------------------------------------------------------
    // 매핑 정보 일괄 업데이트 (SP 호출)
    // ----------------------------------------------------------
    public function updateAccountMappings($account_id, $item_ids): void
    {
        $json_ids = json_encode($item_ids);
        try {
            $this->db->call('_SPUpdateAccountMappings', [$account_id, $json_ids]);
        } catch (Exception $e) {
            throw new Exception("매핑 저장 실패: " . $e->getMessage());
        }
    }
}
