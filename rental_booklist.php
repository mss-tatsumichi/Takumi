<?php
session_start();

// 関数ファイル読み込み
require_once ("function.php");

// データベースの準備
$db['user'] = "pruser";
$db['pass'] = "pruser123";
$db['host'] = "mysql:host=localhost;dbname=prdb";
$pdo = new PDO($db['host'], $db['user'], $db['pass'], array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));


$cartError = false;

// エラー用の変数
$error_msg = "";

// 表示用配列を初期化
$result = array();

// カート操作ボタン押下時
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if (isset($_POST['operate'])) {

        // ユーザーID保持
        $user_id = $_SESSION['id'];

        // どの本を追加したかの識別
        $operateNumber = $_POST['btnNumber'];

        // 選択した商品テーブルにあるか確認
        $existSql = "SELECT * FROM carts JOIN books ON carts.book_id = books.id WHERE {$operateNumber} IN (SELECT book_id FROM carts)";
        $existStmt = $pdo->query($existSql);

        // 選択した商品が未貸出か確認
        $statusSql = "SELECT * FROM carts JOIN books ON carts.book_id = books.id WHERE books.id = {$operateNumber} AND books.status = 1";
        $statusStmt = $pdo->query($statusSql);

        // カート追加した数を確認
        $cartCountSql = "SELECT * FROM carts WHERE user_id = {$user_id}";
        $cartCountStmt = $pdo->query($cartCountSql);

        // エラー表示部分
        if ($existStmt->rowCount() > 0) {
            $cartError = true;
            $alert = "<script type='text/javascript'>alert('商品の在庫が無くなりました');</script>";
            echo $alert;
        } else {
            if ($statusStmt->rowCount() > 0) {
                $cartError = true;
                $alert = "<script type='text/javascript'>alert('カートの追加に失敗しました');</script>";
                echo $alert;
            } else {
                if ($cartCountStmt->rowCount() > 4) {
                    $cartError = true;
                    $alert = "<script type='text/javascript'>alert('カートが既に上限5件に達しています');</script>";
                    echo $alert;
                }
            }
        }

        // エラーがなければカートに追加
        if ($cartError == false) {
            $sql = "INSERT INTO carts (user_id ,book_id) VALUES ($user_id, $operateNumber)";
            $stmt = $pdo->query($sql);
        }
    }
}
// 検索ボタン押下時
if (isset($_POST['search'])) {

    // SQL文に使う配列
    $sqlText = array();

    // タイトルが入力されていれば、部分一致のWHERE文を代入
    if (isset($_POST['title']) && $_POST['title'] != "") {
        $sqlText[] = "AND titles.name LIKE '%{$_POST['title']}%'";
    }

    // 新作旧作の判定部分
    $classText = "";
    switch ($_POST['class']) {
        case '':
            $sqlText[] = '';
            break;
        case 'new':
            $sqlText[] = "AND DATEDIFF(CURDATE(), titles.publication_on) <= 365";
            break;
        case 'old':
            $sqlText[] = "AND DATEDIFF(CURDATE(), titles.publication_on) > 365";
            break;
    }

    // 書籍ポイントの昇順、降順判定
    if ($_POST['order'] == "asc") {
        $sqlText[] = "ORDER BY books.point ASC";
    } else {
        $sqlText[] = "ORDER BY books.point DESC";
    }

    // WHERE,ORDER分を連結
    $sqlsearchText = "";
    foreach ($sqlText as $text) {
        $sqlsearchText = "{$sqlsearchText} {$text}";
    }

    // クエリ部分
    $sql = "SELECT books.id, titles.name, titles.publication_on, books.point FROM titles JOIN books ON titles.id = books.title_id WHERE books.status = 1 AND books.id NOT IN (SELECT book_id FROM carts) {$sqlsearchText}";
    $stmt = $pdo->query($sql);

    // 抽出が0件の場合、エラーを表示
    $count = $stmt->rowCount();
    if ($count == 0) {
        $error_msg = "該当する書籍が存在しません";
    }

    // 連想配列の発刊日要素を使って、新作旧作の表示を加える
    foreach ($stmt as $row) {
        $row['class'] = checkPublication($row['publication_on']);
        array_push($result, $row);
    }
}
// 初期値、クリアボタン押下時
else {
    // クエリ部分
    $sql = "SELECT books.id, titles.name, titles.publication_on, books.point FROM titles JOIN books ON titles.id = books.title_id WHERE books.status = 1 AND books.id NOT IN (SELECT book_id FROM carts) ORDER BY books.point asc";
    $stmt = $pdo->query($sql);

    // 連想配列の発刊日要素を使って、新作旧作の表示を加える
    foreach ($stmt as $row) {
        $row['class'] = checkPublication($row['publication_on']);
        array_push($result, $row);
    }

}




?><!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="rental_booklist.css">
    <title>書籍一覧</title>
</head>

<body>
    <?php include ('rental_header.php'); ?>
    <main>
        <h1>書籍一覧</h1>
        <div class="error-msg"><?php echo $error_msg; ?></div>
        <form action="" id="search_form" method="POST">
            <p class="form">検索</p>
            <div class="title form">
                <label for="title">タイトル名</label>
                <input type="text" name="title" id="title">
            </div>
            <div class="class form">
                <label for="class">区分</label>
                <select name="class" id="class">
                    <option value="" selected>すべて</option>
                    <option value="new">新作</option>
                    <option value="old">旧作</option>
                </select>
            </div>
            <div class="order form">
                <label for="order">並び順</label>
                <select name="order" id="order">
                    <option value="asc" selected>書籍ポイント昇順</option>
                    <option value="desc">書籍ポイント降順</option>
                </select>
            </div>
            <div class="btn-form">
                <input type="submit" name="search" value="検索">
                <input type="submit" name="clear" value="クリア">
            </div>
        </form>
        <table>
            <tr>
                <th>タイトル名</th>
                <th>区分</th>
                <th>書籍ポイント</th>
                <th>操作</th>
            </tr>
            <?php foreach ($result as $res) { ?>
                <tr>
                    <th><?php echo $res['name']; ?></th>
                    <th><?php echo $res['class']; ?></th>
                    <th><?php echo $res['point']; ?>pt</th>
                    <form action="" method="POST">
                        <input type="hidden" name="btnNumber" value="<?php echo $res['id'] ?>">
                        <th><input type="submit" name="operate" value="カート追加"></th>
                    </form>
                </tr><?php } ?>
        </table>
    </main>
</body>

</html>