<?php
// src/Database/LedgerRepository.php
namespace App\Database;

use App\Core\Database;

class LedgerRepository {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function getLedgerData($user_id, $acc_id, $from_date, $to_date) {
        return $this->db->call('_SPGetAccountLedger', [
            $user_id, $acc_id, $from_date, $to_date
        ]);
    }
}