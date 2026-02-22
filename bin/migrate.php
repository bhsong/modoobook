<?php

/**
 * DB Migration Script (CLI 전용)
 */

// 1. 터미널(CLI) 환경에서만 실행되도록 보호
if (php_sapi_name() !== 'cli') {
    exit("❌ 이 스크립트는 터미널(CLI)에서만 실행할 수 있습니다.\n");
}

// 2. 의존성 및 설정 로드
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../db.php'; // $db 객체를 정의하는 파일

echo "🚀 데이터베이스 마이그레이션을 시작합니다...\n";
echo "--------------------------------------------------\n";

try {
    $pdo = $db->getPdo();

    // PDO 에러 모드를 예외(Exception)로 설정 (이미 설정되어 있다면 생략 가능)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 3. 마이그레이션 이력 관리 테이블 생성
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS migration_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;
    ');

    // 4. 마이그레이션 폴더 내 SQL 파일 목록 가져오기
    $migrationDir = __DIR__.'/../database/migrations';
    if (! is_dir($migrationDir)) {
        throw new Exception("마이그레이션 디렉토리를 찾을 수 없습니다: {$migrationDir}");
    }

    $files = glob($migrationDir.'/*.sql');
    sort($files); // 파일명 순서(V1, V2...)로 정렬

    $executedCount = 0;

    foreach ($files as $file) {
        $filename = basename($file);

        // 5. 이미 실행된 파일인지 확인
        $stmt = $pdo->prepare('SELECT id FROM migration_history WHERE migration_name = ?');
        $stmt->execute([$filename]);

        if (! $stmt->fetch()) {
            echo "처리 중: {$filename} ... ";

            $sql = file_get_contents($file);
            if (empty(trim($sql))) {
                echo "[건너뜀] (빈 파일)\n";

                continue;
            }

            // 6. 트랜잭션 시작
            $pdo->beginTransaction();

            try {
                $pdo->exec($sql);

                // 이력 기록
                $insertStmt = $pdo->prepare('INSERT INTO migration_history (migration_name) VALUES (?)');
                $insertStmt->execute([$filename]);

                // 트랜잭션이 아직 살아있을 때만 커밋 (DDL에 의해 자동 커밋되지 않았을 경우)
                if ($pdo->inTransaction()) {
                    $pdo->commit();
                }
                echo "[완료] ✅\n";
                $executedCount++;

            } catch (Exception $innerException) {
                // 에러 발생 시 트랜잭션이 활성 상태인 경우에만 롤백
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                echo "[실패] ❌\n";
                throw new Exception("파일 '{$filename}' 실행 중 오류 발생: ".$innerException->getMessage());
            }
        }
    }

    echo "--------------------------------------------------\n";
    if ($executedCount === 0) {
        echo "✨ 적용할 새로운 마이그레이션이 없습니다. (모두 최신 상태)\n";
    } else {
        echo "🎉 총 {$executedCount}개의 마이그레이션이 성공적으로 적용되었습니다.\n";
    }

} catch (Exception $e) {
    echo "\n🚨 마이그레이션 중단: ".$e->getMessage()."\n";
    exit(1);
}
