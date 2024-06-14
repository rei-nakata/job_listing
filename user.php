<?php
////////////////////////////////////////////////////////////////
// 準備
////////////////////////////////////////////////////////////////

// データベースへのログイン情報
$dsn = "mysql:host=localhost; dbname=joblisting; charset=utf8";
$user = "testuser";
$pass = "testpass";

echo '<link rel="stylesheet" type="text/css" href="style.css">';

$name= "テストユーザー";
$email = "test@gmail.co.jp";
////////////////////////////////////////////////////////////////
// 本処理
////////////////////////////////////////////////////////////////

// データをuser.htmlから受け取る
$input = [];
if (isset($_POST)) {
    $input += $_POST;
}

// DBに接続する
try {
    // ファイルの読み込み
    $fh2 = fopen('user.html', "r");
    $fs2 = filesize('user.html');
    $top = fread($fh2, $fs2);
    fclose($fh2);

    $dbh = new PDO($dsn, $user, $pass);
    if (isset($input["mode"])) {
        if ($input["mode"] == "search") {
            search();
        } else if ($input["mode"] == "narabikae") {
            narabikae();
        } else if ($input["mode"] == "area"){ 
            area();
        }else if ($input["mode"] == "favorite") {
            favorite_register();
        } else if ($input["mode"] == "favodele") {
            favorite_delete();
        }else if ($input["mode"] == "apply") {
            apply_register();
        }
    }
    favorite_display();
    apply_display();
    display();

} catch (PDOException $e) {
    echo "接続失敗..." . $e->getMessage();
}

////////////////////////////////////////////////////////////////
// 関数
////////////////////////////////////////////////////////////////

// お気に入り登録
function favorite_register()
{
    // 関数内でも変数で使えるようにする
    global $dbh;
    global $input;

    // sql文を書く
    $sql = <<<sql
    update job set flag = 1 where id = ?;
    sql;

    // 実行する
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(1, $input["id"]);
    $stmt->execute();
}

// お気に入り表示
function favorite_display()
{
    // 関数内でも変数を使えるようにする
    global $dbh;
    global $top;
    $block = "";

    // sql文を書く
    $sql = <<<sql
    select * from job where flag = 1;
    sql;

    // 実行する
    $stmt = $dbh->prepare($sql);
    $stmt->execute();

    // テンプレートファイルの読み込み
    $fh = fopen('tmpl/favorite.tmpl', "r");
    $fs = filesize('tmpl/favorite.tmpl');
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

        // index.htmlに差し込む変数に格納する
        $block .= $insert;
    }

    if ($block == ""){
        $block = "現在お気に入りはありません。";
    }
    $top = str_replace("現在お気に入りはありません。", $block, $top);
    // index.htmlの置き換え
}

// お気に入り削除
function favorite_delete()
{
    // 関数内でも変数で使えるようにする
    global $dbh;
    global $input;

    // sql文を書く
    $sql = <<<sql
    update job set flag = 0 where id = ?
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

// 申し込み登録
function apply_register()
{
    // 関数内でも変数で使えるようにする
    global $dbh;
    global $input;
    global $name;
    global $email;
    $block = "";

    // sql文を書く
    $sql = <<<sql
    update job set flag = 3 where id = ?;
    sql;

    // 実行する
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(1, $input["id"]);
    $stmt->execute();

    // テンプレートファイルの読み込み
    $fh = fopen('tmpl/conf.tmpl', "r");
    $fs = filesize('tmpl/conf.tmpl');
    $insert_tmpl = fread($fh, $fs);
    fclose($fh);

    // 繰り返してすべての行を取ってくる
    $row = $stmt->fetch()
    // 差し込み用テンプレートを初期化する
    $insert = $insert_tmpl;

    // 値を変数に入れなおす
    $shop = $row["店名"];

    // テンプレートファイルの文字置き換え
    $insert = str_replace("!店名!", $shop, $insert);

    // index.htmlに差し込む変数に格納する
    $block .= $insert;
    echo $block;

}

// 申し込み表示
function apply_display()
{
    // 関数内でも変数を使えるようにする
    global $dbh;
    global $top;
    $block = "";

    // sql文を書く
    $sql = <<<sql
    select * from job where flag = 3;
    sql;

    // 実行する
    $stmt = $dbh->prepare($sql);
    $stmt->execute();

    // テンプレートファイルの読み込み
    $fh = fopen('tmpl/apply.tmpl', "r");
    $fs = filesize('tmpl/apply.tmpl');
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

        // index.htmlに差し込む変数に格納する
        $block .= $insert;
    }

    if ($block == ""){
        $block = "現在申し込み履歴はありません。";
    }
    $top = str_replace("現在申し込み履歴はありません。", $block, $top);
    // index.htmlの置き換え
}

