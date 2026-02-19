<?php
// src/Controllers/AccountController.php
namespace App\Controllers;

use App\Core\View;
use App\Core\Database;
use App\Database\AccountRepository;
use App\Services\AuditLogger;
use App\Services\AccountService;
use Exception;

class AccountController
{
    private $db;
    private $repo;
    private $accountService;

    public function __construct($db)
    {
        $this->db   = $db;
        $audit_logger = new AuditLogger($db);

        // GET 조회용 (단순 조회는 Service 불필요)
        $this->repo = new AccountRepository($db);

        // POST 처리용
        $this->accountService = new AccountService($db, $audit_logger);
    }

    public function index()
    {
        if (!isset($_SESSION['userId'])) {
            header("Location: /index.php?action=login");
            exit;
        }

        $user_id = $_SESSION['userId'];

        // ----------------------------------------------------------
        // POST: 계정 추가 → AccountService 위임
        // ----------------------------------------------------------
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_account'])) {
            $result = $this->accountService->createAccount($_POST, $user_id);

            if (!$result['success']) {
                header("Location: /index.php?action=accounts&error=" . urlencode($result['error']));
                exit;
            }

            header("Location: /index.php?action=accounts");
            exit;
        }

        // ----------------------------------------------------------
        // POST: 계정 삭제 → AccountService 위임
        // ----------------------------------------------------------
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
            $account_id = (int)($_POST['accountId'] ?? 0);
            $result     = $this->accountService->deleteAccount($account_id, $user_id);

            if (!$result['success']) {
                header("Location: /index.php?action=accounts&error=" . urlencode($result['error']));
                exit;
            }

            header("Location: /index.php?action=accounts");
            exit;
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
