-- 사용자 테이블
CREATE TABLE IF NOT EXISTS users (
    userId INT AUTO_INCREMENT PRIMARY KEY,
    userName VARCHAR(50) NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 계정과목 테이블 (자산, 부채, 수익, 비용, 자본)
CREATE TABLE IF NOT EXISTS accounts (
    accountId INT AUTO_INCREMENT PRIMARY KEY,
    userId INT NOT NULL,
    accountName VARCHAR(100) NOT NULL, -- 예: 현금, 신한은행, 삼성전자, 외식비
    accountType ENUM('ASSET', 'LIABILITY', 'REVENUE', 'EXPENSE', 'EQUITY') NOT NULL,
    parentAccountId INT NULL, -- 대분류/소분류를 위한 셀프 참조
    FOREIGN KEY (userId) REFERENCES users(userId),
    FOREIGN KEY (parentAccountId) REFERENCES accounts(accountId)
);

-- 관리항목 정의 (예: 거래처, 계좌번호, 티커, 카드번호 등)
CREATE TABLE IF NOT EXISTS managementItems (
    itemId INT AUTO_INCREMENT PRIMARY KEY,
    userId INT NOT NULL,
    itemName VARCHAR(50) NOT NULL, -- '거래처', '계좌번호', '티커'
    FOREIGN KEY (userId) REFERENCES users(userId)
);

-- 계정과목별 필요한 관리항목 매핑
-- 예: '신한은행' 계정(ID:5)에는 '계좌번호'(ID:2) 항목이 필요함
CREATE TABLE IF NOT EXISTS accountItemMap (
    accountId INT NOT NULL,
    itemId INT NOT NULL,
    PRIMARY KEY (accountId, itemId),
    FOREIGN KEY (accountId) REFERENCES accounts(accountId),
    FOREIGN KEY (itemId) REFERENCES managementItems(itemId)
);

-- 거래 마스터 (누가, 언제, 무엇을)
CREATE TABLE IF NOT EXISTS transactions (
    transactionId INT AUTO_INCREMENT PRIMARY KEY,
    transactionNumber CHAR(14) NOT NULL UNIQUE, 
    userId INT NOT NULL,
    transactionDate DATE NOT NULL,
    description VARCHAR(255), -- 적요
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(userId)
);

-- 분개 (실제 돈의 흐름, 차변/대변)
CREATE TABLE IF NOT EXISTS journalEntries (
    entryId INT AUTO_INCREMENT PRIMARY KEY,
    transactionId INT NOT NULL,
    accountId INT NOT NULL,
    debitAmount DECIMAL(15, 2) DEFAULT 0,  -- 차변
    creditAmount DECIMAL(15, 2) DEFAULT 0, -- 대변
    FOREIGN KEY (transactionId) REFERENCES transactions(transactionId),
    FOREIGN KEY (accountId) REFERENCES accounts(accountId)
);

-- 분개별 관리항목 값 저장 (핵심!)
-- 예: 삼성전자 매수 분개에 '티커':'005930' 저장
CREATE TABLE IF NOT EXISTS entryItemValues (
    entryId INT NOT NULL,
    itemId INT NOT NULL,
    itemValue VARCHAR(255) NOT NULL, -- 실제 값 (계좌번호나 티커 등)
    PRIMARY KEY (entryId, itemId),
    FOREIGN KEY (entryId) REFERENCES journalEntries(entryId),
    FOREIGN KEY (itemId) REFERENCES managementItems(itemId)
);

