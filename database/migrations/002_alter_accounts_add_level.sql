-- ============================================================
-- 마이그레이션: accounts 테이블 계층 구조 컬럼 추가
--              managementItems 테이블 isSystem 컬럼 추가
-- ============================================================

-- 1. accounts.userId NULL 허용 (시스템 계정은 특정 사용자에 속하지 않음)
ALTER TABLE accounts MODIFY COLUMN userId INT NULL;

-- 2. accounts 테이블에 accountLevel, isSystem 컬럼 추가
ALTER TABLE accounts
  ADD COLUMN accountLevel TINYINT NOT NULL DEFAULT 3
    COMMENT '1=대분류(시스템), 2=중분류, 3=소분류(전표입력용)'
    AFTER accountType,
  ADD COLUMN isSystem TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1이면 시스템 제공 계정 (삭제/수정 불가)'
    AFTER accountLevel;

-- 3. parentAccountId 인덱스 추가 (트리 조회 성능)
ALTER TABLE accounts
  ADD INDEX idx_parent (parentAccountId),
  ADD INDEX idx_level  (accountLevel);

-- 4. managementItems.userId NULL 허용
ALTER TABLE managementItems MODIFY COLUMN userId INT NULL;

-- 5. managementItems 테이블에 isSystem 컬럼 추가
ALTER TABLE managementItems
  ADD COLUMN isSystem TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1이면 시스템 제공 관리항목 (삭제/수정 불가)'
    AFTER itemName;
