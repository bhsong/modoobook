DROP PROCEDURE IF EXISTS _SPGetAccountLedger;

DELIMITER //
CREATE PROCEDURE _SPGetAccountLedger (
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
END //

DELIMITER ;