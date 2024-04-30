<?php
session_start();

// DB用の設定
$db['user'] = "pruser";
$db['pass'] = "pruser123";
$db['host'] = "mysql:host=localhost;dbname=prdb";
$pdo = new PDO($db['host'], $db['user'], $db['pass'], array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));

// SQLのWHERE文を配列として持つ
$strReq = array();
$str = '';

// クリアボタン押下時、書籍一覧にリダイレクト
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header("Location: book_list.php");
    exit;
}

// 検索ボタン押下時
if (isset($_GET['search'])) {

    // タイトルが入力されていれば部分一致のWHERE文を格納
    if (!empty($_GET['title'])) {
        $strReq[] = "titles.name LIKE \"%{$_GET['title']}%\" ";
    } else {
        $strReq[] = '';
    }

    // 貸出状況の選択値によって、場合分け
    if ((isset($_GET['on_status']) && $_GET['on_status'] != '')) {
        if (isset($_GET['not_status']) && $_GET['not_status'] != '') {
            if (isset($_GET['cannotstatus']) && $_GET['cannotstatus'] != '') {
                $str = "(0, 1, 2)";
            } else {
                $str = "(0, 1)";
            }
        } else {
            if (isset($_GET['cannotstatus']) && $_GET['cannotstatus'] != '') {
                $str = "(0, 2)";
            } else {
                $str = "(0)";
            }
        }

    } else {
        if (isset($_GET['not_status']) && $_GET['not_status'] != '') {
            if (isset($_GET['cannotstatus']) && $_GET['cannotstatus'] != '') {
                $str = "(1, 2)";
            } else {
                $str = "(1)";
            }
        } else {
            if (isset($_GET['cannotstatus']) && $_GET['cannotstatus'] != '') {
                $str = "(2)";
            } else {
                $str = "";
            }
        }
    }

    // 貸出状況が未選択でない場合、WHERE文を格納
    if ((isset($_GET['on_status']) && $_GET['on_status'] != '') || (isset($_GET['not_status']) && $_GET['not_status'] != '') || (isset($_GET['cannotstatus']) && $_GET['cannotstatus'] != '')) {
        $strReq[] = "books.status IN {$str}";
    } else {
        $strReq[] = '';
    }

    // 最小値が選択されていれば選択値以上の金額になるよう条件を組む
    if (isset($_GET['min_price']) && $_GET['min_price'] != "") {
        $strReq[] = "books.purchase_price >= {$_GET['min_price']}";
    } else {
        $strReq[] = '';
    }

    // 最大値が選択されていれば選択値以下の金額になるよう条件を組む
    if (isset($_GET['max_price']) && $_GET['max_price'] != "") {
        $strReq[] = "books.purchase_price <= {$_GET['max_price']}";
    } else {
        $strReq[] = '';
    }

    // 開始日が選択されていれば選択日より後の日付になるよう条件を組む
    if (isset($_GET['startdate']) && $_GET['startdate'] != "") {
        var_dump($_GET['startdate']);
        $strReq[] = "titles.publication_on >= \"{$_GET['startdate']}\"";
    } else {
        $strReq[] = '';
    }

    // 終了日が選択されていれば選択日より前の日付になるよう条件を組む
    if (isset($_GET['enddate']) && $_GET['enddate'] != "") {
        $strReq[] = "titles.publication_on <= \"{$_GET['enddate']}\"";
    } else {
        $strReq[] = '';
    }

    // 配列に格納されているWHERE文を結合
    $sqlText = "";
    $count = 0;
    for ($i = 0; $i < 6; $i++) {
        if ($strReq[$i] == "") {
            $sqlText = "{$sqlText} {$strReq[$i]}";
        } else {
            if ($count == 0) {
                $sqlText = "WHERE {$sqlText} {$strReq[$i]}";
                $count++;
            } else {
                $sqlText = "{$sqlText} AND {$strReq[$i]}";
            }
        }
    }
    $sql = "SELECT books.id, titles.name, books.status, books.purchase_price, titles.publication_on FROM books JOIN titles ON books.title_id = titles.id {$sqlText} ORDER BY books.id asc";
} else {
    $sql = "SELECT books.id, titles.name, books.status, books.purchase_price, titles.publication_on FROM books JOIN titles ON books.title_id = titles.id ORDER BY books.id asc";
}

