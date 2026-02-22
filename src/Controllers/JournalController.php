<?php

// src/Controllers/JournalController.php

namespace App\Controllers;

use App\Core\View;
use App\Database\AccountRepository;
use App\Database\JournalRepository;
use App\Services\JournalService;

class JournalController extends BaseController
{
    private $journalRepo;

    private $accRepo;

    private $journalService;

    public function __construct($db)
    {
        parent::__construct($db);
        $this->journalRepo = new JournalRepository($db);
        $this->accRepo = new AccountRepository($db);
        $this->journalService = new JournalService($this->journalRepo, $this->auditLogger);
    }

    // ----------------------------------------------------------
    // 전표 입력 화면 (GET)
    // ----------------------------------------------------------
    public function index()
    {
        $user_id = $this->requireAuth();

        $accounts = $this->accRepo->getLeafAccounts($user_id);
        $account_map = $this->journalRepo->getAccountItemMap($user_id);

        View::render('journal_entry_view', [
            'accounts' => $accounts,
            'account_map' => $account_map,
        ]);
    }

    // ----------------------------------------------------------
    // 전표 저장 처리 (POST) — JournalService에 위임
    // ----------------------------------------------------------
    public function save()
    {
        $user_id = $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?action=journal_entry');
            exit;
        }

        $this->requireCsrf('journal_save', '/index.php?action=journal_entry');

        $result = $this->journalService->save($_POST, $user_id);

        if (! $result['success']) {
            header('Location: /index.php?action=journal_entry&error='.urlencode($result['error']));
            exit;
        }

        header('Location: /index.php?action=journal_list');
        exit;
    }
}
