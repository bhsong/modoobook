<?php

// src/Core/functions.php

/**
 * XSS 방지용 HTML 이스케이프 단축 함수
 * 사용법: <?= h($variable) ?>
 */
if (! function_exists('h')) {
    function h($string)
    {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

/**
 * 디버깅용 덤프 함수 (개발 중에만 사용)
 */
if (! function_exists('dd')) {
    function dd($data)
    {
        echo "<pre style='background:#222; color:#0f0; padding:10px; z-index:9999, position:relative;'>";
        var_dump($data);
        echo '</pre>';
        exit();
    }
}
