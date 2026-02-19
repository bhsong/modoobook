<style>
    .search-box { background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; border-radius: 5px; margin-bottom: 20px; }
    .report-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; font-size: 14px; background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .report-table  th, .report-table td { border: 1px solid #ddd; padding: 10px; }
    .tr-header { background-color: #f1f3f5; font-weight: bold; color: #495057; }
    .amt { text-align: right; width: 100px; font-family: 'Courier New', monospace; }
    .mgmt-text { color: #2b8a3e; font-size: 12px; font-weight: 500; }
    .total-row { background-color: #fff9db; font-weight: bold; }
</style>

<div class="search-box">
    <form method="GET" action="/index.php">
        <input type="hidden" name="action" value="journal_list">

        <strong>조회 기간:</strong>
        <input type="date" name="from_date" value="<?= htmlspecialchars($from_date) ?>"> 
        ~
        <input type="date" name="to_date" value="<?= htmlspecialchars($to_date) ?>">
        <button type="submit">조회</button>
    </form>
</div>

<h3>전표 조회</h3>

<?php if (isset($error_msg)): ?>
    <p style="color: red; font-weight: bold;"> ⚠️ <?= htmlspecialchars($error_msg) ?></p>
<?php endif; ?>

<?php if (!$isSearch): ?>
    <p style="color: #666; background: #f0f0f0; padding: 20px; text-align: center;">
        조회할 기간을 선택하고 [조회] 버튼을 눌러주세요.
    </p>
<?php elseif (empty($logs)): ?>
    <p>
        해당 기간에 등록된 전표가 없습니다.
        <a href="/index.php?action=journal_entry" style="font-weight:bold;">전표 입력하기</a>
    </p>
<?php else: ?>
    <?php foreach ($logs as $trNo => $entries): ?>
        <table class="report-table">
            <thead>
                <tr class='tr-header'>
                    <td colspan="2">전표번호: <?= htmlspecialchars($trNo) ?></td>
                    <td colspan="2" style="text-align: right;">일자: <?= htmlspecialchars($entries[0]['transactionDate']) ?></td>
                </tr>
                <tr class='tr-header'>
                    <td colspan="4">적요: <?= htmlspecialchars($entries[0]['description']) ?></td>
                </tr>
                <tr style="background: #f8f9fa;">
                    <th>계정과목</th>
                    <th>관리항목</th>
                    <th>차변</th>
                    <th>대변</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sumDr = 0; $sumCr = 0;
                foreach ($entries as $row):
                    $sumDr += $row['debitAmount'];
                    $sumCr += $row['creditAmount'];
                ?>
                <tr>
                    <td style="width: 30%;"><?= htmlspecialchars($row['accountName']) ?></td>
                    <td class="mgmt-text">
                        <?= $row['itemName'] ? "● ".htmlspecialchars($row['itemName']).": ".$row['itemValue'] : "" ?>
                    </td>
                    <td class="amt"><?= number_format($row['debitAmount']) ?></td>
                    <td class="amt"><?= number_format($row['creditAmount']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2" style="text-align: center;">전표 합계</td>
                    <td class="amt"><?= number_format($sumDr) ?></td>
                    <td class="amt"><?= number_format($sumCr) ?></td>
                </tr>
            </tfoot>
        </table>
    <?php endforeach; ?>
<?php endif; ?>