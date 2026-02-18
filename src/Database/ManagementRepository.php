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

    // ----------------------------------------------------------
    // 관리항목 목록 조회 (isSystem 컬럼 포함, 시스템 항목도 포함)
    // ----------------------------------------------------------
    public function getAllItems(int $userId): array {
        return $this->db->query(
            "SELECT * FROM managementItems
             WHERE userId = ? OR isSystem = 1
             ORDER BY isSystem DESC, itemId ASC",
            [$userId]
        )->fetchAll();
    }

    // ----------------------------------------------------------
    // 관리항목 추가
    // ----------------------------------------------------------
    public function addItem(int $userId, string $itemName): void {
        $this->db->query(
            "INSERT INTO managementItems (userId, itemName) VALUES (?, ?)",
            [$userId, $itemName]
        );
    }

    // ----------------------------------------------------------
    // 관리항목 삭제 (isSystem=1이면 거부)
    // ----------------------------------------------------------
    public function deleteItem(int $itemId): void {
        $item = $this->db->query(
            "SELECT * FROM managementItems WHERE itemId = ?",
            [$itemId]
        )->fetch();

        if (!$item) {
            throw new Exception("관리항목을 찾을 수 없습니다.");
        }
        if ((int)$item['isSystem'] === 1) {
            throw new Exception("시스템 관리항목은 삭제할 수 없습니다.");
        }

        $this->db->query(
            "DELETE FROM managementItems WHERE itemId = ?",
            [$itemId]
        );
    }
}
