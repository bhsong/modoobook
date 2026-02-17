<style>
    .search-box { background: #f1f3f5; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
    .ledger-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .ledger-table th, .ledger-table td { border: 1px solid #dee2e6; padding: 12px; text-align: center; }
    .amt { text-align: right; font-family: 'Consolas', monospace; }
    .balance-col { background-color: #f8f9fa; font-weight: bold; }
    .base-row { background-color: #e9ecef; font-weight: bold; }
</style>

<h2>계정별 원장</h2>

<div class="search-box">
    <form method="GET" action="/index.php">
        <input type="hidden" name="action" value="ledger">

        계정:
        <select name="account_id" required>
                <option value="">-- 계정 선택 --</option>
                <?php foreach($account_list as $acc): ?>
                    <option value="<?= $acc['accountId'] ?>" <?= $acc_id == $acc['accountId'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($acc['accountName']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        기간:
        <input type="date" name="from_date" value="<?= htmlspecialchars($from_date) ?>"> ~
        <input type="date" name="to_date" value="<?= htmlspecialchars($to_date) ?>">
        <button type="submit">조회</button>
    </form>
</div>

<?php if (isset($error_message)): ?>
    <p style="color:red; font-weight:bold;">ERROR: <?= htmlspecialchars($error_message) ?></p>
<?php endif; ?>

<?php if ($isSearch && !empty($ledger_data)): ?>
    <?php
        $currentBalance = ($ledger_data[0]['base_balance']) ?? 0;   // 시작 잔액 설정
    ?>
    <table class="ledger-table">
        <thead>
            <tr style="background:#343a40; color:white;">
                <th>날짜</th>
                <th>전표번호 / 적요</th>
                <th>차변(+)</th>
                <th>대변(-)</th>
                <th>잔액</th>
            </tr>
        </thead>
        <tbody>
            <tr class="base-row">
                <td><?= htmlspecialchars($from_date) ?></td>
                <td>[이월] 전일까지의 잔액</td>
                <td colspan="2"></td>
                <td class="amt"><?= number_format($currentBalance ?? 0) ?></td>
        
            </tr>

            <?php foreach ($ledger_data as $row):
                // 잔액 계산 로직 (자산/비용: 차변+, 대변- / 부채/자본/수익: 반대지만 일단 단순 차감으로 구현)
                // 추후 계정 타입에 따라 더하기/빼기 로직을 정교화 가능
                $currentBalance += ($row['debitAmount'] - $row['creditAmount']);
            ?>
            <tr>
                <td><?= htmlspecialchars($row['transactionDate']) ?></td>
                <td style="text-align: left;">
                    <small style="color: blue;"><?= htmlspecialchars($row['transactionNumber']) ?></small><br>
                    <?= htmlspecialchars($row['description']) ?>
                    <?php if($row['itemName']): ?>
                        <br><span style="font-size: 11px; color: green;">[<?= htmlspecialchars($row['itemName']) ?>: <?= htmlspecialchars($row['itemValue']) ?>]</span>
                    <?php endif; ?>
                </td>
                <td class="amt"><?= $row['debitAmount'] > 0 ? number_format($row['debitAmount'] ?? 0) : '-' ?></td>
                <td class="amt"><?= $row['creditAmount'] > 0 ? number_format($row['creditAmount'] ?? 0) : '-' ?></td>
                <td class="amt balance-col"><?= number_format($currentBalance ?? 0) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

<?php elseif ($isSearch): ?>
    <p style="padding: 20px; text-align: center;">조회된 내역이 없습니다.</p>
<?php else: ?>
    <p style="padding: 20px; text-align: center; color: #666;">계정과 기간을 선택하여 조회하세요.</p>
<?php endif; ?>            
