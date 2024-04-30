<?php
// session_start();

// セッションが存在しなければログイン画面へ戻す。（未ログイン状態でのURL直入力を阻止）
if (!isset($_SESSION) || !isset($_SESSION['id']) || !isset($_SESSION['name'])) {
    header("Location:rental_loginpage.php");
    exit;
} else {

    // セッションが存在すれば、それぞれ変数に保持
    $user_id = $_SESSION['id'];
    $user_name = $_SESSION['name'];
}

// ログアウトボタンが押されたときの処理
if ($_SERVER['REQUEST_METHOD'] == "GET" && isset($_GET['logou'])) {
    // セッション破棄
    $_SESSION = array();
    session_destroy();

    // セッションが破棄されたかを確認、確認できればログイン画面へ、確認されなければエラー表示
    if (empty($_SESSION)) {
        header("Location:rental_loginpage.php");
        exit;
    } else {
        $alert = "<script type='text/javascript'>alert('ログアウトに失敗しました');</script>";
        echo $alert;
    }

}

?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="rental_header.css">
    <title>レンタル書籍ヘッダー</title>
</head>

<body>
    <header>
        <div class="main-header">
            <div class="login-header">
                <p>ログイン：<?php echo $user_name; ?>（会員ID:<?PHP echo $user_id; ?>） </p>
                <form action="" name="logout" method="GET">
                    <input type="hidden" name="logou" value="hoge">
                    <a href="#" onclick="document.logout.submit();">ログアウト</a>
                    <!-- <input type="submit" name="logout" id="logout" value="ログアウト"> -->
                </form>
            </div>
            <div class="sub-header">
                <a href="rental_bookslog.php">履歴</a>
                <a href="rental_cart.php">カート</a>
                <a href="rental_booklist.php">書籍</a>
            </div>
        </div>
    </header>
</body>

</html>