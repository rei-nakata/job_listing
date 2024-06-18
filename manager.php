<?php
////////////////////////////////////////////////////////////////
// 準備
////////////////////////////////////////////////////////////////

// データベースへのログイン情報
$dsn = "mysql:host=localhost; dbname=joblisting; charset=utf8";
$user = "testuser";
$pass = "testpass";

// セッション情報
session_start();
if (isset($_SESSION["id"])) {
    echo "{$_SESSION['id']}さんでログイン中";
    echo "<a href='login.html' class='logout'>ログアウト</a>";
    echo "<br>";
    echo "<a href='login_user.html' class='logout'>ユーザー画面へ</a>";
    echo "<br>";
} else {
    header("Location: index.html");
}

////////////////////////////////////////////////////////////////
// 本処理
////////////////////////////////////////////////////////////////

// データをmanager.htmlから受け取る
$origin = [];
if (isset($_POST)) {
    $origin += $_POST;
}

// 受け取ったデータを処理する
foreach ($origin as $key => $value) {
    // 文字コード処理
    $mb_code = mb_detect_encoding($value);
    $value = mb_convert_encoding($value, "UTF-8", $mb_code);

    // XSS対策
    $value = htmlentities($value, ENT_QUOTES);

    // 改行処理
    $value = str_replace("\r\n", "<br>", $value);
    $value = str_replace("\n", "<br>", $value);
    $value = str_replace("\r", "<br>", $value);

    // 処理が終わったデータを$inputに入れなおす
    $input[$key] = $value;
}

// DBに接続する
try {
    // ファイルの読み込み
    $fh2 = fopen('manager.html', "r");
    $fs2 = filesize('manager.html');
    $top = fread($fh2, $fs2);
    fclose($fh2);

    $dbh = new PDO($dsn, $user, $pass);

    // モード管理
    if (isset($input["mode"])) {
        if ($input["mode"] === "register") {
            register();
        } else if ($input["mode"] === "delete") {
            delete();
        } else if ($input["mode"] === "update") {
            update();
        } else if ($input["mode"] === "change") {
            change();
        }
    }
    display();
} catch (PDOException $e) {
    echo "接続失敗..." . $e->getMessage();
}

////////////////////////////////////////////////////////////////
// 関数
////////////////////////////////////////////////////////////////

// 登録処理
function register()
{
    // 関数内でも変数で使えるようにする
    global $dbh;
    global $input;

    // 登録できる時だけsqlの実行をする
    if (isset($input["店名"]) && isset($input["キャッチコピー"]) && isset($input["職種"]) && isset($input["最寄り駅"]) && isset($input["時給"])) {
        // sql文を書く
        $sql = <<<sql
        insert into job (店名, キャッチコピー, 職種, 最寄り駅, 時給) values(?, ?, ?, ?, ?);
        sql;

        // 実行する
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(1, $input["店名"]);
        $stmt->bindParam(2, $input["キャッチコピー"]);
        $stmt->bindParam(3, $input["職種"]);
        $stmt->bindParam(4, $input["最寄り駅"]);
        $stmt->bindParam(5, $input["時給"]);
        $stmt->execute();
    } else {
        // error対処
        error();
    }
}

// エラー処理
function error()
{
    // 関数内でも変数で使えるようにする
    global $input;

    // 空の変数用意
    $error_message = "";

    // 入力チェック
    if ($input["店名"] == "") {
        $error_message .= "店名が未入力です<br>";
    }
    if ($input["キャッチコピー"] == "") {
        $error_message .= "キャッチコピーが未入力です<br>";
    }
    if ($input["職種"] == "") {
        $error_message .= "職種が未入力です<br>";
    }
    if ($input["最寄り駅"] == "") {
        $error_message .= "最寄り駅が未入力です<br>";
    }
    if ($input["時給"] == "") {
        $error_message .= "時給が未入力です<br>";
    }

    // errorのテンプレート読み込み
    $error = fopen("tmpl/error.tmpl", "r");
    $size = filesize("tmpl/error.tmpl");
    $data = fread($error, $size);
    fclose($error);

    // 文字置き換え
    $data = str_replace("!message!", $error_message, $data);

    echo $data;

    // 処理終了
    exit;
}

