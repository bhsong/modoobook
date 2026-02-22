<?php

/**
 * PHPUnit Bootstrap
 * Docker Compose 환경 기준
 */
require_once dirname(__DIR__).'/vendor/autoload.php';

// 테스트 전용 환경변수 로드
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// 테스트 DB는 본 DB와 반드시 분리
// .env에 TEST_DB_NAME=accountbook_test 추가 필요
if (($_ENV['APP_ENV'] ?? '') === 'production') {
    throw new \RuntimeException(
        'PHPUnit은 production 환경에서 실행할 수 없습니다. APP_ENV=test로 설정하세요.'
    );
}

// 세션 초기화 (Controller 테스트 시 필요)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
