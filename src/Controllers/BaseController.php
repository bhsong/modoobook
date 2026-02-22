<?php

namespace App\Controllers;

use App\Core\CsrfGuard;
use App\Core\Database;
use App\Services\AuditLogger;

/**
 * POST 엔드포인트 공통: CSRF 검증·인증 체크 헬퍼.
 * Refactor R4 — 컨트롤러 연동 시 requireCsrf / requireAuth 재사용.
 */
abstract class BaseController
{
    protected AuditLogger $auditLogger;

    public function __construct(Database $db)
    {
        $this->auditLogger = new AuditLogger($db);
    }

    /**
     * CSRF 토큰 검증. 실패 시 감사 로그 → 403 → 리다이렉트 후 exit.
     *
     * @param  string  $action  감사 로그용 액션명 (예: 'journal_save')
     * @param  string  $redirectOnFail  실패 시 이동할 URL (예: '/index.php?action=journal_entry')
     */
    protected function requireCsrf(string $action, string $redirectOnFail): void
    {
        if (! CsrfGuard::validate()) {
            $this->auditLogger->logFailure('csrf_validation_failed', '유효하지 않은 요청입니다.', ['action' => $action]);
            http_response_code(403);
            header('Location: '.$redirectOnFail.(str_contains($redirectOnFail, '?') ? '&' : '?').'error='.urlencode('유효하지 않은 요청입니다.'));
            exit;
        }
    }

    /**
     * 세션 인증 확인. 미로그인 시 리다이렉트 후 exit, 존재 시 userId 반환.
     *
     * @param  string  $redirectTo  미인증 시 이동할 URL
     * @return int $_SESSION['userId']
     */
    protected function requireAuth(string $redirectTo = '/index.php?action=login'): int
    {
        if (! isset($_SESSION['userId'])) {
            header('Location: '.$redirectTo);
            exit;
        }

        return (int) $_SESSION['userId'];
    }
}
