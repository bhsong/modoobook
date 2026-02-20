<?php
// src/Services/JournalService.php
namespace App\Services;

use App\Database\JournalRepository;

class JournalService
{
    private JournalRepository $journalRepo;
    private AuditLogger       $auditLogger;

    public function __construct(JournalRepository $journalRepo, AuditLogger $auditLogger)
    {
        $this->journalRepo = $journalRepo;
        $this->auditLogger = $auditLogger;
    }

    // ----------------------------------------------------------
    // 전표 저장
    // 반환: ['success' => bool, 'transactionId' => int|null, 'error' => string|null]
    //
    // 처리 순서:
    //  1. entries 배열 조합 (POST 데이터 정제)
    //  2. 최소 2개 이상의 분개 라인 검증
    //  3. 날짜 형식 검증 (YYYY-MM-DD)
    //  4. PHP 레벨 차대변 균형 검증 (SP의 이중 안전망)
    //  5. SP 위임 (saveComplexTransaction)
    //  6. 감사 로그 기록
    // ----------------------------------------------------------
    public function save(array $formData, int $userId): array
    {
        // entries 배열 조합
        $entries = [];
        if (isset($formData['acc']) && is_array($formData['acc'])) {
            foreach ($formData['acc'] as $i => $acc_id) {
                if (empty($acc_id)) {
                    continue;
                }
                $entries[] = [
                    'acc'      => $acc_id,
                    'dr'       => (float)($formData['dr'][$i] ?? 0),
                    'cr'       => (float)($formData['cr'][$i] ?? 0),
                    'item_id'  => $formData['item_id'][$i] ?? null,
                    'item_val' => $formData['item_val'][$i] ?? '',
                ];
            }
        }

        // 1. 최소 2개 이상의 분개 라인 검증
        if (count($entries) < 2) {
            $this->auditLogger->logFailure('journal_save', '분개 라인 부족', ['userId' => $userId]);
            return [
                'success'       => false,
                'transactionId' => null,
                'error'         => '최소 2개 이상의 분개 라인이 필요합니다.',
            ];
        }

        // 2. 날짜 형식 검증 (YYYY-MM-DD)
        $date = $formData['tr_date'] ?? '';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->auditLogger->logFailure('journal_save', '날짜 형식 오류', ['userId' => $userId]);
            return [
                'success'       => false,
                'transactionId' => null,
                'error'         => '날짜 형식이 올바르지 않습니다. (YYYY-MM-DD)',
            ];
        }

        // 3. PHP 레벨 차대변 균형 검증
        if (!$this->validateBalance($entries)) {
            $this->auditLogger->logFailure('journal_save', '차대변 불일치', ['userId' => $userId]);
            return [
                'success'       => false,
                'transactionId' => null,
                'error'         => '차변 합계와 대변 합계가 일치하지 않습니다.',
            ];
        }

        // 4. SP 위임
        try {
            $json_data   = json_encode($entries);
            $description = $formData['description'] ?? '';

            $this->journalRepo->saveComplexTransaction($userId, $date, $description, $json_data);

            // 5. 성공 감사 로그
            $this->auditLogger->log('journal_save', ['userId' => $userId, 'date' => $date]);

            return ['success' => true, 'transactionId' => null, 'error' => null];

        } catch (\Exception $e) {
            $this->auditLogger->logFailure('journal_save', $e->getMessage(), ['userId' => $userId]);
            // 감사 로그에는 원본 메시지 기록, 사용자 응답은 일반화 (PDO 내부 메시지 노출 방지)
            $user_message = (str_starts_with((string)$e->getCode(), '45'))
                ? $e->getMessage()
                : '전표 저장 중 오류가 발생했습니다.';
            return ['success' => false, 'transactionId' => null, 'error' => $user_message];
        }
    }

    // ----------------------------------------------------------
    // 차대변 균형 검증
    // dr 합계 == cr 합계 이고 0보다 크면 true
    // ----------------------------------------------------------
    private function validateBalance(array $entries): bool
    {
        $total_dr = 0.0;
        $total_cr = 0.0;

        foreach ($entries as $entry) {
            $total_dr += (float)($entry['dr'] ?? 0);
            $total_cr += (float)($entry['cr'] ?? 0);
        }

        return $total_dr > 0 && abs($total_dr - $total_cr) < 0.001;
    }
}
