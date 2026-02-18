<?php
// src/Controllers/JournalController.php
namespace App\Controllers;

use App\Database\JournalRepository;
use App\Database\AccountRepository;
use Exception;

use App\Core\View;
use App\Core\Database;

class JournalController {
    private $db;
    private $journalRepo;
    private $accRepo;

    public function __construct($db) {
        $this->db = $db;
        $this->journalRepo = new JournalRepository($db);
        $this->accRepo = new AccountRepository($db);
    }
    
    // 화면 출력 (GET)
    public function index() {
        if (!isset($_SESSION['userId'])) {
            header("Location: /index.php?action=login");
            exit;
        }

        $user_id = $_SESSION['userId'];

        // 전표 입력용 계정 목록 (level=3 소분류만, optgroup용 parentName 포함)
        $accounts = $this->accRepo->getLeafAccounts($user_id);

        // 매핑 정보 (JournalRepository 사용)
        $account_map = $this->journalRepo->getAccountItemMap($user_id);

        // 뷰 호출
        View::render('journal_entry_view', [
            'accounts' => $accounts,
            'account_map' => $account_map
        ]);
    }

    // 전표 저장 처리 (POST)
    public function save() {
        if (!isset($_SESSION['userId'])) die("로그인이 필요합니다.");
        $user_id = $_SESSION['userId'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 입력 데이터 배열로 가공 
            $data = [];

            // acc 배열이 있는지 확인
            if(isset($_POST['acc']) && is_array($_POST['acc'])) {
                foreach ($_POST['acc'] as $i => $acc_id) {
                    if (empty($acc_id)) continue;

                    $data[] = [
                        'acc' => $acc_id,
                        'dr' => $_POST['dr'][$i] ?? 0,
                        'cr' => $_POST['cr'][$i] ?? 0,
                        'item_id' => $_POST['item_id'][$i] ?? null,     // JS가 동적으로 생성한 name값
                        'item_val' => $_POST['item_val'] ?? ''
                    ];
                }
            }

            // 유효성 검사 (차대변 합계 등은 JS에서 했지만 서버에서도 체크 가능)
            if (empty($data)) {
                die("입력된 전표 데이터가 없습니다.");
            }

            try {
                // JSON 변환 및 저장
                $jsonData = json_encode($data);
                $this->journalRepo->saveComplexTransaction(
                      $user_id
                    , $_POST['tr_date']
                    , $_POST['description']
                    , $jsonData
                );

                // 성공 시 다시 입력 화면으로 리다이렉트
                echo "<script>alert('전표가 성공적으로 저장되었습니다.'); location.href='/index.php?action=journal_entry';</script>";
                exit;
            } catch (Exception $e) {
                die($e->getMessage());
            }
        }
    }
}
