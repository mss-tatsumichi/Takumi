<?php
session_start();

// DB用の設定
$db['user'] = "pruser";
$db['pass'] = "pruser123";
$db['host'] = "mysql:host=localhost;dbname=prdb";
$pdo = new PDO($db['host'], $db['user'], $db['pass'], array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));

// 選択した書籍のセッションIDがない場合一覧ページのリンクからIDを受け取る
if (!isset($_SESSION['select_id'])) {
    $id = $_GET["data"][0];
} else {

    // セッションIDとリンクのIDが違う場合、リンクのIDで上書きする
    if ($_SESSION['select_id'] != $_GET['data'][0]) {
        $id = $_GET["data"][0];
    } else {
        $id = $_SESSION['select_id'];
    }
}

// 選択されている書籍詳細のSQL
$sql = "SELECT books.id, titles.name, books.status, books.purchase_price, titles.publication_on, books.point FROM books LEFT JOIN titles ON books.title_id = titles.id WHERE books.id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch();

// 存在しない書籍の場合、書籍一覧へ遷移
if ($row == null) {
    header('Location:book_list.php');
    exit;
}

// 貸出状況によって表示を場合分け
$status = "";
switch ($row['status']) {
    case 0:
        $status = '貸出中';
        break;
    case 1:
        $status = '未貸出';
        break;
    case 2:
        $status = '貸出不良';
        break;
}


$error_msg = "";

// 削除ボタン押下時
if (isset($_POST['delete'])) {
    if (!isset($id)) {
        $error_msg = "削除処理に失敗しました";
    }

    // 外部キー制約を一時取り外し、該当書籍を削除後、再び外部キー制約を張りなおす
    $foreignsql = "SET foreign_key_checks = 0;";
    $foreignstmt = $pdo->query($foreignsql);
    $sql = "DELETE FROM books WHERE id = {$id}";
    $stmt = $pdo->query($sql);
    $foreignonsql = "SET foreign_key_checks = 1;";
    $pdo->query($foreignonsql);
    unset($_SESSION['select_id']);
    header("Location:book_list.php");
    exit;
}

// 変種ボタン押下時、セッションに該当書籍のIDを保存して編集画面へ遷移
if (isset($_POST['edit'])) {
    $_SESSION['book_edit_id'] = $id;
    header('Location:book_edit.php');
    exit;
}

$_SESSION['select_id'] = $id;

?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="book_detail.css">
    <title>Document</title>

</head>

<body>
    <?php include ('header.php'); ?>
    <h2>書籍一覧</h2>
    <table>
        <tr>
            <th class="detail_name">ID</th>
            <th class="detail"><?php echo $row['id']; ?></th>
        </tr>
        <tr>
            <th class="detail_name">タイトル名</th>
            <th class="detail"><?php echo $row['name']; ?></th>
        </tr>
        <tr>
            <th class="detail_name">貸出状況</th>
            <th class="detail"><?php echo $status; ?></th>
        </tr>
        <tr>
            <th class="detail_name">仕入れ価格</th>
            <th class="detail"><?php echo $row['purchase_price']; ?></th>
        </tr>
        <tr>
            <th class="detail_name">発刊日</th>
            <th class="detail"><?php echo $row['publication_on']; ?></th>
        </tr>
        <tr>
            <th class="detail_name">書籍ポイント</th>
            <th class="detail"><?php echo $row['point']; ?></th>
        </tr>
    </table>
    <div class="error-msg"><?php print $error_msg ?></div>
    <div class="detail-btn">
        <form action="" method="post" id="edit-form">
            <input type="submit" class="detail-edit" name="edit" value="編集">
        </form>
        <form action="" method="post" id="delete-form" onsubmit="return delete_check()">
            <input type="submit" class="detail-delete" name="delete" value="削除">
        </form>

    </div>

    <script src="detail.js"></script>
</body>

</html>