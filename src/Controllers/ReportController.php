<?php
// src/Controllers/ReportController.php
namespace App\Controllers;

use App\Database\ReportRepository;
use Exception;

use App\Core\View;
use App\Core\Database;

class ReportController {
    private $db;
    private $repo;

    public function __construct($db) {
        $this->db = $db;
        $this->repo = new ReportRepository($db);
    }

    // 전표 조회 화면 (journal_list)
    public function journalList() {
        if (!isset($_SESSION['userId'])) {
            header("Location: /index.php?action=login");
            exit;
        }
        $user_id = $_SESSION['userId'];

        // 조회 조건 설정 (기본값: 이번 달 1일 ~ 오늘)
        $from_date = $_GET['from_date'] ?? date('Y-m-01');
        $to_date = $_GET['to_date'] ?? date('Y-m-d');

        // 검색 버튼을 눌렀는지 확인 (from_date 파라미터 유무)
        $isSearch = isset($_GET['from_date']);
        $logs = [];
        $error_msg = null;

        // 검색 조건이 있을 때만 DB 조회
        if ($isSearch) {
            try {
                $logs = $this->repo->getJournalList($user_id, $from_date, $to_date);
            } catch (Exception $e) {
                // 에러 발생 시 화면에 표시하기 위해 변수에 담음
                $error_msg = $e->getMessage();
            }
        }

        // 뷰 호출
        View::render('journal_list_view', [
            'logs' => $logs,
            'from_date' => $from_date,
            'to_date' => $to_date,
            'isSearch' => $isSearch,
            'error_msg' => $error_msg
        ]);
    }
}