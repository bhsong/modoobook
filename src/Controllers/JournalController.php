<?php
// src/Controllers/JournalController.php
namespace App\Controllers;

use App\Database\JournalRepository;
use App\Database\AccountRepository;
use App\Services\AuditLogger;
use App\Services\JournalService;
use Exception;

use App\Core\View;
use App\Core\Database;

class JournalController
{
    private $db;
    private $journalRepo;
    private $accRepo;
    private $journalService;

    public function __construct($db)
    {
        $this->db      = $db;
        $audit_logger  = new AuditLogger($db);

        // index() 에서 직접 사용
        $this->journalRepo = new JournalRepository($db);
        $this->accRepo     = new AccountRepository($db);

        // save() 는 Service에 위임
        $this->journalService = new JournalService($db, $audit_logger);
    }

    // ----------------------------------------------------------
    // 전표 입력 화면 (GET)
    // ----------------------------------------------------------
    public function index()
    {
        if (!isset($_SESSION['userId'])) {
            header("Location: /index.php?action=login");
            exit;
        }

        $user_id = $_SESSION['userId'];

        $accounts    = $this->accRepo->getLeafAccounts($user_id);
        $account_map = $this->journalRepo->getAccountItemMap($user_id);

        View::render('journal_entry_view', [
            'accounts'    => $accounts,
            'account_map' => $account_map,
        ]);
    }

    // ----------------------------------------------------------
    // 전표 저장 처리 (POST) — JournalService에 위임
    // ----------------------------------------------------------
    public function save()
    {
        if (!isset($_SESSION['userId'])) {
            header("Location: /index.php?action=login");
            exit;
        }
        $user_id = $_SESSION['userId'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /index.php?action=journal_entry");
            exit;
        }

        $result = $this->journalService->save($_POST, $user_id);

        if (!$result['success']) {
            header("Location: /index.php?action=journal_entry&error=" . urlencode($result['error']));
            exit;
        }

        header("Location: /index.php?action=journal_list");
        exit;
    }
}
