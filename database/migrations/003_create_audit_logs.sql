-- database/migrations/003_create_audit_logs.sql
-- 감사 로그 테이블 생성

CREATE TABLE IF NOT EXISTS audit_logs (
    logId       INT          NOT NULL AUTO_INCREMENT,
    userId      INT          NULL COMMENT '로그인 전 행위도 기록 가능',
    action      VARCHAR(100) NOT NULL COMMENT '행위 코드 (journal_save, account_create 등)',
    status      ENUM('success', 'failed') NOT NULL DEFAULT 'success',
    ip          VARCHAR(45)  NOT NULL COMMENT 'IPv6 대비 45자',
    userAgent   VARCHAR(500) NULL,
    context     JSON         NULL COMMENT '추가 컨텍스트 (transactionId, amount 등)',
    createdAt   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (logId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_audit_userId    ON audit_logs (userId);
CREATE INDEX idx_audit_action    ON audit_logs (action);
CREATE INDEX idx_audit_createdAt ON audit_logs (createdAt);
