<?php

// src/Controllers/AccountController.php

namespace App\Controllers;

use App\Core\View;
use App\Database\AccountRepository;
use App\Services\AccountService;

class AccountController extends BaseController
{
    private $repo;

    private $accountService;

    public function __construct($db)
    {
        parent::__construct($db);
        $this->repo = new AccountRepository($db);
        $this->accountService = new AccountService($this->repo, $this->auditLogger);
    }

    public function index()
    {
        $user_id = $this->requireAuth();

        // ----------------------------------------------------------
        // POST: 계정 추가/삭제 — CSRF 한 번만 검증 (R5)
        // ----------------------------------------------------------
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf('accounts', '/index.php?action=accounts');

            if (isset($_POST['add_account'])) {
                $result = $this->accountService->createAccount($_POST, $user_id);

                if (! $result['success']) {
                    header('Location: /index.php?action=accounts&error='.urlencode($result['error']));
                    exit;
                }

                header('Location: /index.php?action=accounts');
                exit;
            }

            if (isset($_POST['delete_account'])) {
                $account_id = (int) ($_POST['accountId'] ?? 0);
                $result = $this->accountService->deleteAccount($account_id, $user_id);

                if (! $result['success']) {
                    header('Location: /index.php?action=accounts&error='.urlencode($result['error']));
                    exit;
                }

                header('Location: /index.php?action=accounts');
                exit;
            }
        }

        // ----------------------------------------------------------
        // GET: 트리 구조 조회 (단순 조회, Service 불필요)
        // ----------------------------------------------------------
        $account_tree = $this->repo->getAccountTree($user_id);

        View::render('accounts_view', [
            'account_tree' => $account_tree,
        ]);
    }
}
