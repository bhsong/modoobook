<?php
// src/Controllers/HomeController.php
namespace App\Controllers;

use App\Core\View;
use App\Core\Database;
use App\Database\ReportRepository;

class HomeController
{
    private $db;
    private $reportRepo;

    public function __construct(\App\Core\Database $db)
    {
        $this->db         = $db;
        $this->reportRepo = new ReportRepository($db);
    }

    public function index()
    {
        if (!isset($_SESSION['userId'])) {
            header("Location: /index.php?action=login");
            exit;
        }

        $user_id = $_SESSION['userId'];

        // 이번 달 지출 합계 조회 (ReportBuilder 사용)
        $monthly_expense = $this->reportRepo->getCurrentMonthExpense($user_id);

        View::render('home_view', [
            'userName'       => $_SESSION['userName'] ?? '사용자',
            'monthlyExpense' => $monthly_expense,
        ]);
    }
}