//並び替え検索
function narabikae()
{
    // 関数内でも変数で使えるようにする
    global $dbh;
    global $input;
    global $top;
    $block = "";

    if (isset($input["narabikae"])) {
        if ($input["narabikae"] == "新着順") {
            $sql = <<<sql
            select * from job where flag in (0,1) order by id desc;
            sql;
        } else if ($input["narabikae"] == "時給順") {
            $sql = <<<sql
            select * from job where flag in (0,1) order by 時給 desc;
            sql;
        } else if ($input["narabikae"] == "---") {
            $sql = <<<sql
            select * from job where flag in (0,1);
            sql;
        }
        // 実行する
        $stmt = $dbh->prepare($sql);
        $stmt->execute();

        // テンプレートファイルの読み込み
        $fh = fopen('tmpl/user.tmpl', "r");
        $fs = filesize('tmpl/user.tmpl');
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

            // index.htmlに差し込む変数に格納する
            $block .= $insert;
        }

        // index.htmlの置き換え
        $top = str_replace("!block!", $block, $top);
    }
}

// 検索
function search()
{
    // 関数内でも変数で使えるようにする
    global $dbh;
    global $input;
    global $top;
    $block = "";

    if(isset($input["search"])){
        if ($input["search"] == "全て") {
            // sql文を書く
            $sql = <<<sql
                select * from job where flag in (0,1);
                sql;
    
            // 実行する
            $stmt = $dbh->prepare($sql);
            $stmt->execute();
        } else {
    
            // sql文を書く
            $sql = <<<sql
                select * from job where 職種 = ? and flag in (0,1);
                sql;
    
            // 実行する
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(1, $input["search"]);
            $stmt->execute();
        }
        // テンプレートファイルの読み込み
        $fh = fopen('tmpl/user.tmpl', "r");
        $fs = filesize('tmpl/user.tmpl');
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
    
            // index.htmlに差し込む変数に格納する
            $block .= $insert;
        }
    
        // index.htmlの置き換え
        $top = str_replace("!block!", $block, $top);
    }

}

// エリア検索
function area()
{
    // 関数内でも変数で使えるようにする
    global $dbh;
    global $input;
    global $top;
    $block = "";

    if(isset($input["area"])){
        if ($input["area"] == "全て") {
            // sql文を書く
            $sql = <<<sql
                select * from job where flag in (0,1);
                sql;
    
            // 実行する
            $stmt = $dbh->prepare($sql);
            $stmt->execute();
        } else if ($input["area"] == "23区外"){
    
            // sql文を書く
            $sql = <<<sql
                select * from job where 最寄り駅 in ('八王子みなみ野', '立川', '町田', 'よみうりランド', '多摩センター') and flag in (0,1);
                sql;
    
            // 実行する
            $stmt = $dbh->prepare($sql);
            $stmt->execute();
        }
        else{
            
            // sql文を書く
            $sql = <<<sql
                select * from job where 最寄り駅 not in ('八王子みなみ野', '立川', '町田', 'よみうりランド', '多摩センター') and flag in (0,1);
                sql;
    
            // 実行する
            $stmt = $dbh->prepare($sql);
            $stmt->execute();
        }
        // テンプレートファイルの読み込み
        $fh = fopen('tmpl/user.tmpl', "r");
        $fs = filesize('tmpl/user.tmpl');
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
    
            // index.htmlに差し込む変数に格納する
            $block .= $insert;
        }
    
        // index.htmlの置き換え
        $top = str_replace("!block!", $block, $top);
    }

}

// 現在のタスク一覧表示処理
function display()
{
    // 関数内でも変数を使えるようにする
    global $dbh;
    global $top;
    $block = "";

    // sql文を書く
    $sql = <<<sql
    select * from job where flag in (0,1);
    sql;

    // 実行する
    $stmt = $dbh->prepare($sql);
    $stmt->execute();

    // テンプレートファイルの読み込み
    $fh = fopen('tmpl/user.tmpl', "r");
    $fs = filesize('tmpl/user.tmpl');
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

        // index.htmlに差し込む変数に格納する
        $block .= $insert;
    }

    // index.htmlの置き換え
    $top = str_replace("!block!", $block, $top);
    echo $top;
}
