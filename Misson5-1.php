<?php
// DB接続設定
$servername = "*****";
$username = "*****";
$password = "*****";
$dbname = "*****";
// 接続を作成する
$mysqli = new mysqli($servername, $username, $password, $dbname);
// 接続を確認する
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
// テーブルを作成する=
$sql = "CREATE TABLE IF NOT EXISTS threadtable (
    id INTEGER(5) NOT NULL,
    name varchar(64) NOT NULL,
    password varchar(64) NOT NULL,
    comment varchar(1000) NOT NULL,
    time TIMESTAMP NOT NULL
    )";
if ($mysqli->query($sql) === TRUE) {
    echo "ようこそ簡易掲示板へ！";
    echo "<br>";
} else {
    echo "エラーが発生しました： " . $mysqli->error;
}
#編集用にパラメーターを初期化する
$edit_flag = 0;
$edit_id = 0;
$edit_val = "";
$edit_name = "";
$edit_pass = "";

// 要素の数を取得しています
$sql = "SELECT COUNT(*) as c from threadtable"; // すべてのレコード数を取得
$result = $mysqli->query($sql); //sql実行
$n = $result->fetch_assoc(); //結果の行を連想配列で取得する
$count = $n["c"];

#投稿
if ($_SERVER["REQUEST_METHOD"] == "POST") { //もし何かが投稿された時
    #コメントの投稿
    if (isset($_POST["name"])) {
        #新しいコメントの投稿
        if ($_POST["edit_flag"] === "0") {
            //filter_inputとは、目的の値が正しいものか調査するメソッド
            $name = filter_input(INPUT_POST, 'name');
            $password = filter_input(INPUT_POST, 'password');
            $comment = filter_input(INPUT_POST, 'comment');
            $time = date('Y-m-d H:i:s');
            $id = $count + 1;
            $sql = "INSERT INTO threadtable (id, name, password, comment, time) VALUES ('$id', '$name', '$password', '$comment', '$time')";
            if ($mysqli->query($sql) === TRUE) {
                echo "コメントを投稿しました";
                echo "<br>";
            } else {
                echo "エラーが発生しました：" . $sql . "<br>" . $mysqli->error;
                echo "<br>";
            }
        }
    }
}

#コメントを削除する
// もしdel_numがセットされた時
if (isset($_POST["del_num"])) {
    // del_numを$numberにいれる
    $number = filter_input(INPUT_POST, 'del_num');
    if ($number <= $count && $number > 0) {
        $sql = "SELECT password as p FROM threadtable WHERE id = $number";
        $result = $mysqli->query($sql);
        $n = $result->fetch_assoc();
        $pass_idx = $n["p"];
        // strcmp()は文字列を比較するときに使う
        if (strcmp($pass_idx, $_POST["password_del"]) == 0) {
            $sql = "DELETE FROM threadtable WHERE id = $number";
            if ($mysqli->query($sql) === TRUE) {
                echo "<br>";
                echo "コメントを削除しました。";
                echo "<br>";
            } else {
                echo "エラーが発生しました：" . $sql . "<br>" . $mysqli->error;
                echo "<br>";
            }
            while ($number < $count) {
                $idx = $number + 1;
                $sql = "UPDATE threadtable SET id = $number WHERE id = $idx";
                $result = $mysqli->query($sql);
                $number++;
            }
        } else {
            echo "パスワードが間違っています";
        }
    }
}
#編集のための権利の検証
if (isset($_POST["edit_num"])) {
    $number = filter_input(INPUT_POST, 'edit_num');
    if ($number <= $count && $number > 0) {
        $sql = "SELECT password as p FROM threadtable WHERE id = $number";
        $result = $mysqli->query($sql);
        $n = $result->fetch_assoc();
        $pass_idx = $n["p"];
        // strcmp()は文字列を比較するときに使う
        if (strcmp($pass_idx, $_POST["password_edit"]) == 0) {
            $sql = "SELECT id, name, comment, password FROM threadtable WHERE id=$number";
            $result = $mysqli->query($sql);
            $n = $result->fetch_assoc();
            $edit_val = $n["comment"];
            $edit_name = $n["name"];
            $edit_pass = $n["password"];
            $edit_flag = 1;
            $edit_id = $n["id"];
            echo "<br>";
            echo "コメントを編集してください";
            echo "<br>";
        } else {
            echo "パスワードが間違っています";
        }
    }
}

#コメントの編集
if ($_POST["edit_flag"] === "1") {
    $comment = filter_input(INPUT_POST, 'comment');
    $idx = filter_input(INPUT_POST, 'edit_id');
    $sql = "UPDATE threadtable SET comment = '$comment' WHERE id = $idx";
    if ($mysqli->query($sql) === TRUE) {
        echo "コメントが編集されました";
        echo "<br>";
    } else {
        echo "エラーが発生しました：" . $sql . "<br>" . $mysqli->error;
        echo "<br>";
    }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Mission_5-1 デバッグ・レビュー</title>
</head>

<body>
    <h2>Misson_5-1 デバッグ・レビュー</h2>
    <p>投稿方法：　お名前、コメント、パスワードを入力してください</p>
    <p>削除方法：　削除する番号とパスワードを入力してください</p>
    <p>編集方法：　編集する番号とパスワードを入力し、フォームに編集する名前とコメントが反映されるので編集してから、投稿ボタンを押してください（コメントのみ編集可能です）</p>
    <form method="POST">
        <br>
        名前
        <br>
        <input name="name" type="text" value="<?= $edit_name ?>" required>
        <br>
        コメント
        <br>
        <input name="comment" type="text" value="<?= $edit_val ?>" required>
        <br>
        パスワード
        <br>
        <input name="password" type="password" value="<?= $edit_pass ?>" required>
        <br>
        <input type="hidden" value="<?= $edit_flag ?>" name="edit_flag">
        <input type="hidden" value="<?= $edit_id; ?>" name="edit_id">
        <input type="submit" value="投稿">
    </form>
    <form method="POST" onsubmit="return confirm_del()">
        <p>削除番号指定用フォーム</p>
        番号
        <br>
        <input name="del_num" type="number" required>
        <br>
        パスワード
        <br>
        <input name="password_del" type="password" required>
        <br>
        <input type="submit" value="削除">
    </form>
    <form method="POST">
        <p>編集番号指定用フォーム</p>
        番号
        <br>
        <input name="edit_num" type="number" required>
        <br>
        パスワード
        <br>
        <input name="password_edit" type="password" required>
        <br>
        <input type="submit" value="編集">
    </form>
    <hr>
    <p>[投稿一覧]</p>
    <?php
    $sql = "SELECT id, name, comment, time, password FROM threadtable";
    $result = $mysqli->query($sql);

    if ($result->num_rows > 0) {
        // 各行の出力データ
        while ($row = $result->fetch_assoc()) {
            echo $row["id"] . " " . $row["name"] . " " . $row["comment"] . " " . $row["time"];
            echo "<br>";
        }
    } else {
        echo "まだコメントが投稿されていません。";
    }
    $mysqli->close();
    ?>
    <script>
        function confirm_del() {
            return confirm("コメントを削除します。本当によろしいですか？")
        }
    </script>
</body>

</html>