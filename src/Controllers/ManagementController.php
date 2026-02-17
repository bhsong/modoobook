<?php
// src/Controllers/ManagementController.php
namespace App\Controllers;

use App\Database\ManagementRepository;
use Exception;

use App\Core\View;
use App\Core\Database;

class ManagementController {
    private $db;
    private $repo;

    public function __construct($db) {
        $this->db = $db;
        $this->repo = new ManagementRepository($db);
    }

    public function index() {
        // 로그인 체크
        if (!isset($_SESSION['userId'])) {
            header("Location: /index.php?action=login");
            exit;
        }

        $user_id = $_SESSION['userId'];

        // POST 요청 (항목 추가)
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_item'])) {
            $item_name = $_POST['itemName'];

            // 공백 입력 방지 등 유효성 검사 추가 가능
            if (!empty($item_name)) {
                $this->repo->addItem($user_id, $item_name);
            }

            // PRG 패턴: 저장 후 리다이렉트
            header("Location: /index.php?action=management");
            exit;
        }
        
        // GET 요청 (목록 조회)
        $item_list = $this->repo->getItems($user_id);

        // 뷰 호출
        View::render('management_view', [
            'item_list' => $item_list
        ]);
    }
}