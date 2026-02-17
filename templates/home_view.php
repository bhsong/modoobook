<div style='padding: 20px;'>
    <h2>📊 대시보드</h2>
    
    <p>환영합니다. <strong><?= h($userName) ?></strong>님!</p>

    <p>위 메뉴를 통해 가계부를 관리하세요.</p>

    <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
        <h4>📌 바로가기</h4>  
        <ul>
            <li><a href="/index.php?action=journal_entry">전표 입력하기</a></li>
            <li><a href="/index.php?action=journal_list">전표 조회하기</a></li>
            <li><a href="/index.php?action=ledger">계정별 원장 보기</a></li>
        </ul>
    </div>
    <?php // 나중에 '이번 달 지출 합계' 같은 요약 정보 추가 예정(todo) ?>   
</div>