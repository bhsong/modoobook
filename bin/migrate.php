<?php
// 터미널(CLI) 환경에서만 실행되도록 보호
if (php_sapi_name() !== 'cli') {
    die("이 스크립트는 터미널(CLI)에서만 실행할 수 있습니다.\n");
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../db.php'; // $db 객체 로드

echo "🚀 데이터베이스 마이그레이션을 시작합니다...\n";

try {
    // 1. 마이그레이션 이력 관리 테이블 생성 (없으면 만듦)
    $db->getPdo()->exec("
        CREATE TABLE IF NOT EXISTS migration_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // 2. 마이그레이션 폴더의 SQL 파일 목록 가져오기
    $migrationDir = __DIR__ . '/../database/migrations';
    $files = glob($migrationDir . '/*.sql');
    sort($files); // V1, V2 순서대로 정렬

    $executedCount = 0;

    foreach ($files as $file) {
        $filename = basename($file);

        // 3. 이미 실행된 파일인지 DB에서 확인
        $stmt = $db->getPdo()->prepare("SELECT id FROM migration_history WHERE migration_name = ?");
        $stmt->execute([$filename]);
        
        if (!$stmt->fetch()) {
            echo "처리 중: {$filename} ... ";
            
            // 4. SQL 파일 내용 읽기
            $sql = file_get_contents($file);

            // 5. DB에 실행 (PDO exec 사용)
            // 주의: PDO는 DELIMITER 명령어를 이해하지 못하므로, V2 프로시저 파일은 약간의 수정이 필요할 수 있습니다.
            $db->getPdo()->exec($sql);

            // 6. 성공하면 이력 테이블에 기록
            $insertStmt = $db->getPdo()->prepare("INSERT INTO migration_history (migration_name) VALUES (?)");
            $insertStmt->execute([$filename]);

            echo "[완료] ✅\n";
            $executedCount++;
        }
    }

    if ($executedCount === 0) {
        echo "✨ 적용할 새로운 마이그레이션이 없습니다. (모두 최신 상태)\n";
    } else {
        echo "🎉 총 {$executedCount}개의 마이그레이션이 성공적으로 적용되었습니다.\n";
    }

} catch (Exception $e) {
    echo "\n❌ 마이그레이션 실패: " . $e->getMessage() . "\n";
    exit(1);
}