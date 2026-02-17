DELIMITER //

CREATE PROCEDURE sp_save_complex_transaction(
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

END // 

DELIMITER ;