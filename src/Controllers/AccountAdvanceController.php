<?php
// src/Controllers/AccountAdvanceController.php

namespace App\Controllers;

use App\Database\AccountRepository;
use App\Database\ManagementRepository;
use Exception;

use App\Core\View;
use App\Core\Database;

class AccountAdvanceController {
    private $db;
    private $accRepo;
    private $mgmtRepo;

    public function __construct($db) {
        $this->db = $db;
        // 두 가지 리포지토리를 모두 로드
        $this->accRepo = new AccountRepository($db);
        $this->mgmtRepo = new ManagementRepository($db);
    }

    public function index() {
        // 로그인 체크
        if(!isset($_SESSION['userId'])) {
            header("Location: /index.php?action=login");
            exit;
        }

        $user_id = $_SESSION['userId'];

        // POST 요청 (연결 저장)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['link_item'])) {
            $account_id = $_POST['account_id'];
            $item_id = $_POST['item_id'];

            // Repository에 추가한 메소드 호출
            $this->accRepo->linkAccountItem($account_id, $item_id);
        
            // 새로고침 시 중복 전송 방지
            header("Location: /index.php?action=accounts_advance");
            exit;
        }

        // GET 요청 (화면에 뿌려줄 리스트 준비)
        // 각각의 리포지토리에서 목록 가져옴 (재사용성)
        $account_list = $this->accRepo->getLeafAccounts($user_id);
        $item_list = $this->mgmtRepo->getAllItems($user_id);

        // 뷰 호출
        View::render('accounts_advance_view', [
            'account_list' => $account_list,
            'item_list' => $item_list
        ]);
    }
}