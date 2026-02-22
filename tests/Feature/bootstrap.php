<?php

/**
 * tests/Feature/bootstrap.php
 * Feature(통합) 테스트 전용 부트스트랩
 *
 * - 실제 DB 컨테이너에 연결
 * - APP_ENV=testing 강제 (운영 DB 보호)
 * - 각 테스트 전후 트랜잭션 롤백으로 데이터 격리
 */

declare(strict_types=1);

// 운영 환경 보호: APP_ENV=testing 이 아니면 즉시 종료
$appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? '');
if ($appEnv !== 'testing') {
    fwrite(STDERR, "\n");
    fwrite(STDERR, "========================================================\n");
    fwrite(STDERR, " [Feature Test] 운영 환경 실행 차단\n");
    fwrite(STDERR, " APP_ENV=testing 이 아닙니다. (현재: '{$appEnv}')\n");
    fwrite(STDERR, " 실행 방법: APP_ENV=testing ./vendor/bin/phpunit tests/Feature/\n");
    fwrite(STDERR, " 또는:     ./scripts/test.sh feature\n");
    fwrite(STDERR, "========================================================\n");
    fwrite(STDERR, "\n");
    exit(1);
}

// Composer 오토로드
require_once dirname(__DIR__, 2).'/vendor/autoload.php';

// .env 로드 (테스트 전용 .env.testing 우선)
$envFile = dirname(__DIR__, 2).'/.env.testing';
if (! file_exists($envFile)) {
    $envFile = dirname(__DIR__, 2).'/.env';
}

if (file_exists($envFile)) {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname($envFile), basename($envFile));
    $dotenv->load();
}

// 세션 초기화 (헤더 전송 방지)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DB 연결 확인
$config = [
    'DB_HOST' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'DB_NAME' => $_ENV['DB_NAME'] ?? 'test',
    'DB_USER' => $_ENV['DB_USER'] ?? 'root',
    'DB_PASS' => $_ENV['DB_PASS'] ?? '',
];
try {
    $db = new App\Core\Database($config);
    // 연결 테스트
    $db->query('SELECT 1');
} catch (\Exception $e) {
    fwrite(STDERR, "\n[Feature Test] DB 연결 실패: ".$e->getMessage()."\n");
    fwrite(STDERR, "Docker Compose가 실행 중인지 확인하세요: docker compose up -d\n\n");
    exit(1);
}

echo "\n[Feature Test] DB 연결 성공 (APP_ENV=testing)\n\n";
