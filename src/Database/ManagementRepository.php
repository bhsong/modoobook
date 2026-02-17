<?php
// src/Database/ManagementRepository.php
namespace App\Database;

use App\Core\Database;
use Exception;

class ManagementRepository {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // 관리항목 목록 조회
    public function getItems($user_id) {
        return $this->db->query(
            "SELECT * FROM managementItems WHERE userId = ? ORDER BY itemId ASC",
            [$user_id]
        )->fetchAll();
    }

    public function addItem($user_id, $item_name) {
        $this->db->query(
            "INSERT INTO managementItems (userId, itemName) VALUES (?, ?)",
            [$user_id, $item_name]
        );
    }
}