// 削除処理
function delete()
{
    // 関数内でも変数で使えるようにする
    global $dbh;
    global $input;

    // sql文を書く
    $sql = <<<sql
    update job set flag = 2 where id = ?
    sql;

    // 実行する
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(1, $input["id"]);
    $stmt->execute();
    if (!$stmt->execute()) {
        // エラーが発生した場合の処理
        print_r($stmt->errorInfo()); // エラー情報を表示
        exit(); // プログラムの実行を停止
    }
}

// 編集処理
function update()
{
    // 関数内でも変数で使えるようにする
    global $input;
    global $dbh;
    global $top;

    // 初期化する
    $place = "";

    // sql文を書く
    $sql = <<<sql
    select * from job where id = ?;
    sql;

    // 実行する
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(1, $input["id"]);
    $stmt->execute();

    // テンプレートファイルの読み込み
    $fh = fopen('tmpl/update.tmpl', "r");
    $fs = filesize('tmpl/update.tmpl');
    $update = fread($fh, $fs);
    fclose($fh);

    // 値を変数に入れなおす
    $row = $stmt->fetch();
    $id = $row["id"];
    $shop = $row["店名"];
    $catchcopy = $row["キャッチコピー"];
    $job = $row["職種"];
    $station = $row["最寄り駅"];
    $money = $row["時給"];

    // テンプレートファイルの文字置き換え
    $update = str_replace("!id!", $id, $update);
    $update = str_replace("!店名!", $shop, $update);
    $update = str_replace("!キャッチコピー!", $catchcopy, $update);
    $update = str_replace("!職種!", $job, $update);
    $update = str_replace("!最寄り駅!", $station, $update);
    $update = str_replace("!時給!", $money, $update);

    // manager.htmlに差し込む変数に格納する
    $place .= $update;

    // $topに代入
    $top = str_replace("編集するときはこちらに表示されます", $place, $top);
}

// 更新処理
function change()
{
    // 関数内でも変数で使えるようにする
    global $dbh;
    global $input;

    // sql文を書く
    $sql = <<<sql
        update job set 店名 = ?, キャッチコピー = ?, 職種 = ?, 最寄り駅 = ?, 時給 = ? where id = ?;
    sql;

    // 実行する
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(1, $input["店名"]);
    $stmt->bindParam(2, $input["キャッチコピー"]);
    $stmt->bindParam(3, $input["職種"]);
    $stmt->bindParam(4, $input["最寄り駅"]);
    $stmt->bindParam(5, $input["時給"]);
    $stmt->bindParam(6, $input["id"]);
    $stmt->execute();
}

// 現在のタスク一覧表示処理
function display()
{
    // 関数内でも変数を使えるようにする
    global $dbh;
    global $block;
    global $top;

    // sql文を書く
    $sql = <<<sql
    select * from job where flag in (0,1,3);
    sql;

    // 実行する
    $stmt = $dbh->prepare($sql);
    $stmt->execute();

    // テンプレートファイルの読み込み
    $fh = fopen('tmpl/insert.tmpl', "r");
    $fs = filesize('tmpl/insert.tmpl');
    $insert_tmpl = fread($fh, $fs);
    fclose($fh);

    // 繰り返してすべての行を取ってくる
    while ($row = $stmt->fetch()) {
        // 差し込み用テンプレートを初期化する
        $insert = $insert_tmpl;

        // 値を変数に入れなおす
        $id = $row["id"];
        $shop = $row["店名"];
        $catchcopy = $row["キャッチコピー"];
        $job = $row["職種"];
        $station = $row["最寄り駅"];
        $money = $row["時給"];

        // テンプレートファイルの文字置き換え
        $insert = str_replace("!id!", $id, $insert);
        $insert = str_replace("!店名!", $shop, $insert);
        $insert = str_replace("!キャッチコピー!", $catchcopy, $insert);
        $insert = str_replace("!職種!", $job, $insert);
        $insert = str_replace("!最寄り駅!", $station, $insert);
        $insert = str_replace("!時給!", $money, $insert);

        // manager.htmlに差し込む変数に格納する
        $block .= $insert;
    }

    // $topに代入
    $top = str_replace("!block!", $block, $top);
    echo $top;
}