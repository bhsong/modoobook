<?php

// src/Database/ReportRepository.php

namespace App\Database;

use App\Core\Database;
use Exception;
use PDO;

class ReportRepository extends BaseRepository
{
    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    protected function getTable(): string
    {
        return 'transactions';
    }

    // ----------------------------------------------------------
    // 전표 목록 조회 (전표번호로 그룹화, SP 호출)
    // ----------------------------------------------------------
    public function getJournalList(int $user_id, string $from_date, string $to_date): array
    {
        try {
            $stmt = $this->db->query(
                'CALL _SPGetJournalList(?, ?, ?)',
                [$user_id, $from_date, $to_date]
            );

            $logs = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            return $logs ?: [];
        } catch (Exception $e) {
            throw new Exception('전표 조회 실패: '.$e->getMessage());
        }
    }

    // ----------------------------------------------------------
    // 월별 수입/지출 요약 (ReportBuilder 사용 예시)
    // ----------------------------------------------------------
    public function getMonthlySummary(int $userId, int $year): array
    {
        return (new \App\Core\ReportBuilder($this->db))
            ->select([
                'a.accountType',
                'MONTH(t.transactionDate) as month',
                'SUM(je.debitAmount) as totalDebit',
                'SUM(je.creditAmount) as totalCredit',
            ])
            ->from('transactions', 't')
            ->join('INNER', 'journalEntries', 'je', 't.transactionId = je.transactionId')
            ->join('INNER', 'accounts', 'a', 'je.accountId = a.accountId')
            ->where('t.userId', $userId)
            ->whereBetween('t.transactionDate', $year.'-01-01', $year.'-12-31')
            ->groupBy(['a.accountType', 'month'])
            ->orderBy('month')
            ->get();
    }

    // ----------------------------------------------------------
    // 이번 달 지출 합계 (대시보드용)
    // accounts.accountType = 'EXPENSE', 현재 월 기준
    // ----------------------------------------------------------
    public function getCurrentMonthExpense(int $userId): int
    {
        // YEAR()/MONTH() 함수 대신 PHP로 날짜 범위 계산 (PDO 바인딩 가능)
        $month_start = date('Y-m-01');
        $month_end = date('Y-m-t');

        $result = (new \App\Core\ReportBuilder($this->db))
            ->select(['COALESCE(SUM(je.debitAmount), 0) as totalExpense'])
            ->from('transactions', 't')
            ->join('INNER', 'journalEntries', 'je', 't.transactionId = je.transactionId')
            ->join('INNER', 'accounts', 'a', 'je.accountId = a.accountId')
            ->where('t.userId', $userId)
            ->where('a.accountType', 'EXPENSE')
            ->whereBetween('t.transactionDate', $month_start, $month_end)
            ->get();

        return (int) ($result[0]['totalExpense'] ?? 0);
    }
}
