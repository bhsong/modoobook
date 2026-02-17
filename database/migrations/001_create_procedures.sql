
CREATE OR REPLACE PROCEDURE sp_save_complex_transaction(
    IN p_user_id INT,
    IN p_date DATE,
    IN p_description VARCHAR(255),
    IN p_json_data JSON  -- 전표 라인들이 담긴 JSON 배열
)
BEGIN
	DECLARE	v_trans_id INT;
    DECLARE v_trans_no VARCHAR(20);
    DECLARE v_seq INT;
    DECLARE i INT DEFAULT 0;
    DECLARE v_row_count INT;
    
    DECLARE v_acc_id INT;
    DECLARE v_dr_amt DECIMAL(17,4);
    DECLARE v_cr_amt DECIMAL(17,4);
    DECLARE v_item_id INT;
    DECLARE v_item_val VARCHAR(255);
    DECLARE v_entry_id INT;
    
	-- 1. 대차평균 검증
    -- JSON TABLE을 사용하여 합계 계산
    IF (SELECT SUM(dr) FROM JSON_TABLE(p_json_data, '$[*]' COLUMNS (dr DECIMAL(17,4) PATH '$.dr')) as t) <>
	   (SELECT SUM(cr) FROM JSON_TABLE(p_json_data, '$[*]' COLUMNS (cr DECIMAL(17,4) PATH '$.cr')) as t) THEN
       SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '차대변 합계가 일치하지 않습니다.';
    END IF;
    
    START TRANSACTION;
    -- 2. 전표 번호 생성 로직 (YYYYMMDD + 6자리 순번
	SELECT COUNT(*) + 1 INTO v_seq
    FROM transactions
    WHERE transactionDate = p_date;
    
    -- 날짜에서 '-' 제거하고 순번을 6자리로 채움
    SET v_trans_no = CONCAT(REPLACE(p_date, '-', ''), LPAD(v_seq, 6, '0'));

    -- 3. 거래 마스터 저장
    INSERT INTO transactions (userId, transactionDate, description, transactionNumber)
    VALUES (p_user_id, p_date, p_description, v_trans_no);
    SET v_trans_id = LAST_INSERT_ID();
    
    -- 4. JSON 루프를 돌며 분개장 저장
    SET v_row_count = JSON_LENGTH(p_json_data);
    
    WHILE i < v_row_count DO
    	SET v_acc_id = JSON_VALUE(p_json_data, CONCAT('$[', i, '].acc'));
        SET v_dr_amt = JSON_VALUE(p_json_data, CONCAT('$[', i, '].dr'));
        SET v_cr_amt = JSON_VALUE(p_json_data, CONCAT('$[', i, '].cr'));
        SET v_item_id = JSON_VALUE(p_json_data, CONCAT('$[', i, '].item_id'));
        SET v_item_val = JSON_VALUE(p_json_data, CONCAT('$[', i, '].item_val'));
        
        -- 분개 저장
        INSERT INTO journalEntries (transactionId, accountId, debitAmount, creditAmount)
        VALUES (v_trans_id, v_acc_id, v_dr_amt, v_cr_amt);
        SET v_entry_id = LAST_INSERT_ID();
        
        -- 해당 라인에 관리항목 값이 있다면 저장
        IF v_item_id IS NOT NULL AND v_item_val <> '' THEN
        	INSERT INTO entryItemValues (entryId, itemId, itemValue) 
            VALUES (v_entry_id, v_item_id, v_item_val);
        END IF;
        
        
    	SET i = i + 1;
    END WHILE;
    
    COMMIT;

END;

CREATE OR REPLACE PROCEDURE _SPUpdateAccountMappings(
    IN p_account_id INT,
    IN p_item_ids JSON      -- 예: "[1,2,5]" 형태의 문자열
)
BEGIN
    DECLARE i INT DEFAULT 0;
    DECLARE total_count INT DEFAULT 0;
    DECLARE current_item_id INT;

    -- 에러 발생 시 자동 롤백 (안전장치)
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;       -- 에러를 PHP로 다시 던짐
    END;

    START TRANSACTION;

    -- 기존 매핑 삭제(초기화)
    DELETE FROM accountItemMap WHERE accountId = p_account_id;

    -- JSON 배열이 비어있지 않다면 루프를 돌며 INSERT
    IF p_item_ids IS NOT NULL AND JSON_LENGTH(p_item_ids) > 0 THEN
        SET total_count = JSON_LENGTH(p_item_ids);

        WHILE i < total_count DO
            -- JSON 배열에서 i번째 값을 추출 (인덱스는 0부터 시작)
            SET current_item_id = JSON_UNQUOTE(JSON_EXTRACT(p_item_ids, CONCAT('$[', i, ']')));

            INSERT INTO accountItemMap (accountId, itemId)
            VALUES (p_account_id, current_item_id);

            SET i = i + 1;
        
        END WHILE;
    END IF;

    COMMIT;


END;


CREATE OR REPLACE PROCEDURE _SPGetJournalList (
    IN p_user_id INT,
    IN p_from_date DATE,
    IN p_to_date DATE
)
BEGIN
    SELECT
        t.transactionNumber,
        t.transactionDate,
        t.description,
        a.accountName,
        j.debitAmount,
        j.creditAmount,
        mi.itemName,
        eiv.itemValue
    FROM transactions t
    JOIN journalEntries j ON t.transactionId = j.transactionId
    JOIN accounts a ON j.accountId = a.accountId
    LEFT JOIN entryItemValues eiv ON j.entryId = eiv.entryId
    LEFT JOIN managementItems mi ON eiv.itemId = mi.itemId
    WHERE t.userId = p_user_id
    AND t.transactionDate BETWEEN p_from_date AND p_to_date
    ORDER BY t.transactionDate DESC, t.transactionNumber DESC, j.entryId ASC;
END;

CREATE OR REPLACE PROCEDURE _SPGetAccountLedger (
    IN p_user_id INT,
    IN p_account_id INT,
    IN p_from_date DATE,
    IN p_to_date DATE
)
BEGIN
    -- 이월 잔액 계산
    DECLARE v_base_balance DECIMAL(17,4) DEFAULT 0;

    SELECT SUM(j.debitAmount) - SUM(j.creditAmount) INTO v_base_balance
    FROM transactions t
    JOIN journalEntries j ON t.transactionId = j.transactionId
    WHERE t.userId = p_user_id
    AND j.accountId = p_account_id
    AND t.transactionDate < p_from_date;

    -- 상세 내역 조회 
    -- 초기 잔액은 별도로 제공하거나 결과셋 상단에 유니온 할 수 있음. 추후 구현 고려
    SELECT
        IFNULL(v_base_balance, 0) AS base_balance,
        t.transactionDate,
        t.transactionNumber,
        t.description,
        j.debitAmount,
        j.creditAmount,
        mi.itemName,
        eiv.itemValue
    FROM transactions t
    JOIN journalEntries j ON t.transactionId = j.transactionId
    LEFT JOIN entryItemValues eiv ON j.entryId = eiv.entryId
    LEFT JOIN managementItems mi ON eiv.itemId = mi.itemId
    WHERE t.userId = p_user_id
      AND j.accountId = p_account_id
      AND t.transactionDate BETWEEN p_from_date AND p_to_date
    ORDER BY t.transactionDate ASC, t.transactionID ASC;
END;