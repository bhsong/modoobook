<?php

// src/Controllers/AccountSetupController.php

namespace App\Controllers;

use App\Core\View;
use App\Database\AccountRepository;
use App\Database\ManagementRepository;
use Exception;

class AccountSetupController extends BaseController
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
        $account_id = $_GET['accountId'] ?? null;

        if (! $account_id) {
            header('Location: /index.php?action=accounts&error='.urlencode('잘못된 접근입니다.'));
            exit;
        }

        // POST 요청 (저장 로직)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_mapping'])) {
            $this->requireCsrf('account_item_setup', '/index.php?action=account_item_setup&accountId='.(int) $account_id);

            $items = $_POST['items'] ?? [];

            try {

                // Repository에 추가한 메소드 호출
                $this->accRepo->UpdateAccountMappings($account_id, $items);

                // 성공 시 목록으로 이동
                header('Location: /index.php?action=accounts');
                exit;
            } catch (Exception $e) {
                error_log('[AccountSetupController] 저장 오류: '.$e->getMessage());
                header('Location: /index.php?action=account_item_setup&accountId='.(int) $account_id.'&error='.urlencode('저장 중 오류가 발생했습니다.'));
                exit;
            }
        }

        // GET 요청 (화면에 뿌려줄 리스트 준비)
        // 각각의 리포지토리에서 목록 가져옴 (재사용성)
        $account = $this->accRepo->getAccountById($account_id);
        $all_items = $this->mgmtRepo->getAllItems($user_id);           // 전체 항목 (체크박스 목록)
        $checked_ids = $this->accRepo->getMappedItemIds($account_id);   // 이미 체크된 항목들

        // 뷰 호출
        View::render('account_item_setup_view', [
            'account' => $account,
            'all_items' => $all_items,
            'checked_ids' => $checked_ids,
        ]);
    }
}
