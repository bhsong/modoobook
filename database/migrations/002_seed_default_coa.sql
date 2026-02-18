-- ============================================================
-- 시드 데이터: 기본 Chart of Accounts (3단계 계층)
--             기본 관리항목 7개
--             계정-관리항목 매핑
-- ============================================================
-- 전제: 002_alter_accounts_add_level.sql이 먼저 실행되어야 합니다.
-- userId = NULL: 모든 사용자 공통 시스템 계정/항목
-- ============================================================


-- ============================================================
-- PART 1: 기본 Chart of Accounts
-- ============================================================

-- ----------------------------------------------------------
-- 1단계: 대분류 (accountLevel=1, isSystem=1, parentAccountId=NULL)
-- ----------------------------------------------------------
INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId) VALUES
('자산', 'ASSET',     1, 1, NULL, NULL),
('부채', 'LIABILITY', 1, 1, NULL, NULL),
('자본', 'EQUITY',    1, 1, NULL, NULL),
('수익', 'REVENUE',   1, 1, NULL, NULL),
('비용', 'EXPENSE',   1, 1, NULL, NULL);

-- ----------------------------------------------------------
-- 2단계: 중분류 (accountLevel=2, isSystem=1)
-- 서브쿼리로 1단계 accountId 참조
-- ----------------------------------------------------------

-- 자산 하위
INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '유동자산', 'ASSET', 2, 1, accountId, NULL
FROM accounts WHERE accountName = '자산' AND isSystem = 1 AND accountLevel = 1;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '비유동자산', 'ASSET', 2, 1, accountId, NULL
FROM accounts WHERE accountName = '자산' AND isSystem = 1 AND accountLevel = 1;

-- 부채 하위
INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '유동부채', 'LIABILITY', 2, 1, accountId, NULL
FROM accounts WHERE accountName = '부채' AND isSystem = 1 AND accountLevel = 1;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '비유동부채', 'LIABILITY', 2, 1, accountId, NULL
FROM accounts WHERE accountName = '부채' AND isSystem = 1 AND accountLevel = 1;

-- 자본 하위
INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '자본금', 'EQUITY', 2, 1, accountId, NULL
FROM accounts WHERE accountName = '자본' AND isSystem = 1 AND accountLevel = 1;

-- 수익 하위
INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '근로소득', 'REVENUE', 2, 1, accountId, NULL
FROM accounts WHERE accountName = '수익' AND isSystem = 1 AND accountLevel = 1;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '금융소득', 'REVENUE', 2, 1, accountId, NULL
FROM accounts WHERE accountName = '수익' AND isSystem = 1 AND accountLevel = 1;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '기타수익', 'REVENUE', 2, 1, accountId, NULL
FROM accounts WHERE accountName = '수익' AND isSystem = 1 AND accountLevel = 1;

-- 비용 하위
INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '주거비', 'EXPENSE', 2, 1, accountId, NULL
FROM accounts WHERE accountName = '비용' AND isSystem = 1 AND accountLevel = 1;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '식비', 'EXPENSE', 2, 1, accountId, NULL
FROM accounts WHERE accountName = '비용' AND isSystem = 1 AND accountLevel = 1;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '교통비', 'EXPENSE', 2, 1, accountId, NULL
FROM accounts WHERE accountName = '비용' AND isSystem = 1 AND accountLevel = 1;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '통신비', 'EXPENSE', 2, 1, accountId, NULL
FROM accounts WHERE accountName = '비용' AND isSystem = 1 AND accountLevel = 1;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '의료/건강비', 'EXPENSE', 2, 1, accountId, NULL
FROM accounts WHERE accountName = '비용' AND isSystem = 1 AND accountLevel = 1;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '교육비', 'EXPENSE', 2, 1, accountId, NULL
FROM accounts WHERE accountName = '비용' AND isSystem = 1 AND accountLevel = 1;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '여가/문화비', 'EXPENSE', 2, 1, accountId, NULL
FROM accounts WHERE accountName = '비용' AND isSystem = 1 AND accountLevel = 1;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '기타지출', 'EXPENSE', 2, 1, accountId, NULL
FROM accounts WHERE accountName = '비용' AND isSystem = 1 AND accountLevel = 1;

-- ----------------------------------------------------------
-- 3단계: 소분류 (accountLevel=3, isSystem=1)
-- 실제 전표 입력에 사용되는 계정
-- ----------------------------------------------------------

