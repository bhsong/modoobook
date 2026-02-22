<?php

// src/Database/LedgerRepository.php

namespace App\Database;

use App\Core\Database;

class LedgerRepository extends BaseRepository
{
    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    protected function getTable(): string
    {
        return 'journalEntries';
    }

    // journalEntries 테이블의 실제 PK는 entryId
    protected function getPrimaryKey(): string
    {
        return 'entryId';
    }

    // ----------------------------------------------------------
    // 계정별 원장 조회 (SP 호출)
    // ----------------------------------------------------------
    public function getLedgerData(int $user_id, int $acc_id, string $from_date, string $to_date): array
    {
        return $this->db->call('_SPGetAccountLedger', [
            $user_id, $acc_id, $from_date, $to_date,
        ]);
    }
}
