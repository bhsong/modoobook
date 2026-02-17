DELIMITER //

CREATE PROCEDURE sp_save_transaction(
    IN p_user_id INT,
    IN p_date DATE,
    IN p_description VARCHAR(255),
    IN p_dr_acc INT,
    IN p_dr_amt DECIMAL(17,4),
    IN p_cr_acc INT,
    IN p_cr_amt DECIMAL(17,4),
    IN p_item_id INT,
    IN p_item_val VARCHAR(255)
)
BEGIN
	DECLARE v_trans_id INT;
    DECLARE v_dr_entry_id INT;
    DECLARE v_cr_entry_id INT;
    
    -- 대차평균 검증 
    IF p_dr_amt <> p_cr_amt THEN
    	SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '차변과 대변의 금액이 일치하지 않습니다.';
    END IF;
   
   START TRANSACTION;
   
   -- 1. 거래 마스터 삽입
   INSERT INTO transactions (userId, transactionDate, description)
   VALUES (p_user_id, p_date, p_description);
   SET v_trans_id = LAST_INSERT_ID();
   
   -- 2. 차변 기록
   INSERT INTO journal_entries (transactionId, accountId, debitAmount, creditAmount)
   VALUES (v_trans_id, p_dr_acc, p_dr_amt, 0);
   SET v_dr_entry_id = LAST_INSERT_ID();
   
   -- 3. 대변 기록
   INSERT INTO journal_entries (transactionId, accountId, debitAmount, creditAmount)
   VALUES (v_trans_id, p_cr_acc, 0, p_cr_amt);
   SET v_cr_entry_id = LAST_INSERT_ID();
   
   -- 4. 관리항목 값 저장
   IF p_item_id IS NOT NULL AND p_item_val IS NOT NULL THEN
   	INSERT INTO entryItemValues (entryId, itemId, itemValue)
    VALUES (v_dr_entry_id, p_item_id, p_item_val);
   END IF;
   
   COMMIT;
   
END //

DELIMITER ;