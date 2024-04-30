<?php
session_start();

// SQLを使うときのデータベースに関する設定
$db['user'] = "pruser";
$db['pass'] = "pruser123";
$db['host'] = "mysql:host=localhost;dbname=prdb";
$pdo = new PDO($db['host'], $db['user'], $db['pass'], array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));

// 描画用のエラーメッセージを配列として初期化
$error_msg = array();

// ログインボタンが押されればGETメソッドで入力値の正誤判定を行う
if (!empty($_GET)) {
    if ($_SERVER['REQUEST_METHOD'] == "GET") {

        // 入力値を変数に持つ
        $id = $_GET['id'];

        // バリデーション通過用の真偽値をセット
        $id_error = false;

        // バリデーション部分（エラーがあれば真偽値をtrueに変えてSQL文まで通さない）
        if (empty($id)) {
            $error_msg[] = 'IDを入力してください';
            $id_error = true;
        }
        if (!empty($id) && !preg_match('/^[0-9]+$/', $id)) {
            $error_msg[] = 'IDを半角数字で入力してください';
            $id_error = true;
        }

        // バリデーションを通過すれば、SQLを実行
        if ($id_error == false) {
            $sql = "SELECT * FROM users WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch();

            // SELECTで取り出した行が存在すればログイン成功とする
            if ($row == null) {
                $error_msg[] = "ユーザー情報が存在しません";
            } else {

                // 取り出した会員idと会員名を変数に保持、履歴一覧へ遷移
                $_SESSION['id'] = $row['id'];
                $_SESSION['name'] = $row['name'];
                header('Location:rental_bookslog.php');
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
    <link rel="stylesheet" href="rental_loginpage.css">
    <title>レンタル書籍ログイン画面</title>
</head>

<body>
    <!-- ログインフォーム部分 -->
    <form action="" method="get">
        <h1>レンタル書籍</h1>
        <div class="id">
            <label for="id">会員ID</label>
            <input type="text" name="id" id="id">
        </div>
        <div class="error-msg">

            <!-- エラーメッセージを一つずつ表示 -->
            <?php foreach ($error_msg as $error) {
                echo $error;
            } ?>
        </div>
        <input type="submit" name="login" value="ログイン" id="btn-login">
    </form>
</body>

</html>