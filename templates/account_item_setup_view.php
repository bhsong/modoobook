<h2>'<?= htmlspecialchars($account['accountName']) ?>' 계정 관리항목 설정</h2>

<form method="POST" action="/index.php?action=account_item_setup&accountId=<?= (int) $account['accountId'] ?>">
    <?= \App\Core\CsrfGuard::tokenField() ?>
    <p>이 계정을 입력할 때 함께 기록할 항목을 선택하세요:</p>

    <?php if (empty($all_items)) { ?>
        <p style="color:red;">등록된 관리항목이 없습니다. <a href="/index.php?action=management">관리항목 등록</a>을 먼저 해주세요.</p>
    <?php } else { ?>
        <?php foreach ($all_items as $item) { ?>
            <label style="display:block; margin: 5px 0;">
                <input type="checkbox" name="items[]" value="<?= (int) $item['itemId'] ?>"
                    <?= in_array($item['itemId'], $checked_ids) ? 'checked' : '' ?>>
                <?= htmlspecialchars($item['itemName']) ?>
            </label><br>
        <?php } ?>
    <?php } ?>

    <br>
    <div style="margin-top; 10px;">
        <button type="submit" name="save_mapping" style="padding: 5px 15px; font-weight: bold;">저장하고 돌아가기</button>
        <a href="/index.php?action=accounts" style="margin-left: 10px;">취소</a>
    </div>
</form>