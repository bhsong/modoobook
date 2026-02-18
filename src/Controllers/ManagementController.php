<?php
// src/Controllers/ManagementController.php
namespace App\Controllers;

use App\Core\View;
use App\Core\Database;
use App\Database\ManagementRepository;
use Exception;

class ManagementController {
    private $db;
    private $repo;

    public function __construct($db) {
        $this->db   = $db;
        $this->repo = new ManagementRepository($db);
    }

    public function index() {
        if (!isset($_SESSION['userId'])) {
            header("Location: /index.php?action=login");
            exit;
        }

        $user_id = $_SESSION['userId'];

        // ----------------------------------------------------------
        // POST: 항목 추가
        // ----------------------------------------------------------
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
            $item_name = trim($_POST['itemName'] ?? '');
            if (!empty($item_name)) {
                $this->repo->addItem($user_id, $item_name);
            }
            header("Location: /index.php?action=management");
            exit;
        }

        // ----------------------------------------------------------
        // POST: 항목 삭제
        // ----------------------------------------------------------
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_item'])) {
            $itemId = (int)($_POST['itemId'] ?? 0);
            try {
                $this->repo->deleteItem($itemId);
            } catch (Exception $e) {
                header("Location: /index.php?action=management&error=" . urlencode($e->getMessage()));
                exit;
            }
            header("Location: /index.php?action=management");
            exit;
        }

        // ----------------------------------------------------------
        // GET: 목록 조회 (시스템 항목 포함)
        // ----------------------------------------------------------
        $item_list = $this->repo->getAllItems($user_id);

        View::render('management_view', [
            'item_list' => $item_list,
        ]);
    }
}
