<?php
// src/Database/ReportRepository.php
namespace App\Database;

use App\Core\Database;
use PDO;
use Exception;

class ReportRepository{
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // 전표 목록 조회 (전표번호로 그룹화)
    public function getJournalList($user_id, $from_date, $to_date) {
        try {
            // Database::call()은 단순 배열만 반환하므로,
            // FETCH_GROUP을 쓰기 위해 query()를 사용하여 SP 직접 호출
            $stmt = $this->db->query("CALL _SPGetJournalList(?, ?, ?)",
                [$user_id, $from_date, $to_date
            ]);

            // transactionNumber를 기준으로 그룹화하여 배열 반환
            // 컨트롤러나 뷰에서 별도 가공 없이 바로 루프 돌리기 좋음
            $logs = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

            // 커서 닫기 (SP 연속 호출 시 필수)
            $stmt->closeCursor();

            return $logs ?: [];
        } catch (Exception $e) {
            throw new Exception("전표 조회 실패: " . $e->getMessage());
        }
    }
}