-- 유동자산 하위
INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '현금', 'ASSET', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '유동자산' AND isSystem = 1 AND accountLevel = 2;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '국민은행 입출금', 'ASSET', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '유동자산' AND isSystem = 1 AND accountLevel = 2;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '카카오뱅크', 'ASSET', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '유동자산' AND isSystem = 1 AND accountLevel = 2;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '토스뱅크', 'ASSET', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '유동자산' AND isSystem = 1 AND accountLevel = 2;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '신용카드 미결제', 'ASSET', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '유동자산' AND isSystem = 1 AND accountLevel = 2;

-- 비유동자산 하위
INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '주식', 'ASSET', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '비유동자산' AND isSystem = 1 AND accountLevel = 2;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT 'ETF', 'ASSET', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '비유동자산' AND isSystem = 1 AND accountLevel = 2;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '연금저축', 'ASSET', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '비유동자산' AND isSystem = 1 AND accountLevel = 2;

-- 유동부채 하위
INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '신한카드', 'LIABILITY', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '유동부채' AND isSystem = 1 AND accountLevel = 2;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '현대카드', 'LIABILITY', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '유동부채' AND isSystem = 1 AND accountLevel = 2;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '단기차입금', 'LIABILITY', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '유동부채' AND isSystem = 1 AND accountLevel = 2;

-- 비유동부채 하위
INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '주택담보대출', 'LIABILITY', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '비유동부채' AND isSystem = 1 AND accountLevel = 2;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '학자금대출', 'LIABILITY', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '비유동부채' AND isSystem = 1 AND accountLevel = 2;

-- 자본금 하위
INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '기초자본', 'EQUITY', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '자본금' AND isSystem = 1 AND accountLevel = 2;

-- 근로소득 하위
INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '급여', 'REVENUE', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '근로소득' AND isSystem = 1 AND accountLevel = 2;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '상여금', 'REVENUE', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '근로소득' AND isSystem = 1 AND accountLevel = 2;

-- 금융소득 하위
INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '이자수입', 'REVENUE', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '금융소득' AND isSystem = 1 AND accountLevel = 2;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '배당수입', 'REVENUE', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '금융소득' AND isSystem = 1 AND accountLevel = 2;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '주식매매차익', 'REVENUE', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '금융소득' AND isSystem = 1 AND accountLevel = 2;

-- 기타수익 하위
INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '기타수입', 'REVENUE', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '기타수익' AND isSystem = 1 AND accountLevel = 2;

-- 주거비 하위
INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '월세', 'EXPENSE', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '주거비' AND isSystem = 1 AND accountLevel = 2;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '관리비', 'EXPENSE', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '주거비' AND isSystem = 1 AND accountLevel = 2;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '공과금', 'EXPENSE', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '주거비' AND isSystem = 1 AND accountLevel = 2;

-- 식비 하위
INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '식료품', 'EXPENSE', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '식비' AND isSystem = 1 AND accountLevel = 2;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '외식', 'EXPENSE', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '식비' AND isSystem = 1 AND accountLevel = 2;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '카페', 'EXPENSE', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '식비' AND isSystem = 1 AND accountLevel = 2;

-- 교통비 하위
INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '대중교통', 'EXPENSE', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '교통비' AND isSystem = 1 AND accountLevel = 2;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '택시', 'EXPENSE', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '교통비' AND isSystem = 1 AND accountLevel = 2;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '주유비', 'EXPENSE', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '교통비' AND isSystem = 1 AND accountLevel = 2;

-- 통신비 하위
INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '휴대폰', 'EXPENSE', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '통신비' AND isSystem = 1 AND accountLevel = 2;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '인터넷', 'EXPENSE', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '통신비' AND isSystem = 1 AND accountLevel = 2;

-- 의료/건강비 하위
INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '병원비', 'EXPENSE', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '의료/건강비' AND isSystem = 1 AND accountLevel = 2;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '약국', 'EXPENSE', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '의료/건강비' AND isSystem = 1 AND accountLevel = 2;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '헬스장', 'EXPENSE', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '의료/건강비' AND isSystem = 1 AND accountLevel = 2;

-- 교육비 하위
INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '도서', 'EXPENSE', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '교육비' AND isSystem = 1 AND accountLevel = 2;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '강의/수강료', 'EXPENSE', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '교육비' AND isSystem = 1 AND accountLevel = 2;

