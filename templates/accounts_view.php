<h2>2. [<?= htmlspecialchars($_SESSION['userName']) ?>] 님의 계정과목 관리</h2>

<?php if (isset($_GET['error'])): ?>
<div style="color: red; padding: 10px; background: #fff0f0; margin-bottom: 10px; border-radius: 4px;">
    &#9888; <?= htmlspecialchars($_GET['error']) ?>
</div>
<?php endif; ?>

<div style="background: #f0f0f0; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
    <strong>설정 메뉴:</strong>
    <a href="/index.php?action=management">&#10133; 관리항목(거래처/티커 등) 종류 등록</a>
</div>

<!-- 계정 추가 폼: 상위 계정(중분류) 선택 → accountType 자동 상속 -->
<form method="POST" action="/index.php?action=accounts" style="margin-bottom: 20px;">
    <label>상위 계정 (중분류 선택):
        <select name="parentAccountId" required>
            <option value="">선택하세요</option>
            <?php foreach ($account_tree as $l1): ?>
            <optgroup label="<?= htmlspecialchars($l1['accountName']) ?>">
                <?php foreach ($l1['children'] as $l2): ?>
                <option value="<?= (int)$l2['accountId'] ?>">
                    <?= htmlspecialchars($l2['accountName']) ?>
                </option>
                <?php endforeach; ?>
            </optgroup>
            <?php endforeach; ?>
        </select>
    </label>
    <input type="text" name="accountName" placeholder="계정명 (예: 현금, 외식비)" required>
    <button type="submit" name="add_account">계정 추가</button>
</form>

<!-- 계정 트리 목록 -->
<table border="1" style="width: 100%; border-collapse: collapse; margin-top: 10px;">
    <thead>
        <tr style="background: #ddd;">
            <th style="text-align: left; padding: 6px;">계정명</th>
            <th style="width: 80px;">유형</th>
            <th style="width: 200px;">관리</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($account_tree as $l1): ?>
        <!-- 1단계: 대분류 — 수정/삭제 없음 -->
        <tr style="background: #e4e4e4; font-weight: bold;">
            <td style="padding: 6px 6px 6px 0;"><?= htmlspecialchars($l1['accountName']) ?></td>
            <td style="text-align: center; font-size: 0.85em;"><?= htmlspecialchars($l1['accountType']) ?></td>
            <td style="text-align: center; font-size: 0.8em; color: #888;">시스템</td>
        </tr>

        <?php foreach ($l1['children'] as $l2): ?>
        <!-- 2단계: 중분류 -->
        <tr style="background: #f5f5f5;">
            <td style="padding: 6px 6px 6px 1rem;">&nbsp;&nbsp;&#9492; <?= htmlspecialchars($l2['accountName']) ?></td>
            <td style="text-align: center; font-size: 0.85em;"><?= htmlspecialchars($l2['accountType']) ?></td>
            <td style="text-align: center;">
                <?php if (!$l2['isSystem']): ?>
                    <a href="/index.php?action=account_item_setup&accountId=<?= (int)$l2['accountId'] ?>">항목설정</a>
                    &nbsp;
                    <form method="POST" action="/index.php?action=accounts" style="display:inline;">
                        <input type="hidden" name="accountId" value="<?= (int)$l2['accountId'] ?>">
                        <button type="submit" name="delete_account"
                            onclick="return confirm('<?= htmlspecialchars($l2['accountName'], ENT_QUOTES) ?> 계정을 삭제하시겠습니까?')">삭제</button>
                    </form>
                <?php else: ?>
                    <span style="font-size: 0.8em; color: #888;">시스템</span>
                <?php endif; ?>
            </td>
        </tr>

        <?php foreach ($l2['children'] as $l3): ?>
        <!-- 3단계: 소분류 -->
        <tr>
            <td style="padding: 6px 6px 6px 2rem;">&nbsp;&nbsp;&nbsp;&nbsp;&#9492; <?= htmlspecialchars($l3['accountName']) ?></td>
            <td style="text-align: center; font-size: 0.85em;"><?= htmlspecialchars($l3['accountType']) ?></td>
            <td style="text-align: center;">
                <a href="/index.php?action=account_item_setup&accountId=<?= (int)$l3['accountId'] ?>">항목설정</a>
                <?php if (!$l3['isSystem']): ?>
                &nbsp;
                <form method="POST" action="/index.php?action=accounts" style="display:inline;">
                    <input type="hidden" name="accountId" value="<?= (int)$l3['accountId'] ?>">
                    <button type="submit" name="delete_account"
                        onclick="return confirm('<?= htmlspecialchars($l3['accountName'], ENT_QUOTES) ?> 계정을 삭제하시겠습니까?')">삭제</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; // l3 ?>
        <?php endforeach; // l2 ?>
    <?php endforeach; // l1 ?>
    </tbody>
</table>
