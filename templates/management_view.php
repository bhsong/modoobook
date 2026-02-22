<h3>1. 관리항목 종류 등록</h3>

<?php if (isset($_GET['error'])) { ?>
<div style="color: red; padding: 8px; background: #fff0f0; margin-bottom: 10px; border-radius: 4px;">
    &#9888; <?= htmlspecialchars($_GET['error']) ?>
</div>
<?php } ?>

<form method="POST" action="/index.php?action=management">
    <?= \App\Core\CsrfGuard::tokenField() ?>
    <input type="text" name="itemName" placeholder="관리항목명 (예: 거래처, 계좌번호, 티커)" required>
    <button type="submit" name="add_item">항목 추가</button>
</form>

<ul style="margin-top: 15px;">
    <?php foreach ($item_list as $item) { ?>
    <li style="padding: 4px 0;">
        <?= htmlspecialchars($item['itemName']) ?>
        <?php if ($item['isSystem']) { ?>
            <span style="background: #e0e0e0; padding: 2px 7px; border-radius: 3px; font-size: 0.8em; color: #555;">시스템</span>
        <?php } else { ?>
            <form method="POST" action="/index.php?action=management" style="display:inline; margin-left:6px;">
                <?= \App\Core\CsrfGuard::tokenField() ?>
                <input type="hidden" name="itemId" value="<?= (int) $item['itemId'] ?>">
                <button type="submit" name="delete_item"
                    onclick="return confirm('<?= htmlspecialchars($item['itemName'], ENT_QUOTES) ?> 항목을 삭제하시겠습니까?')">삭제</button>
            </form>
        <?php } ?>
    </li>
    <?php } ?>
</ul>

<hr>
<a href="/index.php?action=accounts_advance">다음: 계정과목에 항목 연결하기</a>
