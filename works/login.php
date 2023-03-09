<?php

/* ini_set('display_errors', 1);
ini_set('output_buffering', 1); */

// require_once(dirname(__FILE__)).'./function.php';

session_start();

if (isset($_SESSION['USER'])){
  // ログイン済みの場合はホーム画面へ遷移
  header('Location: ./index.php');
  exit();
}

// ユーザー側ログイン画面
if ($_SERVER['REQUEST_METHOD'] == 'POST'){
  //POST処理

  //1．入力値を取得
  $user_no = $_POST['user_no'];
  $name = $_POST['name'];
  $password = $_POST['password'];

  //2．バリデーションチェック
  $err = array();
  if (!$user_no){
    $err['user_no'] = 'ユーザー番号を正しく入力してください';
  }elseif (!preg_match('/[0-9]+$/', $user_no)){
    $err['user_no'] = '名前を入力してください';
  }elseif (mb_strlen($user_no, 'utf-8') > 20){
    $err['user_no'] = 'ユーザー番号が長すぎます';
  }
  if (!$name){
    $err['name'] = '名前を入力してください';
  }

  if (!$password){
    $err['password'] = 'パスワードを入力してください';
  }

  if(empty($err)){
    //3．データベースに照合
    $DB_HOST = 'mysql:dbname=works;host=localhost;port=8889;charset=utf8';
    $DB_USER = 'root';
    $DB_PASSWORD = 'root';
    try {
        $pdo = new PDO($DB_HOST,$DB_USER,$DB_PASSWORD);
        $pdo->query('SET NAMES utf8;');
        $pdo ->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $sql = "SELECT id,user_no,name FROM user WHERE user_no =:user_no AND name =:name AND password =:password LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_no', $user_no, PDO::PARAM_STR);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':password', $password, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch();


    } catch (PDOException $e) {
        echo 'ERROR: Could not connect.'.$e->getMessage()."\n";
        exit();
    }

    if ($user){
      //4．ログイン処理（セッションの保存）
      $_SESSION['USER'] = $user;

      //5．HOME画面へ遷移
      header('Location: ./index.php');
      exit();
    
    }else{
      $err['password'] = '認証に失敗しました';
    }
  }


}else{
  $user_no = "";
  $user_name = "";
  $password = "";
}


?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Form</title>
  <link rel="stylesheet" href="./css/style.css">
</head>
<body style="overflow: hidden;">
  <h1 class="title">達成度登録システム</h1>
  <hr class="title_hr">
  <form class="login_form" method="POST">
    <h1 class="form_title">Login</h1>
    <div class="form_input">
      <input type="text" class="user <?php if (isset($err['user_no'])) echo 'is-invalid'; ?>"   name="user_no" value="<?= $user_no ?>" placeholder="ユーザー番号">
      <div class="invalid-feedback" style="text-align: center; color: red; font-size: 18px;"><?= $err['user_no'] ?></div>
      <input type="text" class="user <?php if (isset($err['name'])) echo 'is-invalid'; ?>" name="name" value="<?= $name ?>" placeholder="名前">
      <div class="invalid-feedback" style="text-align: center; color: red; font-size: 18px;"><?= $err['name'] ?></div>
      <input type="text" class="password" <?php if (isset($err['password'])) echo "is-invalid"; ?>  name="password" placeholder="パスワード">
      <div class="invalid-feedback" style="text-align: center; color: red; font-size: 18px;"><?= $err['password'] ?></div>
      <div class="form_btn">
        <button type="submit" class="login_btn">ログイン</button>
        <button type="button" class="login_btn" onclick="newLogin()" style="background-color: rgb(255, 135, 135); margin-left: 50px;">新規登録</button>
      </div>
    </div>
  </form>

  <script>
    function newLogin(){
      location.href = './newLogin.php';
    }
  </script>
</body>
</html>
