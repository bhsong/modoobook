<h3>1. 관리항목 종류 등록</h3>
<form method="POST" action="/index.php?action=management">
    <input type="text" name="itemName" placeholder="관리항목명 (예: 거래처, 계좌번호, 티커)" required>
    <button type="submit" name="add_item">항목 추가</button>
</form>

<ul>
    <?php foreach ($item_list as $item): ?>
        <li><?= htmlspecialchars($item['itemName']) ?></li>
    <?php endforeach; ?>
</ul>

<hr>
<a href="/index.php?action=accounts_advance">다음: 게정과목에 항목 연결하기 </a>