-- 여가/문화비 하위
INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '구독서비스', 'EXPENSE', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '여가/문화비' AND isSystem = 1 AND accountLevel = 2;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '여행', 'EXPENSE', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '여가/문화비' AND isSystem = 1 AND accountLevel = 2;

INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '쇼핑', 'EXPENSE', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '여가/문화비' AND isSystem = 1 AND accountLevel = 2;

-- 기타지출 하위
INSERT INTO accounts (accountName, accountType, accountLevel, isSystem, parentAccountId, userId)
SELECT '기타비용', 'EXPENSE', 3, 1, accountId, NULL
FROM accounts WHERE accountName = '기타지출' AND isSystem = 1 AND accountLevel = 2;


-- ============================================================
-- PART 2: 기본 관리항목 (managementItems)
-- ============================================================

INSERT INTO managementItems (itemName, isSystem, userId) VALUES
('거래처',   1, NULL),
('메모',     1, NULL),
('은행명',   1, NULL),
('계좌번호', 1, NULL),
('카드사',   1, NULL),
('카드번호', 1, NULL),
('티커',     1, NULL);


-- ============================================================
-- PART 3: 계정-관리항목 매핑 (accountItemMap)
-- INSERT IGNORE: 복합 PK 중복 실행 시 에러 방지
-- ============================================================

-- [공통] 메모: 모든 level=3 시스템 계정에 전체 적용
INSERT IGNORE INTO accountItemMap (accountId, itemId)
SELECT a.accountId, i.itemId
FROM accounts a
CROSS JOIN managementItems i
WHERE a.accountLevel = 3 AND a.isSystem = 1
  AND i.itemName = '메모' AND i.isSystem = 1;

-- [공통] 거래처: 비용/수익 계정 전체 적용
INSERT IGNORE INTO accountItemMap (accountId, itemId)
SELECT a.accountId, i.itemId
FROM accounts a
CROSS JOIN managementItems i
WHERE a.accountLevel = 3 AND a.isSystem = 1
  AND a.accountType IN ('EXPENSE', 'REVENUE')
  AND i.itemName = '거래처' AND i.isSystem = 1;

-- [공통] 거래처: 대출 계정 (금융기관명)
INSERT IGNORE INTO accountItemMap (accountId, itemId)
SELECT a.accountId, i.itemId
FROM accounts a
CROSS JOIN managementItems i
WHERE a.isSystem = 1
  AND a.accountName IN ('주택담보대출', '학자금대출', '단기차입금')
  AND i.itemName = '거래처' AND i.isSystem = 1;

-- [은행] 은행명 + 계좌번호: 은행 계정 적용
INSERT IGNORE INTO accountItemMap (accountId, itemId)
SELECT a.accountId, i.itemId
FROM accounts a
CROSS JOIN managementItems i
WHERE a.isSystem = 1
  AND a.accountName IN ('국민은행 입출금', '카카오뱅크', '토스뱅크')
  AND i.itemName IN ('은행명', '계좌번호') AND i.isSystem = 1;

-- [은행] 계좌번호만: 대출 계정 추가 적용
INSERT IGNORE INTO accountItemMap (accountId, itemId)
SELECT a.accountId, i.itemId
FROM accounts a
CROSS JOIN managementItems i
WHERE a.isSystem = 1
  AND a.accountName IN ('주택담보대출', '학자금대출', '단기차입금')
  AND i.itemName = '계좌번호' AND i.isSystem = 1;

-- [카드] 카드사 + 카드번호: 카드 계정 적용
INSERT IGNORE INTO accountItemMap (accountId, itemId)
SELECT a.accountId, i.itemId
FROM accounts a
CROSS JOIN managementItems i
WHERE a.isSystem = 1
  AND a.accountName IN ('신한카드', '현대카드', '신용카드 미결제')
  AND i.itemName IN ('카드사', '카드번호') AND i.isSystem = 1;

-- [투자] 티커: 투자 계정 적용
INSERT IGNORE INTO accountItemMap (accountId, itemId)
SELECT a.accountId, i.itemId
FROM accounts a
CROSS JOIN managementItems i
WHERE a.isSystem = 1
  AND a.accountName IN ('주식', 'ETF', '연금저축', '배당수입', '주식매매차익')
  AND i.itemName = '티커' AND i.isSystem = 1;
