<?php
// src/Core/Router.php
namespace App\Core;

use App\Core\Database;

class Router {
    private $routes = [];

    // 라우트 등록 (Action 이름, 컨트롤러 클래스명, 실행할 메소드명)
    public function add(string $action, string $controller, string $method = 'index') {
        $this->routes[$action] = [
            'controller' => $controller,
            'method' => $method
        ];
    }

    // 요청 처리 (Dispatcher)
    public function dispatch(string $action, \App\Core\Database $db) {
        // 등록된 라우터가 있는지 확인
        if (array_key_exists($action, $this->routes)) {
            $route = $this->routes[$action];
            $controllerClass = $route['controller'];
            $method = $route['method'];

            // 컨트롤러 클래스 존재 여부 확인
            if (!class_exists($controllerClass)) {
                throw new \Exception("Controller class not found: $controllerClass");
            }

            // 컨트롤러 인스턴스 생성 (DI: PDO 주입)
            // 모든 컨트롤러가 생성자에서 $pdo를 받는다고 가정
            $controllerInstance = new $controllerClass($db);

            // 메소드 존재 여부
            if (!method_exists($controllerInstance, $method)) {
                throw new \Exception("Method not found: $method in $controllerClass");
            }

            // 실행
            $controllerInstance->$method();
        } else {
            // 404 Not found 처리
            throw new \Exception("Page not found (Action: $action)", 404);
        }
    }
}