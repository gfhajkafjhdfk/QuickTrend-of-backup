<?php
require_once __DIR__ . '/auth_check.php';
$name = htmlspecialchars($_SESSION['user_name'] ?? '', ENT_QUOTES, 'UTF-8');
$token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
// クエリ文字列のコードを固定文言に変換して表示する（文字列をそのまま出さないためXSSの心配なし）
$messages = [
    'name_changed' => ['ok', 'ニックネームを変更しました。'],
    'password_changed' => ['ok', 'パスワードを変更しました。'],
    'invalid_name' => ['error', 'ニックネームは2〜50文字で入力してください。'],
    'name_taken' => ['error', 'このニックネームは既に使われています。'],
    'password_mismatch' => ['error', '新しいパスワードが確認用と一致しません。'],
    'weak_password' => ['error', 'パスワードは8文字以上で、英字と数字を両方含めてください。'],
    'wrong_password' => ['error', '現在のパスワードが正しくありません。'],
    'invalid_request' => ['error', '不正なリクエストです。もう一度お試しください。'],
];
[$msgClass, $msgText] = $messages[$_GET['msg'] ?? ''] ?? [null, null];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickTrend - アカウント設定</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="auth-page">
    <main class="box">
        <nav><a href="QuickTrend.php">マイページへ戻る</a></nav>
        <h1>アカウント設定</h1>
        <?php if ($msgText !== null): ?>
            <p class="<?php echo $msgClass; ?>"><?php echo $msgText; ?></p>
        <?php endif; ?>

        <h2>ニックネーム変更</h2>
        <form method="post" action="change_username.php">
            <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
            <label for="name">新しいニックネーム（2〜50文字）
                <input type="text" id="name" name="name" required minlength="2" maxlength="50"
                       value="<?php echo $name; ?>" autocomplete="nickname">
            </label>
            <button type="submit">変更する</button>
        </form>

        <h2>パスワード変更</h2>
        <form method="post" action="change_password.php">
            <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
            <label for="current_password">現在のパスワード
                <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
            </label>
            <label for="new_password">新しいパスワード（8文字以上・英字と数字を含む）
                <input type="password" id="new_password" name="new_password" required autocomplete="new-password"
                       minlength="8" pattern="(?=.*[A-Za-z])(?=.*[0-9]).{8,}"
                       title="8文字以上で、英字と数字を両方含めてください">
            </label>
            <label for="new_password_confirm">新しいパスワード（確認）
                <input type="password" id="new_password_confirm" name="new_password_confirm" required autocomplete="new-password">
            </label>
            <button type="submit">変更する</button>
        </form>
    </main>
</body>
</html>
