<?php
sessioN_start();

$id = $_SESSION['book_edit_id'];
$error_msg = array();

// DB用の設定
$db['user'] = "pruser";
$db['pass'] = "pruser123";
$db['host'] = "mysql:host=localhost;dbname=prdb";
$pdo = new PDO($db['host'], $db['user'], $db['pass'], array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));

// 編集の選択肢などに使う値を取り出す
$sql = "SELECT books.id, titles.name, books.status, books.purchase_price, books.point FROM books LEFT JOIN titles ON titles.id = books.title_id GROUP BY titles.id ORDER BY books.id ASC";
$stmt = $pdo->query($sql);
$allresult = $stmt->fetchAll();

// 該当書籍の値を初期値として取り出す
$standardsql = "SELECT books.id, titles.name, books.status, books.purchase_price, books.point FROM books LEFT JOIN titles ON titles.id = books.title_id WHERE books.id = {$id}";
$standardstmt = $pdo->query($standardsql);
$standardresult = $standardstmt->fetch();

// 貸出状況（数値）によって場合分けして、文字列で表示
$status = array('貸出中', '未貸出', '貸出不良');
switch ($standardresult['status']) {
    case 0:
        $standardstatus = '貸出中';
        $afterstatus = array_splice($status, 0, 1);
        break;
    case 1:
        $standardstatus = '未貸出';
        $afterstatus = array_splice($status, 1, 1);
        break;
    case 2:
        $standardstatus = '貸出不良';
        $afterstatus = array_splice($status, 2, 1);
        break;
}

// 戻るボタン押下時、書籍詳細画面へ遷移
if (isset($_POST['back'])) {
    header('Location:book_detail.php?data%5b%5d=' . $id);
    exit;
}

// 確定ボタン押下時
if (isset($_POST['ok'])) {
    $errorCount = 0;

    // バリデーション
    if (!isset($_POST['title'])) {
        $error_msg[] = "タイトルを選択してください";
        $errorCount++;
    }
    if (!isset($_POST['status'])) {
        $error_msg[] = "貸出状況を選択してください";
        $errorCount++;
    }

    if (empty($_POST['purchase_price'])) {
        $error_msg[] = "仕入れ価格を入力してください";
        $errorCount++;
    }

    if (!empty($_POST['purchase_price']) && !preg_match('/^[0-9]+$/', $_POST['purchase_price'])) {
        $error_msg[] = "仕入れ価格を半角数字で入力してください";
        $errorCount++;
    }

    if (empty($_POST['point'])) {
        $error_msg[] = "書籍ポイントを入力してください";
        $errorCount++;
    }

    if (!empty($_POST['point']) && !preg_match('/^[0-9]+$/', $_POST['point'])) {
        $error_msg[] = "書籍ポイントを半角数字で入力してください";
        $errorCount++;
    }

    // バリデーション通過時
    if ($errorCount == 0) {
        $statusNumber = 0;

        // 入力された貸出状況（文字列）を数値に変換
        switch ($_POST['status']) {
            case '未貸出':
                $statusNumber = 1;
                break;
            case '貸出不良':
                $statusNumber = 2;
                break;

        }


        // 入力されたタイトル名からDBを通してIDを取得
        $titlesql = "SELECT id FROM titles WHERE name = \"{$_POST['title']}\"";
        $titlestmt = $pdo->query($titlesql);
        $title_id = $titlestmt->fetch(PDO::FETCH_ASSOC);

        // 入力値通りにアップデート
        $editsql = "UPDATE books INNER JOIN titles ON books.title_id = titles.id SET books.title_id = :title_id, books.status = :status, books.purchase_price = :price, books.point = :point WHERE books.id = :id";
        $editstmt = $pdo->prepare($editsql);
        $editstmt->bindValue(':title_id', $title_id['id'], PDO::PARAM_STR);
        $editstmt->bindValue(':status', $statusNumber, PDO::PARAM_INT);
        $editstmt->bindValue(':price', $_POST['purchase_price'], PDO::PARAM_INT);
        $editstmt->bindValue(':point', $_POST['point'], PDO::PARAM_INT);
        $editstmt->bindValue(':id', $id, PDO::PARAM_INT);
        $editstmt->execute();
        $count = $editstmt->rowCount();

        // 成功すれば書籍一覧へ遷移
        if ($count != 0) {
            header("Location: book_list.php");
            exit;

            // 失敗時、エラー表示
        } else {
            $error_msg[] = "更新処理に失敗しました";
        }
    }
}

?>


<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="book_edit.css">
    <title>Document</title>
</head>

<body>
    <?php include ('header.php'); ?>
    <form action="" method="post" id="edit_form">
        <h2>書籍編集</h2>
        <div class="id">
            <label for="id">ID：</label>
            <?php print $id; ?>
        </div>
        <div class="title">
            <label for="title">タイトル：</label>
            <select name="title" id="title">
                <option value="<?php print $standardresult['name']; ?>" selected><?php print $standardresult['name']; ?>
                </option>
                <?php for ($j = 0; $j < count($allresult); $j++) { ?>
                    <option value="<?php print $allresult[$j]['name']; ?>"><?php echo $allresult[$j]['name']; ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="status">
            <label for="status">貸出状況：</label>
            <select name="status" id="status">
                <option value=<?php print $standardstatus; ?> selected><?php print $standardstatus; ?></option>
                <option value=<?php print $status[0]; ?>><?php print $status[0]; ?></option>
                <option value=<?php print $status[1]; ?>><?php print $status[1]; ?></option>
            </select>
        </div>
        <div class="purchase_price">
            <label for="purchase_price">仕入れ価格：</label>
            <input type="text" name="purchase_price" value=<?php echo $standardresult['purchase_price']; ?>>
        </div>
        <div class="point">
            <label for="point">書籍ポイント：</label>
            <input type="text" name="point" value=<?php echo $standardresult['point']; ?>>
        </div>
        <div class="error-msg"><?php foreach ($error_msg as $error) {
            echo $error; ?><br><?php
        } ?>
        </div>
        <input type="submit" name="back" value="戻る">
        <input type="submit" name="ok" value="確定">
    </form>
</body>

</html>