<?php
namespace App\Core;

class ErrorHandler {
    /** 
     * 포착되지 않은 예외(Exception)을 처리
    */
    public static function handleException(\Throwable $exception) {
        // 환경 설정 확인 (기본값은 운영모드인 'production'으로 설정하여 안전하게)
        $isDev = ($_ENV['APP_ENV'] ?? 'production') === 'dev';

        // 로그 파일에 기록
        $logPath = __DIR__ . '/../../logs/app.log';
        $logMessage = sprintf(
            "[%s] %s in %s on line %d\n",
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
        error_log($logMessage, 3, $logPath);

        // HTTP 상태 코드 설정
        $code = $exception->getCode();
        if ($code !== 404) {
            $code = 500;
        }
        http_response_code($code);

        // 화면 출력 (개발/운영 분기)
        if($isDev) {
            // [개발 모드] 상세 에러 표시
            echo "<div style='padding:20px; background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; font-family:sans-serif;'>";
            echo "<h2>🛠️ [개발 모드] 오류 발생</h2>";
            echo "<p><strong>메시지:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
            echo "<p><strong>파일:</strong> " . $exception->getFile() . " (Line: " . $exception->getLine() . ")</p>";
            echo "<h3>Stack Trace:</h3>";
            echo "<pre style='background:#fff; padding:10px; overflow:auto; font-size:12px;'>" . $exception->getTraceAsString() . "</pre>";
            echo "</div>";
        } else {
            // [운영 모드] 사용자 친화적 메시지 표시 (정보 숨김)
            echo "<div style='text-align:center; padding:50px; font-family:sans-serif;'>";
            echo "<h2>⚠️ 시스템 오류가 발생했습니다.</h2>";
            if ($code === 404) {
                echo "<p>요청하신 페이지를 찾을 수 없습니다.</p>";
            } else {
                echo "<p>불편을 드려 죄송합니다. 관리자에게 문의하거나 잠시 후 다시 시도해 주세요.</p>";
            }
            echo "<p><a href='/index.php'>홈으로 돌아가기</a></p>";
            echo "</div>";
        }
        exit;
    }

    public static function handleError($level, $message, $file, $line) {
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
    }
}
