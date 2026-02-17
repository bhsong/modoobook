<?php
// src/Controllers/AccountSetupController.php
namespace App\Controllers;

use App\Database\AccountRepository;
use App\Database\ManagementRepository;
use Exception;

use App\Core\View;
use App\Core\Database;

class AccountSetupController {
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
        $account_id = $_GET['accountId'] ?? null;

        if(!$account_id) {
            die("잘못된 접근입니다. (계정 ID 누락)");
        }

        // POST 요청 (저장 로직)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_mapping'])) {
            $items = $_POST['items'] ?? [];

            try {

                // Repository에 추가한 메소드 호출
                $this->accRepo->UpdateAccountMappings($account_id, $items);
        
                // 성공 시 목록으로 이동
                header("Location: /index.php?action=accounts");
                exit;
            } catch (Exception $e) {
                die("저장 중 오류 발생 " . $e->getMessage());
            }
        }

        // GET 요청 (화면에 뿌려줄 리스트 준비)
        // 각각의 리포지토리에서 목록 가져옴 (재사용성)
        $account = $this->accRepo->getAccountById($account_id);
        $all_items = $this->mgmtRepo->getItems($user_id);           // 전체 항목 (체크박스 목록)
        $checked_ids = $this->accRepo->getMappedItemIds($account_id);   // 이미 체크된 항목들

        // 뷰 호출
        View::render('account_item_setup_view', [
            'account' => $account,
            'all_items' => $all_items,
            'checked_ids' => $checked_ids
        ]);
    }
}