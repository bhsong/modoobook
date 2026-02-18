<?php
// src/Controllers/AccountController.php
namespace App\Controllers;

use App\Core\View;
use App\Core\Database;
use App\Database\AccountRepository;
use Exception;

class AccountController {
    private $db;
    private $repo;

    public function __construct($db) {
        $this->db   = $db;
        $this->repo = new AccountRepository($db);
    }

    public function index() {
        if (!isset($_SESSION['userId'])) {
            header("Location: /index.php?action=login");
            exit;
        }

        $user_id = $_SESSION['userId'];

        // ----------------------------------------------------------
        // POST: 계정 추가
        // ----------------------------------------------------------
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_account'])) {
            $name            = trim($_POST['accountName'] ?? '');
            $parentAccountId = (int)($_POST['parentAccountId'] ?? 0);

            if (empty($name) || $parentAccountId <= 0) {
                header("Location: /index.php?action=accounts&error=" . urlencode("계정명과 상위 계정을 입력하세요."));
                exit;
            }

            // 상위 계정에서 accountType, level 자동 결정
            $parentAccount = $this->repo->getAccountById($parentAccountId);
            if (!$parentAccount) {
                header("Location: /index.php?action=accounts&error=" . urlencode("유효하지 않은 상위 계정입니다."));
                exit;
            }

            // 사용자는 중분류(level 2) 하위로만 추가 가능 → level 3 계정 생성
            if ((int)$parentAccount['accountLevel'] !== 2) {
                header("Location: /index.php?action=accounts&error=" . urlencode("중분류(2단계) 계정을 상위 계정으로 선택해야 합니다."));
                exit;
            }

            $type     = $parentAccount['accountType'];
            $newLevel = 3;
            $this->repo->createAccount($user_id, $name, $type, $newLevel, $parentAccountId);

            header("Location: /index.php?action=accounts");
            exit;
        }

        // ----------------------------------------------------------
        // POST: 계정 삭제
        // ----------------------------------------------------------
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
            $accountId = (int)($_POST['accountId'] ?? 0);
            try {
                $this->repo->deleteAccount($accountId);
            } catch (Exception $e) {
                header("Location: /index.php?action=accounts&error=" . urlencode($e->getMessage()));
                exit;
            }
            header("Location: /index.php?action=accounts");
            exit;
        }

        // ----------------------------------------------------------
        // GET: 트리 구조 조회
        // ----------------------------------------------------------
        $account_tree = $this->repo->getAccountTree($user_id);

        View::render('accounts_view', [
            'account_tree' => $account_tree,
        ]);
    }
}
