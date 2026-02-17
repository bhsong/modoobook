<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>복식부기 가계부 System</title>
    <style>
        body { font-family: 'Apple SD Gothic Neo', 'Malgun Gothic', sans-serif; padding: 20px; margin: 0; background-color: #f4f6f9; }
        main { background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 1000px; margin: 0 auto; }
        h2 { border-bottom: 2px solid #eee; padding-bottom: 10px; color: #333; }
        a { text-decoration: none; color: #007bff; }
        a:hover { text-decoration: underline; }
        
        /* 테이블 공통 스타일 */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #dee2e6; padding: 12px; }
        th { background-color: #f8f9fa; }
        
        /* 버튼 공통 스타일 */
        button { cursor: pointer; padding: 5px 10px; }
    </style>
</head>
<body>

    <?php if (isset($_SESSION['userId'])): ?>
        <?php include __DIR__ . '/menu.php'; ?>
        <hr style="margin: 20px 0; border: 0; border-top: 1px solid #ddd;">
    <?php endif; ?>

    <main>
        <?= $content ?>
    </main>

    <footer style="text-align: center; margin-top: 40px; color: #888; font-size: 0.8em;">
        &copy; <?= date('Y') ?> Accounting System. All rights reserved.
    </footer>
</body>
</html>