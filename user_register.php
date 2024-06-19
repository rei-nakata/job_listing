<?php
// アカウント登録用ですが、実装が間に合わなかったため、利用しません。

// データを受け取る
$origin = [];
if (isset($_POST)) {
    $origin += $_POST;
}

// 受け取ったデータを処理する
$input = [];
foreach ($origin as $key => $value) {
    // 文字コード
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


// ファイルの読み込み
$fh2 = fopen('user.html', "r");
$fs2 = filesize('user.html');
$top = fread($fh2, $fs2);
fclose($fh2);

if (isset($input["mode"])) {
    if ($input["mode"] == "user_regisiter") {
        user_register();
    }
}
function user_register()
{
    global $input;
    global $top;
    $block = "";

    // テンプレートファイルの読み込み
    $fh = fopen('tmpl/user_register.tmpl', "r");
    $fs = filesize('tmpl/user_register.tmpl');
    $insert = fread($fh, $fs);
    fclose($fh);

    // 値を変数に入れなおす
    $name = $input["name"];
    $email = $input["email"];

    // テンプレートファイルの文字置き換え
    $insert = str_replace("!name!", $name, $insert);
    $insert = str_replace("!email!", $email, $insert);
    $insert = str_replace("!id!", $input["userid"], $insert);
    $insert = str_replace("!pass!", $input["pass"], $insert);

    $block .= $insert;

    // テンプレートファイルの読み込み
    // $fh2 = fopen('user_conf.html', "r");
    // $fs2 = filesize('user_conf.html');
    // $top = fread($fh2, $fs2);
    // fclose($fh2);

    $top = str_replace("!block!", $block, $top);
    echo $top;
}