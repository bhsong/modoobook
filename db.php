<?php
// index.php가 아닌 곳에서 독립적으로 실행될 때를 대비해 오토로더 체크
if (!class_exists('Dotenv\Dotenv')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use App\Core\Database;
use Dotenv\Dotenv;

// .env 파일 로드
// 파일이 없으면 에러가 날 수 있으므로 try-catch 또는 safeLoad 사용
try {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();    // 파일이 없어도 죽지는 않음 (서버 환경변수 쓸 때 대비)
} catch (Exception $e) {
    // .env 파일 파싱 에러 등 무시 (운영서버 환경변수 우선)
}

// 필수 환경변수 검증
$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']);

// Config 배열 생성 (환경변수 $_ENV 사용)
$config = [
    'DB_HOST' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'DB_NAME' => $_ENV['DB_NAME'] ?? 'test',
    'DB_USER' => $_ENV['DB_USER'] ?? 'root',
    'DB_PASS' => $_ENV['DB_PASS'] ?? '',
];

// Database 인스턴스 생성 (여기서 연결 시도)
// 이제 $pdo 변수 대신 $db 변수 사용
try {
    $db = new Database($config);
} catch (Exception $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
