<?php

// ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓・共通の仕様部分・↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓

session_start();

// 関数ファイル読み込み
require_once ("function.php");
require('error.php');

// データベースの準備
$db['user'] = "pruser";
$db['pass'] = "pruser123";
$db['host'] = "mysql:host=localhost;dbname=prdb";
$pdo = new PDO($db['host'], $db['user'], $db['pass'], array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));

$result = array();
$error_msg = "";

// ーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーー


// ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓・初回訪問時の仕様・↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓

// ログイン中ユーザーのカート関連情報をデータベースから持ってくる

if (isset($_SESSION['id'])) {
    $user_id = $_SESSION['id'];
    $sql = "SELECT titles.name, titles.publication_on, books.id FROM titles JOIN books ON titles.id = books.title_id JOIN carts ON carts.book_id = books.id WHERE carts.user_id = {$user_id} AND books.id IN (SELECT book_id FROM carts)";
    $stmt = $pdo->query($sql);

    // カートに商品がない場合、エラーメッセージ
    $sqlCount = $stmt->rowCount();
    if ($sqlCount == 0) {
        $error_msg = "カートに商品が存在しません";
    }

    // 発刊日から新作旧作を振り分ける
    foreach ($stmt as $row) {
        $row['class'] = checkPublication($row['publication_on']);
        array_push($result, $row);
    }
}

// ーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーー


// ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓・ユーザー操作時に必要なデータの仕様・↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓

// レンタル期間、合計ポイント、料金をそれぞれ連想配列で受け取る
$rentalPriceSql = "SELECT rental_prices.span, rental_prices.price, rental_prices.point + books.point AS point FROM rental_prices JOIN rental_logs ON rental_prices.id = rental_logs.rental_price_id JOIN books ON books.id = rental_logs.book_id GROUP BY rental_prices.id ORDER BY rental_prices.id asc";
$rentalPriceStmt = $pdo->query($rentalPriceSql);
$rentalResult = $rentalPriceStmt->fetchAll();

// レンタル日数から返却日を計算して配列に入れ込む
$rental_js = array();
foreach ($rentalResult as $resultRow) {
    // $date = new DateTime('now');
    // $today = $date->format('Y-m-d');
    $resultRow['return_day'] = date('Y-m-d', strtotime("+{$resultRow['span']} day"));
    array_push($rental_js, $resultRow);
}

// それぞれのカラムを配列に持つ
$spanArray = array_column($rental_js, 'return_day');
$priceArray = array_column($rental_js, 'price');
$pointArray = array_column($rental_js, 'point');


// JSに渡す前にjson形式に変更
$spanArray_json = json_encode($spanArray);
$priceArray_json = json_encode($priceArray);
$pointArray_json = json_encode($pointArray);

// ーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーー



if ($_SERVER['REQUEST_METHOD'] == "POST") {

    //↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓・ 削除ボタン押下時の仕様・↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
    if (isset($_SESSION)) {
        if (isset($_POST['delete'])) {
            $id = $_POST['id'];

            // カートのテーブルから該当する本を削除
            $deleteSql = "DELETE FROM carts WHERE book_id = {$id}";
            $deleteStmt = $pdo->query($deleteSql);
        }
    } else {
        $error_msg = "削除処理に失敗しました";
    }


    // ーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーー


    //↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓・ 貸出ボタン押下時の仕様・↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
    if (isset($_POST['rental'])) {
        $is_error = false;
        // 入力された値に未選択が含まれていないか確認
        for ($i = 0; $i < COUNT($_POST['span']); $i++) {
            if ($_POST['span'][$i] == "") {
                $is_error = true;
                $error_msg = "レンタル期間が選択されていません";
            }
        }
        // 該当エラーがなかった時
        if ($is_error == false) {
            for ($i = 0; $i < COUNT($_POST['span']); $i++) {

                // 該当書籍の貸出状況を変更
                $checkStatusSql = "SELECT COUNT(*) FROM books WHERE id = {$_POST['book'][$i]} AND status = 1";
                $checkStatusStmt = $pdo->query($checkStatusSql);
                if (!($checkStatusStmt->execute())) {
                    $error_msg = "貸出に失敗しました";
                }
                $booksSql = "UPDATE books SET status = 0 WHERE id = {$_POST['book'][$i]}";
                $booksStmt = $pdo->query($booksSql);
                // 貸出履歴に新しいレコードを挿入する
                $rental_logSql = "INSERT INTO rental_logs(user_id, book_id, rental_price_id, rented_on, return_on, add_point) VALUES ({$user_id}, {$_POST['book'][$i]}, CAST({$_POST['span'][$i]} AS SIGNED) + 1, CURDATE(), '{$spanArray[$_POST['span'][$i]]}', {$pointArray[$_POST['span'][$i]]})";
                $rental_logStmt = $pdo->query($rental_logSql);

            }


            // カートテーブルから貸出した本を削除
            $deleteSql = "DELETE FROM carts WHERE user_id = {$user_id}";
            $deleteStmt = $pdo->query($deleteSql);
            // DB操作に成功したらページ遷移
            if ($booksStmt->rowCount() != 0 && $rental_logStmt->rowCount() != 0 && $deleteStmt->rowCount() != 0 && $error_msg == "") {
                header("Location:rental_bookslog.php");
                exit;
            } else {
                $error_msg = "貸出に失敗しました";
            }
        }
    }

    // ーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーー
}

