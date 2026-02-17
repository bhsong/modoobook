<?php
// src/Controllers/AccountSetupController.php
namespace App\Controllers;

use App\Database\AccountRepository;
use Exception;

use App\Core\View;
use App\Core\Database;

class AccountController {
    private $db;
    private $repo;

    public function __construct($db) {
        $this->db = $db;
        $this->repo = new AccountRepository($db);
    }

    public function index() {
        // 로그인 체크 (필수)
        if (!isset($_SESSION['userId'])) {
            header("Location: /index.php?action=login");
            exit;
        }

        $user_id = $_SESSION['userId'];

        // POST 요청 처리 (계정 추가 로직)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_account'])) {
            $name = $_POST['accountName'];
            $type = $_POST['accountType'];

            $this->repo->createAccount($user_id, $name, $type);

            // 중요: 새로고침 시 중복 등록 방지를 위해 리다이렉트 (PRG 패턴)
            header("Location: /index.php?action=accounts");
            exit;
        }

        // GET 요청 처리 (목록 조회)
        $account_list= $this->repo->getAccounts($user_id);

        // 뷰 호출
        View::render('accounts_view', [
            'account_list' => $account_list
        ]);
    }
}