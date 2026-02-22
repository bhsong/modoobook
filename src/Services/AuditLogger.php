<?php

// src/Services/AuditLogger.php

namespace App\Services;

use App\Core\Database;

class AuditLogger
{
    private Database $db;

    private ?int $userId;

    private string $ip;

    private string $userAgent;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->userId = isset($_SESSION['userId']) ? (int) $_SESSION['userId'] : null;
        $this->ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $this->userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    // ----------------------------------------------------------
    // 성공한 행위 기록
    // action  : 'journal_save', 'account_create', 'account_delete' 등
    // context : ['transactionId' => 1, 'amount' => 50000] 등
    //
    // ※ 감사 로그 저장 실패가 비즈니스 트랜잭션을 막으면 안 되므로
    //    내부 예외는 삼키고 error_log에만 기록.
    // ----------------------------------------------------------
    public function log(string $action, array $context = []): void
    {
        try {
            $this->db->query(
                "INSERT INTO audit_logs (userId, action, status, ip, userAgent, context)
                 VALUES (?, ?, 'success', ?, ?, ?)",
                [
                    $this->userId,
                    $action,
                    $this->ip,
                    $this->userAgent,
                    ! empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : null,
                ]
            );
        } catch (\Exception $e) {
            error_log("[AuditLogger] 로그 저장 실패 ({$action}): ".$e->getMessage());
        }
    }

    // ----------------------------------------------------------
    // 실패한 행위 기록 (status = 'failed')
    // ----------------------------------------------------------
    public function logFailure(string $action, string $reason, array $context = []): void
    {
        $context['reason'] = $reason;

        try {
            $this->db->query(
                "INSERT INTO audit_logs (userId, action, status, ip, userAgent, context)
                 VALUES (?, ?, 'failed', ?, ?, ?)",
                [
                    $this->userId,
                    $action,
                    $this->ip,
                    $this->userAgent,
                    json_encode($context, JSON_UNESCAPED_UNICODE),
                ]
            );
        } catch (\Exception $e) {
            error_log("[AuditLogger] 실패 로그 저장 실패 ({$action}): ".$e->getMessage());
        }
    }
}
