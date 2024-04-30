<?php
session_start();

// DB用の設定
$db['user'] = "pruser";
$db['pass'] = "pruser123";
$db['host'] = "mysql:host=localhost;dbname=prdb";
$pdo = new PDO($db['host'], $db['user'], $db['pass'], array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
$sql = "SELECT name FROM titles";
$stmt = $pdo->query($sql);

$title = "";
$price = "";
$point = "";
$errors = array();

// 登録ボタン押下時
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST)) {
        $title = $_POST['title'];
        $price = $_POST['price'];
        $point = $_POST['point'];

        // エラーカウントを設定
        $errorCount = 0;

        // 入力値ごとのバリデーション
        if (empty($title)) {
            $errors[] = "タイトルを選択してください";
            $errorCount++;
        }

        if (empty($price)) {
            $errors[] = "仕入れ価格を入力してください";
            $errorCount++;
        }

        if (!empty($price) && !preg_match('/^[0-9]+$/', $price)) {
            $errors[] = "仕入れ価格を半角数字で入力してください";
            $errorCount++;
        }

        if (empty($point)) {
            $errors[] = "書籍ポイントを入力してください";
            $errorCount++;
        }

        if (!empty($point) && !preg_match('/^[0-9]+$/', $point)) {
            $errors[] = "書籍ポイントを半角数字で入力してください";
            $errorCount++;
        }


        // バリデーション通過時、DB接続の例外処理（SQLは）try-catchを使わないほうがいいみたい。。。
        if ($errorCount == 0) {
            try {
                $formsql = "INSERT INTO books (id, title_id, status, purchase_price, point) VALUES (NULL, (SELECT id FROM titles WHERE name = \"{$title}\"), 1, {$price}, {$point})";
                $formstmt = $pdo->prepare($formsql);
                $formstmt->execute();
                header("Location: book_list.php");
                exit;
            } catch (PDOException $e) {
                $errors[] = "データの登録に失敗しました";
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
    <link rel="stylesheet" href="add_book.css">
    <title>Document</title>
</head>

<body>
    <?php include ('header.php'); ?>
    <form action="add_book.php" method="post">
        <h2>書籍新規登録</h2>
        <div class="title">
            <label for="title">タイトル：</label>
            <select name="title" id="title">
                <option value="" selected>未選択</option>
                <?php foreach ($stmt as $row) {
                    print ('<option value ="' . $row['name'] . '">' . $row['name'] . '</option>');
                } ?>
            </select>
        </div>
        <div class="price">
            <label for="price">仕入れ価格：</label>
            <input type="text" value="" id="price" name="price">
        </div>
        <div class="point">
            <label for="point">書籍ポイント：</label>
            <input type="text" value="" id="point" name="point">
        </div>
        <div class="error-msg"><?php foreach ($errors as $error) {
            echo $error; ?><br><?php
        } ?></div>
        <input type="submit" value="登録" name="regist">
    </form>
</body>

</html>