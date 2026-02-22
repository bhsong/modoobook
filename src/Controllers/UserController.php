<?php

// src/Controllers/UserController.php

namespace App\Controllers;

use App\Core\View;

class UserController extends BaseController
{
    private $db;

    public function __construct($db)
    {
        parent::__construct($db);
        $this->db = $db;
    }

    public function index()
    {
        // 이미 로그인했으면 대시보드로 이동
        if (isset($_SESSION['userId'])) {
            header('Location: /index.php?action=dashboard');
            exit;
        }

        // 에러 메시지 처리 (URL 파라미터로 오는 경우 등)
        $error = $_GET['error'] ?? null;

        // 사용자 목록 가져오기
        $user_list = $this->db->query(
            'SELECT * FROM users'
        )->fetchAll();

        // 뷰 로드
        View::render('login', [
            'error' => $error,
            'user_list' => $user_list,
        ]);
    }

    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?action=login');
            exit;
        }

        $this->requireCsrf('register_user', '/index.php?action=login');

        $user_name = $_POST['userName'] ?? '';
        if (! empty($user_name)) {
            $stmt = $this->db->query(
                'INSERT INTO users (userName) VALUES (?)',
                [$user_name]
            );
        }
        // 등록 후 다시 로그인 화면으로
        header('Location: /index.php');
        exit;
    }

    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ! isset($_POST['select_user'])) {
            header('Location: /index.php?action=login');
            exit;
        }

        $this->requireCsrf('login_process', '/index.php?action=login');

        $userId = (int) $_POST['select_user'];
        $user = $this->db->query(
            'SELECT userId, userName FROM users WHERE userId = ?',
            [$userId]
        )->fetch(\PDO::FETCH_ASSOC);

        if (! $user) {
            header('Location: /index.php?action=login&error='.urlencode('존재하지 않는 사용자입니다.'));
            exit;
        }

        session_regenerate_id(true);
        $_SESSION['userId'] = (int) $user['userId'];
        $_SESSION['userName'] = $user['userName'];

        header('Location: /index.php?action=dashboard');
        exit;
    }

    public function logout()
    {
        session_destroy();
        header('Location: /index.php');
        exit;
    }
}
