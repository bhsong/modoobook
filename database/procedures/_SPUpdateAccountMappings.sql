DROP PROCEDURE IF EXISTS _SPUpdateAccountMappings;
DELIMITER //

CREATE PROCEDURE _SPUpdateAccountMappings(
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


END //

DELIMITER ;