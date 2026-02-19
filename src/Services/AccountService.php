<?php
// src/Services/AccountService.php
namespace App\Services;

use App\Core\Database;
use App\Database\AccountRepository;

class AccountService
{
    private Database           $db;
    private AccountRepository  $accountRepo;
    private AuditLogger        $auditLogger;

    public function __construct(Database $db, AuditLogger $auditLogger)
    {
        $this->db          = $db;
        $this->accountRepo = new AccountRepository($db);
        $this->auditLogger = $auditLogger;
    }

    // ----------------------------------------------------------
    // 계정과목 생성
    // 반환: ['success' => bool, 'accountId' => int|null, 'error' => string|null]
    //
    // 처리 순서:
    //  1. 입력 값 기본 검증
    //  2. 부모 계정 조회 및 level 2 여부 확인
    //  3. 동일 부모 내 accountName 중복 검증
    //  4. accountType 자동 상속 후 create()
    //  5. 감사 로그 기록
    // ----------------------------------------------------------
    public function createAccount(array $data, int $userId): array
    {
        $account_name      = trim($data['accountName'] ?? '');
        $parent_account_id = (int)($data['parentAccountId'] ?? 0);

        // 1. 기본 검증
        if (empty($account_name) || $parent_account_id <= 0) {
            return [
                'success'   => false,
                'accountId' => null,
                'error'     => '계정명과 상위 계정을 입력하세요.',
            ];
        }

        // 2. 부모 계정 조회
        $parent_account = $this->accountRepo->findById($parent_account_id);
        if (!$parent_account) {
            return [
                'success'   => false,
                'accountId' => null,
                'error'     => '유효하지 않은 상위 계정입니다.',
            ];
        }

        // 사용자는 level 3만 생성 가능 → 부모는 반드시 level 2
        if ((int)$parent_account['accountLevel'] !== 2) {
            return [
                'success'   => false,
                'accountId' => null,
                'error'     => '중분류(2단계) 계정을 상위 계정으로 선택해야 합니다.',
            ];
        }

        // 3. accountName 중복 검증 (동일 userId + parentAccountId 기준)
        $existing = $this->accountRepo->findAll([
            'userId'          => $userId,
            'parentAccountId' => $parent_account_id,
            'accountName'     => $account_name,
        ]);
        if (!empty($existing)) {
            return [
                'success'   => false,
                'accountId' => null,
                'error'     => '동일한 상위 계정 아래 이미 존재하는 계정명입니다.',
            ];
        }

        // 4. 계정 생성 (accountType은 부모에서 자동 상속)
        $account_id = $this->accountRepo->create([
            'userId'          => $userId,
            'accountName'     => $account_name,
            'accountType'     => $parent_account['accountType'],
            'accountLevel'    => 3,
            'parentAccountId' => $parent_account_id,
        ]);

        // 5. 감사 로그
        $this->auditLogger->log('account_create', [
            'userId'      => $userId,
            'accountId'   => $account_id,
            'accountName' => $account_name,
        ]);

        return ['success' => true, 'accountId' => $account_id, 'error' => null];
    }

    // ----------------------------------------------------------
    // 계정과목 삭제
    // 반환: ['success' => bool, 'error' => string|null]
    //
    // 처리 순서:
    //  1. 계정 조회
    //  2. isSystem=1 거부
    //  3. 하위 계정 존재 시 거부
    //  4. journalEntries 연결 여부 확인 → 연결 시 거부
    //  5. 삭제 실행
    //  6. 감사 로그 기록
    // ----------------------------------------------------------
    public function deleteAccount(int $accountId, int $userId): array
    {
        // 1. 계정 조회
        $account = $this->accountRepo->findById($accountId);
        if (!$account) {
            return ['success' => false, 'error' => '계정을 찾을 수 없습니다.'];
        }

        // 2. 시스템 계정 거부
        if ((int)($account['isSystem'] ?? 0) === 1) {
            $this->auditLogger->logFailure('account_delete', '시스템 계정 삭제 시도', [
                'accountId' => $accountId,
                'userId'    => $userId,
            ]);
            return ['success' => false, 'error' => '시스템 계정은 삭제할 수 없습니다.'];
        }

        // 3. 하위 계정 존재 시 거부
        $children = $this->accountRepo->findAll(['parentAccountId' => $accountId]);
        if (!empty($children)) {
            return ['success' => false, 'error' => '하위 계정이 존재하여 삭제할 수 없습니다. 하위 계정을 먼저 삭제하세요.'];
        }

        // 4. 해당 계정에 연결된 journalEntries 존재 시 거부
        $linked_entry = $this->db->query(
            'SELECT entryId FROM journalEntries WHERE accountId = ? LIMIT 1',
            [$accountId]
        )->fetch();

        if ($linked_entry) {
            return ['success' => false, 'error' => '해당 계정에 연결된 전표가 있어 삭제할 수 없습니다.'];
        }

        // 5. 삭제 실행
        try {
            $this->accountRepo->delete($accountId);
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }

        // 6. 감사 로그
        $this->auditLogger->log('account_delete', [
            'userId'    => $userId,
            'accountId' => $accountId,
        ]);

        return ['success' => true, 'error' => null];
    }
}
