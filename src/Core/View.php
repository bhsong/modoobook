<?php
// src/Core/View.php

namespace App\Core;

class View {
    /**
     * 뷰 파일을 렌더링합니다
     * @param string $viewPath 템플릿 파일 이름 (예: 'home', 'accounts_view')
     * @param array $data 뷰로 전달할 데이터 배열 (예: ['userName' => '홍길동'])
     */
    public static function render(string $viewPath, array $data = [], string $layout = 'layout'): void {
        // 배열의 키를 변수명으로 변환 (예: ['name'=>'Kim'] -> $name = 'Kim' 생성)
        extract($data);

        // 파일 경로 구성 (templates 디렉터리 기준)
        // 확장자가 없으면 .php를 자동으로 붙여줌
        $viewFile = __DIR__ . '/../../templates/' . $viewPath . '.php';
        if (!file_exists($viewFile)) {
            throw new \Exception("View file not found: $viewFile");
        }

        // [핵심] 출력 버퍼링 시작 (화면에 바로 안 뿌리고 메모리에 담음)
        ob_start();
        require $viewFile;           // 뷰 파일 실행 (HTML 생성)
        $content = ob_get_clean();  // 생성된 HTML을 $content 변수에 저장하고 버퍼 종료 

        // 레이아웃 파일 불러오기 (여기서 $content가 출력됨)
        $layoutFile = __DIR__ . '/../../templates/' . $layout . '.php';
        if(file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            // 레이아웃 파일이 없으면 그냥 콘텐츠만 출력 (Fallback)
            echo $content;
        }
    }
}