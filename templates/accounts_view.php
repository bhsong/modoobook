<h2>2. [<?= htmlspecialchars($_SESSION['userName']) ?>] 님의 계정과목 관리</h2>

<div style="background: #f0f0f0; padding: 10px; margin-bottom: 20px;">
    <strong>설정 메뉴:</strong>
    <a href="/index.php?action=management" style="button">➕ 관리항목(거래처/티커 등) 종류 등록하러 가기</a> |
</div>

<form method="POST" action="/index.php?action=accounts">
    <input type="text" name="accountName" placeholder="계정명 (예. 보통예금, 외식비)" required>
    <select name="accountType" required>
        <option value="ASSET">자산</option>
        <option value="EXPENSE">비용</option>
        <option value="INCOME">수익</option>
        <option value="LIABILITY">부채</option>
        <option value="EQUITY">자본</option>
    </select>
    <button type="submit" name="add_account">계정추가</button>
</form>

<table border="1" style="width: 100%; border-collapse: collapse; margin-top: 20px;">
    <tr>
        <th>ID</th><th>계정명</th><th>타입</th><th>설정</th>
    </tr>
    <?php foreach ($account_list as $acc): ?>
    <tr>
        <td style="text-align:center;"><?= $acc['accountId'] ?></td>
        <td><?= htmlspecialchars($acc['accountName']) ?></td>
        <td style="text-align:center;"><?= $acc['accountType'] ?></td>
        <td style="text-align:center;">
            <a href="/index.php?action=account_item_setup&accountId=<?= $acc['accountId'] ?>">관리항목 설정</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>