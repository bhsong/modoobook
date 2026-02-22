<?php

// src/Controllers/AccountAdvanceController.php

namespace App\Controllers;

use App\Core\View;
use App\Database\AccountRepository;
use App\Database\ManagementRepository;

class AccountAdvanceController extends BaseController
{
    private $db;

    private $accRepo;

    private $mgmtRepo;

    public function __construct($db)
    {
        parent::__construct($db);
        $this->db = $db;
        $this->accRepo = new AccountRepository($this->db);
        $this->mgmtRepo = new ManagementRepository($this->db);
    }

    public function index()
    {
        $user_id = $this->requireAuth();

        // POST 요청 (연결 저장)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['link_item'])) {
            $this->requireCsrf('accounts_advance', '/index.php?action=accounts_advance');

            $account_id = $_POST['account_id'];
            $item_id = $_POST['item_id'];

            // Repository에 추가한 메소드 호출
            $this->accRepo->linkAccountItem($account_id, $item_id);

            // 새로고침 시 중복 전송 방지
            header('Location: /index.php?action=accounts_advance');
            exit;
        }

        // GET 요청 (화면에 뿌려줄 리스트 준비)
        // 각각의 리포지토리에서 목록 가져옴 (재사용성)
        $account_list = $this->accRepo->getLeafAccounts($user_id);
        $item_list = $this->mgmtRepo->getAllItems($user_id);

        // 뷰 호출
        View::render('accounts_advance_view', [
            'account_list' => $account_list,
            'item_list' => $item_list,
        ]);
    }
}
