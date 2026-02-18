<?php
// src/Controllers/LedgerController.php
namespace App\Controllers;

use App\Database\LedgerRepository;
use App\Database\AccountRepository;
use Exception;

use App\Core\View;
use App\Core\Database;

class LedgerController {
    private $db;
    private $ledgerRepo;
    private $accRepo;

    public function __construct($db) {
        $this->db = $db;
        $this->ledgerRepo = new LedgerRepository($db);
        $this->accRepo = new AccountRepository($db);
    }

    public function index() {
        // 로그인 체크
        if(!isset($_SESSION['userId'])) {
            header("Location: /index.php?action=login");
            exit;
        }

        $user_id = $_SESSION['userId'];

        // 계정 목록 가져오기 
        $account_list = $this->accRepo->getAllAccounts($user_id);

        // 조회 조건
        $acc_id = $_GET['account_id'] ?? '';
        $from_date = $_GET['from_date'] ?? date('Y-m-01');
        $to_date = $_GET['to_date'] ?? date('Y-m-d');

        $isSearch = !empty($acc_id);
        $ledger_data = [];
        $error_message = null;

        if ($isSearch) {
            try{
                $ledger_data = $this->ledgerRepo->getLedgerData($user_id, $acc_id, $from_date, $to_date); 
            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }
        }
        View::render('ledger_view', [
            'account_list' => $account_list,
            'acc_id' => $acc_id,
            'from_date' => $from_date,
            'to_date' => $to_date,
            'isSearch' => $isSearch,
            'ledger_data' => $ledger_data,
            'error_message' => $error_message
        ]);
    }
}


