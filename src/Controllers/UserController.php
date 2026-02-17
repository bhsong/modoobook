<?php
// src/Controllers/UserController.php
namespace App\Controllers;

//use App\Database\userRepository;       // 추후 구현. 지금은 repository없이 바로 PDO 사용
use App\Core\View;
use App\Core\Database;

class UserController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index() {
        // 이미 로그인했으면 대시보드로 이동
        if(isset($_SESSION['userId'])) {
            header("Location: /index.php?action=dashboard");
            exit;
        }

        // 에러 메시지 처리 (URL 파라미터로 오는 경우 등)
        $error = $_GET['error'] ?? null;

        //사용자 목록 가져오기
        $user_list = $this->db->query(
            "SELECT * FROM users"
        )->fetchAll();
        
        // 뷰 로드
        View::render('login', [
            'error' => $error,
            'user_list' => $user_list
        ]);
    }

    public function register() {
        if (!empty($_POST['userName'])) {
            $stmt = $this->pdo->prepare("INSERT INTO users (userName) VALUES (?)");
            $stmt->execute([$_POST['userName']]);
        }
        // 등록 후 다시 로그인 화면으로
        header("Location: /index.php");
        exit;
    }

    public function login() {
        if (isset($_GET['select_user'])) {
            $_SESSION['userId'] = $_GET['select_user'];
            $_SESSION['userName'] = $_GET['userName'];
            // 로그인 성공 시 대시보드로 이동
            header("Location: /index.php?action=dashboard");
            exit;
        }
    }

    public function logout() {
        session_destroy();
        header("Location: /index.php");
        exit;
    }
}