$stmt = $pdo->query($sql);
$count = $stmt->rowCount();
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="book_list.css">
    <title>book_list</title>
</head>

<body>
    <?php include ('header.php'); ?>
    <h2>検索条件</h2>
    <form action="book_list.php" method="get">
        <div class="titles">
            <label for="title">タイトル：</label>
            <input type="text" name="title" id="title"><br>
        </div>
        <div class="status">
            <label for="status">貸出状況:</label>
            <input type="checkbox" name="on_status" value="0">貸出中
            <input type="checkbox" name="not_status" value="1">未貸出
            <input type="checkbox" name="cannotstatus" value="2">貸出不良<br>
        </div>
        <div class="price">
            <label for="min_price">仕入れ価格</label>
            <select name="min_price">
                <option value="">未選択</option>
                <option value="0">0</option>
                <option value="500">500</option>
                <option value="1000">1000</option>
                <option value="1500">1500</option>
                <option value="2000">2000</option>
                <option value="2500">2500</option>
                <option value="3000">3000</option>
                <option value="3500">3500</option>
                <option value="4000">4000</option>
                <option value="4500">4500</option>
                <option value="5000">5000</option>

            </select>
            <label for="max_price">～</label>
            <select name="max_price">
                <option value="">未選択</option>
                <option value="0">0</option>
                <option value="500">500</option>
                <option value="1000">1000</option>
                <option value="1500">1500</option>
                <option value="2000">2000</option>
                <option value="2500">2500</option>
                <option value="3000">3000</option>
                <option value="3500">3500</option>
                <option value="4000">4000</option>
                <option value="4500">4500</option>
                <option value="5000">5000</option>
            </select><br>
        </div>
        <div class="date">
            <label for="startdate">発刊日</label>
            <input type="date" name="startdate">
            <label for="enddate">～</label>
            <input type="date" name="enddate"><br>
        </div>
        <div class="btn">
            <input type="submit" name="search" id="search" value="検索開始">
        </div>
    </form>
    <form action="" method="post" class="reset">
        <input type="submit" value="クリア" id="reset">
    </form>





    <h2>書籍一覧</h2>
    <table>

        <?php if ($count == 0) { ?>
            <div class="error-msg">該当する書籍が存在しませんでした。</div>
        <?php } else { ?>
            <tr>
                <th class="id">ID</th>
                <th class="title">タイトル名</th>
                <th class="rental_status">貸出状況</th>
                <th class="price">仕入れ価格</th>
                <th class="saleday">発刊日</th>
                <th class="userdetail">操作</th>
            </tr>
            <?php $num = 1;
            foreach ($stmt as $row) { ?>
                <tr>
                    <th class="id"><?php echo $row['id'] ?></th>
                    <th class="title"><?php echo $row['name']; ?></th>
                    <th class="rental_status">
                        <?php $result = "";
                        if ($row['status'] == 0) {
                            $result = "貸出中";
                        } else if ($row['status'] == 1) {
                            $result = '未貸出';
                        } else {
                            $result = '貸出不良';
                        }
                        echo $result;
                        ?>
                    </th>
                    <th class="price"><?php echo $row['purchase_price']; ?></th>
                    <th class="saleday"><?php echo $row['publication_on']; ?></th>
                    <th><a class="userdetail" href="book_detail.php?data%5b%5d=<?php print $row['id']; ?>">詳細</a></th>
                </tr>
                <?php $num++;
            } ?>
        <?php } ?>
    </table>
</body>

</html>