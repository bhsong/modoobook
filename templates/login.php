<!DOCTYPE html>
<html>
<head><title>복식부기 가계부 - 로그인</title></head>
<body>
    <h1>복식부기 가계부</h1>
    <h2>1. 사용자 선택</h2>

    <form method="POST" action="/index.php?action=register_user">
        <?= \App\Core\CsrfGuard::tokenField() ?>
        <input type="hidden" name="action" value="register_user">
        <input type="text" name="userName" placeholder="사용자 이름" required>
        <button type="submit" name="add_user">등록</button>
    </form>

    <ul>
        <?php foreach ($user_list as $u) { ?>
            <li>
                <?= htmlspecialchars($u['userName']) ?>
                <form method="POST" action="/index.php?action=login_process" style="display:inline">
                    <?= \App\Core\CsrfGuard::tokenField() ?>
                    <input type="hidden" name="select_user" value="<?= (int) $u['userId'] ?>">
                    <button type="submit">[선택]</button>
                </form>
            </li>
        <?php } ?>
    </ul>
</body>
</html>