<?php
// session_start();

/*
ログアウトボタン押下時
セッション破棄、ログインページへ遷移
*/
if (isset($_GET["log_out"])) {
    $_SESSION = array();
    session_destroy();
    header("Location:login_page.php");
    exit;
}

// 書籍一覧ボタン押下時
if (isset($_GET["redirect_book_list"])) {
    header("Location:book_list.php");
    exit;
}

// 書籍新規登録ボタン押下時
if (isset($_GET["redirect_add_book"])) {
    header("Location:add_book.php");
    exit;
}

// セッション切れの場合、ログインページへ遷移
if ($_SESSION == null) {
    unset($_SESSION);
    header("Location:login_page.php");
    exit;
}


?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="header.css">
    <title>header</title>
</head>

<body>
    <header>
        <div class=header-menu>
            <p>名前：<?php echo $_SESSION['name'] ?></p>
            <div class="btn">
                <form action="" method="get">
                    <button type="submit" name="redirect_book_list" class="header-btn">書籍一覧</button>
                    <button type="submit" name="redirect_add_book" class="header-btn">書籍新規登録</button>
                    <button type="submit" name="log_out" class="header-btn">ログアウト</button>
                </form>
            </div>
        </div>
    </header>
</body>

</html>