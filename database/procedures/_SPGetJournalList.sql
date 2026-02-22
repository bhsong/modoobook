DROP PROCEDURE IF EXISTS _SPGetJournalList;

DELIMITER //
CREATE PROCEDURE _SPGetJournalList (
    IN p_user_id INT,
    IN p_form_date DATE,
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
END //
DELIMITER ;