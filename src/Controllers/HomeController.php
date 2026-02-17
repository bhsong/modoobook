<?php
// src/Controllers/HomeController.php

namespace App\Controllers;

use App\Core\View;
use App\Core\Database;

class HomeController {
    private $db;

    public function __construct(\App\Core\Database $db) {
        $this->db = $db;
    }

    public function index() {
        // 로그인 체크 (대시보드도 로그인해야 볼 수 있게)
        if (!isset($_SESSION['userId'])) {
            header("Location: /index.php?action=login");
            exit;
        }

        // View::render 호출
        // templates/home_view.php 파일을 불러오고, userName 데이터를 넘김
        View::render('home_view', [
            'userName' => $_SESSION['userName'] ?? '사용자'
        ]);
    }
}