?>


<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="rental_cart.css">
    <title>カート一覧</title>
</head>

<body>
    <?php include ('rental_header.php'); ?>

    <main>
        <h2>カート一覧</h2>
        <div class="error-msg"><?php echo $error_msg; ?></div>
        <?php if ($error_msg == "" || $error_msg == "レンタル期間が選択されていません") { ?>
            <table class="cart">
                <tr>
                    <th>タイトル名</th>
                    <th>区分</th>
                    <th>レンタル期間</th>
                    <th>返却予定日</th>
                    <th>レンタル料</th>
                    <th>ポイント</th>
                    <th>操作</th>
                </tr>
                <?php $i = 0;
                foreach ($result as $res) { ?>
                    <tr>
                        <th><?php echo $res['name']; ?></th>
                        <th class="bookClass"><?php echo $res['class'] ?></th>
                        <?php if ($res['class'] == "新作") { ?>
                            <th>
                                <form class="Radio" id="Radio">
                                    <input type="radio" name="span[<?php echo $i; ?>]" class="notSelect" value="" form="rental_form"
                                        checked>
                                    <label for="">未選択</label>
                                    <input type="radio" name="span[<?php echo $i; ?>]" class="onTheDay" form="rental_form"
                                        value="0">
                                    <label for="">当日</label>
                                    <input type="radio" name="span[<?php echo $i; ?>]" class="newOneNight" form="rental_form"
                                        value="1">
                                    <label for="">１泊２日</label>
                                    <input type="radio" name="span[<?php echo $i; ?>]" class="twoNight" form="rental_form"
                                        value="2">
                                    <label for="">２泊３日</label>
                                    <input type="hidden" name="book[<?php echo $i; ?>]" value="<?php echo $res['id']; ?>"
                                        form="rental_form">
                                </form>


                            </th>
                        <?php } else { ?>
                            <th>
                                <form class="Radio" id="Radio">
                                    <input type="radio" name="span[<?php echo $i; ?>]" class="notSelect" form="rental_form" value=""
                                        checked>
                                    <label for="">未選択</label>
                                    <input type="radio" name="span[<?php echo $i; ?>]" class="oldOneNight" form="rental_form"
                                        value="3">
                                    <label for="">１泊２日</label>
                                    <input type="radio" name="span[<?php echo $i; ?>]" class="sevenNight" form="rental_form"
                                        value="4">
                                    <label for="">７泊８日</label>
                                    <input type="hidden" name="book[<?php echo $i; ?>]" value="<?php echo $res['id']; ?>"
                                        form="rental_form">
                                </form>

                            </th>
                        <?php } ?>
                        <th class="returnDay">-</th>
                        <th class="rentalPrice">-</th>
                        <th class="rentalPoint">-</th>
                        <th>
                            <form action="" method="POST" id="delete">
                                <input type="hidden" value="<?php echo $res['id']; ?>" name="id" form="delete">
                                <input type="submit" name="delete" value="削除" form="delete">
                            </form>
                        </th>
                    </tr>
                    <?php $i++;
                } ?>
            </table>

            <table class="sum">
                <tr>
                    <th>合計</th>
                    <th id="sum"></th>
                </tr>
            </table>
            <form action="" method="POST" class="btn-rental" id="rental_form">
                <input type="submit" value="貸出" name="rental">
            </form>

        <?php } ?>
    </main>

    <script>

        window.addEventListener('DOMContentLoaded', () => {

            // PHPからの配列を受け取る
            let spanArray = <?php echo $spanArray_json; ?>;
            let priceArray = <?php echo $priceArray_json; ?>;
            let pointArray = <?php echo $pointArray_json; ?>;

            // 必要なHTML要素を取得
            const bookClass = document.getElementsByClassName('bookClass');
            const oldOneNight = document.getElementsByClassName('oldOneNight');
            const newOneNight = document.getElementsByClassName('newOneNight');
            const notSelect = document.getElementsByClassName('notSelect');
            const twoNight = document.getElementsByClassName('twoNight');
            const onTheDay = document.getElementsByClassName('onTheDay');
            const sevenNight = document.getElementsByClassName('sevenNight');
            const Radio = document.getElementsByClassName('Radio');
            const returnDay = document.getElementsByClassName('returnDay');
            const rentalPrice = document.getElementsByClassName('rentalPrice');
            const rentalPoint = document.getElementsByClassName('rentalPoint');
            const sum = document.getElementById('sum');
            const form = document.getElementById('rental-form');


            console.log(Radio);

            // ポイント、料金合算用の配列
            let Point = [0, 0, 0, 0, 0];
            let Price = [0, 0, 0, 0, 0];

            let totalPoint = 0;
            let totalPrice = 0;

            // 新作旧作とユーザーが選択するレンタル期間によって表示を変更する。ポイントと料金をその都度書き換える
            for (let i = 0; i < Radio.length; i++) {

                Radio[i].addEventListener('change', () => {
                    // 旧作側の処理
                    if (bookClass[i].innerText == "旧作") {

                        // 未選択
                        if (notSelect[i].checked) {
                            returnDay[i].textContent = "-";
                            rentalPrice[i].textContent = "-";
                            rentalPoint[i].textContent = "-";
                            Point[i] = 0;
                            Price[i] = 0;
                        }

                        // １泊２日
                        if (oldOneNight[i].checked) {
                            returnDay[i].textContent = spanArray[3];
                            rentalPrice[i].textContent = priceArray[3] + "円";
                            rentalPoint[i].textContent = pointArray[3] + "pt";
                            Point[i] = Number(pointArray[3]);
                            Price[i] = Number(priceArray[3]);


                        }

                        // 7泊８日
                        if (sevenNight[i].checked) {
                            returnDay[i].textContent = spanArray[4];
                            rentalPrice[i].textContent = priceArray[4] + "円";
                            rentalPoint[i].textContent = pointArray[4] + "pt";
                            Point[i] = Number(pointArray[4]);
                            Price[i] = Number(priceArray[4]);

                        }

                        // 配列ごとの合計を求める
                        totalPoint = Point.reduce(function (totalPoint, num) {
                            return totalPoint + num;
                        }, 0);
                        totalPrice = Price.reduce(function (totalPrice, num) {
                            return totalPrice + num;
                        }, 0);

                        // ポイント合算、料金合算を表示
                        if (totalPoint != 0) {
                            sum.innerText = `${totalPrice.toLocaleString()}円\n${totalPoint.toLocaleString()}pt`;
                        }

                    } else {

                        // 新作側の処理
                        // 未選択
                        if (notSelect[i].checked) {
                            returnDay[i].textContent = "-";
                            rentalPrice[i].textContent = "-";
                            rentalPoint[i].textContent = "-";
                            Point[i] = 0;
                            Price[i] = 0;
                        }

                        // 当日
                        if (onTheDay[i].checked) {
                            returnDay[i].textContent = spanArray[0];
                            rentalPrice[i].textContent = priceArray[0] + "円";
                            rentalPoint[i].textContent = pointArray[0] + "pt";
                            Point[i] = Number(pointArray[0]);
                            Price[i] = Number(priceArray[0]);

                        }

                        // １泊２日
                        if (newOneNight[i].checked) {
                            returnDay[i].textContent = spanArray[1];
                            rentalPrice[i].textContent = priceArray[1] + "円";
                            rentalPoint[i].textContent = pointArray[1] + "pt";
                            Point[i] = Number(pointArray[1]);
                            Price[i] = Number(priceArray[1]);

                        }

                        // ２泊３日
                        if (twoNight[i].checked) {
                            returnDay[i].textContent = spanArray[2];
                            rentalPrice[i].textContent = priceArray[2] + "円";
                            rentalPoint[i].textContent = pointArray[2] + "pt";
                            Point[i] = Number(pointArray[2]);
                            Price[i] = Number(priceArray[2]); 1
                        }

                        // 配列ごとの合計を求める
                        totalPoint = Point.reduce(function (totalPoint, num) {
                            return totalPoint + num;
                        }, 0);
                        totalPrice = Price.reduce(function (totalPrice, num) {
                            return totalPrice + num;
                        }, 0);

                        // ポイント合算、料金合算を表示
                        if (totalPoint != 0) {
                            sum.innerText = `${totalPrice.toLocaleString()}円\n${totalPoint.toLocaleString()}pt`;
                        }
                    }
                });
            }
        });

    </script>

</body>

</html>