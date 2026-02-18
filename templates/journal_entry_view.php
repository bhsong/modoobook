<style>
    .line-row { border: 1px solid #ddd; padding: 10px; margin-bottom: 5px; background: #fafafa; }
    .mgmt-section { background: #eef; padding: 5px; margin-top: 5px; display: none; border-radius: 4px; }
    .total-box { font-weight: bold; font-size: 1.2em; margin-top: 20px; color: blue; }
</style>

<form action="/index.php?action=journal_save" method="POST" id="transForm">
    <h3>전표 입력 (N:M 복식부기)</h3>
    일자: <input type="date" name="tr_date" value="<?= date('Y-m-d') ?>">
    적요: <input type="text" name="description" style="width:300px" required>

    <div id="lines_container"></div>

    <button type="button" onclick="addLine()">+ 라인 추가</button>

    <div class="total-box">
        차변 합계: <span id="total_dr">0</span>
        대변 합계: <span id="total_cr">0</span>
        차액: <span id="diff">0</span>
    </div>
    <br>
    <button type="submit" id="submitBtn" disabled>전표 저장</button>
</form>

<script>
// PHP 데이터를 JS에 주입
// $account_map: {accountId: [{itemId, itemName}, ...]}
// $accounts: getLeafAccounts() 결과 [{accountId, accountName, parentName, ...}]
const accountMap = <?= json_encode($account_map) ?>;
const accounts   = <?= json_encode($accounts) ?>;
let rowIdx = 0;

// level=3 계정을 parentName 기준으로 optgroup 그룹핑
function buildAccountOptions() {
    const grouped = {};
    accounts.forEach(function(a) {
        var group = a.parentName || '기타';
        if (!grouped[group]) grouped[group] = [];
        grouped[group].push(a);
    });

    var html = '<option value="">선택</option>';
    Object.entries(grouped).forEach(function([group, accs]) {
        html += '<optgroup label="' + group + '">';
        html += accs.map(function(a) {
            return '<option value="' + a.accountId + '">' + a.accountName + '</option>';
        }).join('');
        html += '</optgroup>';
    });
    return html;
}

function addLine() {
    var container = document.getElementById('lines_container');
    var div       = document.createElement('div');
    div.className = 'line-row';
    div.id        = 'row_' + rowIdx;

    div.innerHTML =
        '계정: <select name="acc[]" onchange="toggleMgmt(' + rowIdx + ', this.value)" required>' +
            buildAccountOptions() +
        '</select> ' +
        '차변: <input type="number" name="dr[]" class="dr-input" value="0" onchange="calcTotal()"> ' +
        '대변: <input type="number" name="cr[]" class="cr-input" value="0" onchange="calcTotal()"> ' +
        '<button type="button" onclick="this.parentElement.remove(); calcTotal();">삭제</button>' +
        '<div id="mgmt_' + rowIdx + '" class="mgmt-section"></div>';

    container.appendChild(div);
    rowIdx++;
}

function toggleMgmt(idx, accId) {
    var mgmtDiv = document.getElementById('mgmt_' + idx);
    mgmtDiv.innerHTML = '';
    mgmtDiv.style.display = 'none';

    if (accountMap[accId]) {
        mgmtDiv.style.display = 'block';
        accountMap[accId].forEach(function(item) {
            mgmtDiv.innerHTML +=
                '<small>' + item.itemName + ':</small> ' +
                '<input type="hidden" name="item_id[' + idx + ']" value="' + item.itemId + '">' +
                '<input type="text" name="item_val[' + idx + ']" placeholder="값 입력"> ';
        });
    }
}

function calcTotal() {
    var drSum = 0, crSum = 0;
    document.querySelectorAll('.dr-input').forEach(function(i) { drSum += Number(i.value); });
    document.querySelectorAll('.cr-input').forEach(function(i) { crSum += Number(i.value); });

    document.getElementById('total_dr').innerText = drSum.toLocaleString();
    document.getElementById('total_cr').innerText = crSum.toLocaleString();
    var diff = drSum - crSum;
    document.getElementById('diff').innerText = diff.toLocaleString();

    // 차액이 0이고 합계가 0이 아닐 때만 저장 활성화
    document.getElementById('submitBtn').disabled = (diff !== 0 || drSum === 0);
}

// 첫 라인 자동 추가
addLine();
</script>
