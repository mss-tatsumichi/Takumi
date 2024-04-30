<?php
session_start();

// DB用の設定
$db['user'] = "pruser";
$db['pass'] = "pruser123";
$db['host'] = "mysql:host=localhost;dbname=prdb";
$pdo = new PDO($db['host'], $db['user'], $db['pass'], array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));

$id = "";
$password = "";
$errors['id'] = "";
$errors['password'] = "";
$errors['sql'] = "";

// ログインボタン押下時処理
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id = $_POST['id'];
    $password = $_POST['password'];

    // エラー初期値false エラーがあればtureに切り替え
    $id_error = false;

    // IDバリデーション
    if (empty($id)) {
        $errors['id'] = "※IDを入力してください。";
        $id_error = true;
    } else if (!preg_match('/^[0-9]+$/', $id)) {
        $errors['id'] = "※IDを半角数字で入力してください。";
        $id_error = true;
    }

    // パスワードバリデーション
    if (empty($password)) {
        $errors['password'] = "※passwordを入力してください。";
        $pass_error = true;
    } else if (!preg_match('/^[a-zA-Z0-9]+$/', $password)) {
        $errors['password'] = "※passwordを半角英数字で入力してください。";
        $pass_error = true;
    } else if (strlen($password) < 12) {
        $errors['password'] = '※passwordを12文字以上で入力してください';
        $pass_error = true;
    } else {
        $password = $_POST['password'];
        $pass_error = false;
    }

    // バリデーション通過時、DB接続
    if ($id_error == false && $pass_error == false) {
        $sql = "SELECT * FROM manager WHERE id = '" . $id . "' AND password = '" . $password . "'";
        $stmt = $pdo->query($sql);

        // SQLの取得結果が0件の時（つまり該当するID、パスワードが存在しないとき）エラー表示
        $count = $stmt->rowCount();
        if ($count == 0) {
            $errors['sql'] = 'ユーザー情報が存在しません。';
        } else {

            // ログイン処理成功時、セッションにID、パスワード、ユーザー名を保持して書籍ページへ遷移
            foreach ($stmt as $row) {
                $_SESSION['id'] = $row['id'];
                $_SESSION['password'] = $row['password'];
                $_SESSION['name'] = $row['name'];
                header("Location: book_list.php");
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="login_style.css">
    <title>Login Menu</title>

<body>
    <form action="login_page.php" method="post">

        <?php foreach ($errors as $error) { ?>
            <div class="error"><?php echo $error; ?></div>
        <?php } ?>

        <ul>
            <li>
                <label for="id">ID</label><span>:</span></div>
                <input type="text" name="id" id="id"><br>
            </li>
            <li>
                <label for="password">PASSWORD</label><span>:</span>
                <input type="text" name="password" id="password"><br>
            </li>
            <li>
                <input type="submit" value="ログイン" id="submit">
            </li>
        </ul>
    </form>
</body>

</html>