<?php

// src/Controllers/ManagementController.php

namespace App\Controllers;

use App\Core\View;
use App\Database\ManagementRepository;
use Exception;

class ManagementController extends BaseController
{
    private $db;

    private $repo;

    public function __construct($db)
    {
        parent::__construct($db);
        $this->db = $db;
        $this->repo = new ManagementRepository($this->db);
    }

    public function index()
    {
        $user_id = $this->requireAuth();

        // ----------------------------------------------------------
        // POST: 항목 추가/삭제 — CSRF 한 번만 검증
        // ----------------------------------------------------------
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf('management', '/index.php?action=management');

            if (isset($_POST['add_item'])) {
                $item_name = trim($_POST['itemName'] ?? '');
                if (! empty($item_name)) {
                    $this->repo->addItem($user_id, $item_name);
                }
                header('Location: /index.php?action=management');
                exit;
            }

            if (isset($_POST['delete_item'])) {
                $itemId = (int) ($_POST['itemId'] ?? 0);
                try {
                    $this->repo->deleteItem($itemId);
                } catch (Exception $e) {
                    header('Location: /index.php?action=management&error='.urlencode($e->getMessage()));
                    exit;
                }
                header('Location: /index.php?action=management');
                exit;
            }
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
