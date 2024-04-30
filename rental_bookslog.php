<?php
session_start();

// 関数ファイル読み込み
require_once ("function.php");

// データベースの準備
$db['user'] = "pruser";
$db['pass'] = "pruser123";
$db['host'] = "mysql:host=localhost;dbname=prdb";
$pdo = new PDO($db['host'], $db['user'], $db['pass'], array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));


// エラーメッセージを初期化
$error_msg = "";


// 検索ボタンが押されたときの処理
if (isset($_GET['search'])) {

    // 抽出した連想配列の値を変更するため、変更後の配列を宣言
    $result = array();

    // フォームから受け取った値を保持
    $name = $_GET['title'];
    $rented_start = $_GET['rented_start_date'];
    $rented_end = $_GET['rented_end_date'];

    // SQL用の文を配列で初期化
    $sqlText = array();

    // タイトルが入力されていれば、部分一致のWHERE文を代入
    if (isset($name) && $name != "") {
        $sqlText[] = "titles.name LIKE '%{$name}%'";
    }

    // 貸出日付が入力されていれば、それぞれの条件に合致するWHERE式を代入
    if (isset($rented_start) && $rented_start != "") {
        $sqlText[] = "rental_logs.rented_on >= \"{$rented_start}\"";
    }
    if (isset($rented_end) && $rented_end != "") {
        $sqlText[] = "rental_logs.rented_on <= \"{$rented_end}\"";
    }

    // ステータスの入力値によってWHERE文を代入
    switch ($_GET['status']) {
        case '0':
            break;
        case '1':
            $sqlText[] = 'rental_logs.rented_on IS NOT NULL AND return_logs.returned_on IS NOT NULL';
            break;
        case '2':
            $sqlText[] = 'rental_logs.rented_on IS NOT NULL AND return_logs.returned_on IS NULL AND rental_logs.return_on >= CURDATE()';
            break;
        case '3':
            $sqlText[] = 'rental_logs.rented_on IS NOT NULL AND return_logs.returned_on IS NULL AND rental_logs.return_on < CURDATE()';
    }

    // 配列からすべてのWHERE文を連結する。
    // 配列の最初の要素にはWHERE、それ以外はANDをつけて連結する
    $count = 0;
    $whereSql = "";
    foreach ($sqlText as $text) {
        if ($count == 0) {
            $whereSql = "WHERE {$text}";
        } else {
            $whereSql = "{$whereSql} AND {$text}";
        }
        $count++;
    }

    // SQL文
    $sql = "SELECT titles.name, rental_logs.rented_on, rental_prices.span, rental_logs.return_on, rental_prices.price, return_logs.returned_on 
        FROM rental_logs INNER JOIN books ON rental_logs.book_id = books.id 
        LEFT JOIN return_logs ON rental_logs.id = return_logs.rental_log_id 
        INNER JOIN rental_prices ON rental_prices.id = rental_logs.rental_price_id 
        INNER JOIN titles ON titles.id = books.title_id {$whereSql} 
        ORDER BY rental_logs.rented_on desc, rental_logs.id desc";
    $stmt = $pdo->query($sql);

    // 抽出回数が0であった場合、エラーメッセージ代入
    $sqlCount = $stmt->rowCount();
    if ($sqlCount == 0) {
        $error_msg = "該当する履歴が存在しません";
    }

    foreach ($stmt as $row) {
        // 返却日があるかどうか  →  あればステータスを返却済みに
        //           ↓
        //           なければ返却予定日を過ぎているかで場合分け（過ぎていれば延滞中、過ぎていなければレンタル中
        $row['status'] = checkStatus($row['returned_on'], $row['return_on']);

        // レンタル期間に入っている数値によって表示する値を変える
        $row['span'] = checkSpan($row['span']);

        // 変更後の値を格納
        array_push($result, $row);
    }

    // // 検索ボタンの押下なし（初期値）もしくは、クリアボタンが押されたときの処理
} else {
    // 抽出した連想配列の値を変更するため、変更後の配列を宣言
    $result = array();

    // タイトル名、貸出日付、レンタル期間、返却予定、レンタル料をそのまま抽出
    // ステータスは、貸出日付、返却予定に加えて返却日付も抽出することで求める
    $sql = "SELECT titles.name, rental_logs.rented_on, rental_prices.span, rental_logs.return_on, rental_prices.price, return_logs.returned_on 
               FROM rental_logs INNER JOIN books ON rental_logs.book_id = books.id 
               LEFT JOIN return_logs ON rental_logs.id = return_logs.rental_log_id 
               INNER JOIN rental_prices ON rental_prices.id = rental_logs.rental_price_id 
               INNER JOIN titles ON titles.id = books.title_id 
               ORDER BY rental_logs.rented_on desc, rental_logs.id desc";
    $stmt = $pdo->query($sql);

    foreach ($stmt as $row) {

        // 返却日があるかどうか  →  あればステータスを返却済みに
        //           ↓
        //           なければ返却予定日を過ぎているかで場合分け（過ぎていれば延滞中、過ぎていなければレンタル中
        $row['status'] = checkStatus($row['returned_on'], $row['return_on']);

        // レンタル期間に入っている数値によって表示する値を変える
        $row['span'] = checkSpan($row['span']);

        // 変更後の値を格納
        array_push($result, $row);
    }
}

?>




<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="rental_bookslog.css">
    <title>レンタル履歴一覧</title>
</head>

<body>
    <?php include ('rental_header.php'); ?>
    <main>
        <h1>履歴一覧</h1>
        <div class="error-msg"><?php echo $error_msg; ?></div>
        <form action="" id="search_form" method="GET">
            <p class="form">検索</p>
            <div class="title form">
                <label for="title">タイトル名<span></span></label>
                <input type="text" name="title" id="title">
            </div>
            <div class="rented_date form">
                <label for="rented_date">貸出日付<span></span></label>
                <input type="date" name="rented_start_date">～<input type="date" name="rented_end_date">
            </div>
            <div class="status form">
                <label for="status">ステータス<span></span></label>
                <select name="status" id="status">
                    <option value="0" selected>すべて</option>
                    <option value="1">返却済み</option>
                    <option value="2">レンタル中</option>
                    <option value="3">延滞中</option>
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
                <th>貸出日付</th>
                <th>レンタル期間</th>
                <th>ステータス</th>
                <th>返却予定日</th>
                <th>レンタル料</th>
            </tr>
            <?php foreach ($result as $res) { ?>
                <tr>
                    <th><?php echo $res['name']; ?></th>
                    <th><?php echo $res['rented_on']; ?></th>
                    <th><?php echo $res['span']; ?></th>
                    <th><?php echo $res['status']; ?></th>
                    <th><?php echo $res['return_on']; ?></th>
                    <th><?php echo $res['price']; ?></th>
                </tr><?php } ?>
        </table>
    </main>
</body>

</html>