<?php

namespace App\Core;

/**
 * CSRF Synchronizer Token Pattern — 토큰 생성·검증·hidden input 헬퍼.
 * 세션 기반 1회용 토큰으로 위조 요청을 차단합니다.
 */
class CsrfGuard
{
    private const SESSION_KEY = 'csrf_token';

    private const POST_KEY = '_csrf_token';

    /**
     * 세션이 시작되지 않았으면 session_start() 호출.
     * R1/R2: session_status() 기반 감지, 중복 제거용 헬퍼.
     */
    private static function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * 토큰 생성 후 세션에 저장하고 토큰 문자열을 반환합니다.
     */
    public static function generate(): string
    {
        self::ensureSession();
        $token = bin2hex(random_bytes(32));
        $_SESSION[self::SESSION_KEY] = $token;

        return $token;
    }

    /**
     * POST 요청의 토큰을 검증합니다.
     * 성공 시 토큰을 소비(재발급)하여 1회용으로 만듭니다.
     * Security #7: hash_equals() 사용으로 타이밍 어택 방지.
     */
    public static function validate(): bool
    {
        self::ensureSession();
        $sessionToken = $_SESSION[self::SESSION_KEY] ?? null;
        $postToken = $_POST[self::POST_KEY] ?? null;

        if ($sessionToken === null || $postToken === null) {
            return false;
        }
        if (! hash_equals($sessionToken, $postToken)) {
            return false;
        }

        // 검증 성공 시 토큰 소비 후 재발급 (1회용)
        self::generate();

        return true;
    }

    /**
     * hidden input HTML을 반환합니다. 폼에 삽입하여 POST 시 토큰을 전송합니다.
     * XSS 방지를 위해 토큰 값은 h()로 이스케이프합니다.
     */
    public static function tokenField(): string
    {
        self::ensureSession();
        if (empty($_SESSION[self::SESSION_KEY])) {
            self::generate();
        }
        $token = $_SESSION[self::SESSION_KEY];

        return '<input type="hidden" name="'.self::POST_KEY.'" value="'.h($token).'">';
    }
}
