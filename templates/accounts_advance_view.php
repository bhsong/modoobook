<h3>2. 계정과목별 필요 항목 설정</h3>

<p style="color:gray; font-size:0.9em;">
    * 예: '보통예금' 계정을 쓸 때는 '은행명'과 '계좌번호'를 입력받도록 설정합니다.
</p>

<form method="POST" action="/index.php?action=accounts_advance">
    <label>계정과목:</label>
    <select name="account_id">
        <?php foreach ($account_list as $acc): ?>
            <option value="<?= $acc['accountId'] ?>"><?= htmlspecialchars($acc['accountName']) ?></option>
        <?php endforeach; ?>
    </select>

    <label>필요한 관리항목:</label>
    <select name="item_id">
        <?php foreach ($item_list as $item): ?>
            <option value="<?= $item['itemId'] ?>"><?= htmlspecialchars($item['itemName']) ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit" name="link_item">항목 연결</button>
</form>

<hr>

<a href="/index.php?action=accounts">계정과목 등록으로 돌아가기</a>