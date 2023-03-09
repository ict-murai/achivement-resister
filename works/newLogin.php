<?php
/* ini_set('display_errors', 1);
ini_set('output_buffering', 1); */


session_start();

/* if (isset($_SESSION['USER'])){
  // ログイン済みの場合はホーム画面へ遷移
  header('Location: ./index.php');
  exit();
} */

// ユーザー側ログイン画面
if ($_SERVER['REQUEST_METHOD'] == 'POST'){
  //POST処理

  //1．入力値を取得
  $user_no = $_POST['user_no'];
  $user_name = $_POST['user_name'];
  $password = $_POST['password'];
  $title = $_POST['title'];

  //2．バリデーションチェック
  $err = array();
  if (!$user_no){
    $err['user_no'] = 'ユーザー番号を入力してください';
  }elseif (!preg_match('/[0-9]+$/', $user_no)){
    $err['user_no'] = 'ユーザー番号を正しく入力してください';
  }elseif (mb_strlen($user_no, 'utf-8') > 20){
    $err['user_no'] = 'ユーザー番号が長すぎます';
  }

  if (!$user_name){
    $err['user_name'] = '名前を入力してください';
  }

  if (!$password){
    $err['password'] = 'パスワードを入力してください';
  }

  if (!$title){
    $err['title'] = 'タイトルを入力してください';
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

        $stmt = $pdo->prepare('SELECT * FROM user WHERE user_no = :user_no limit 1');
        $stmt->execute();
        $result = $stmt->fetch();

        if($result > 0){

            $err['user_no'] = 'このユーザー番号は既に存在します';

        }else{
            $sql = "INSERT INTO user(user_no,name,password) VALUE (:user_no,:user_name,:password)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':user_no', $user_no, PDO::PARAM_STR);
            $stmt->bindValue(':user_name', $user_name, PDO::PARAM_STR);
            $stmt->bindValue(':password', $password, PDO::PARAM_STR);
            $stmt->execute();

            $_SESSION['TITLE'] = $title;

            header('Location: ./login.php');
            exit();
        }


    } catch (PDOException $e) {
        echo 'ERROR: Could not connect.'.$e->getMessage()."\n";
        exit();
    }

    /* 重複チェック */
    $stmt = $pdo->prepare('SELECT * FROM users WHERE user_no=?');
    $stmt->bindValue(':user_no', $user_no, PDO::PARAM_STR);
    $stmt->execute();
    if (count($stmt->fetchAll())) {
      $message = 'このユーザー番号はすでに使われています';
      echo $message;
    }
  }


}else{
  $user_no = "";
  $user_name = "";
  $password = "";
  $title = "";
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
<body style="overflow: hidden; background-color: rgb(255, 171, 135);">
  <h1 class="title">達成度登録システム</h1>
  <hr class="title_hr">
  <form class="login_form" method="POST" style="height: 600px;">
    <h1 class="form_title">新規ユーザー登録</h1>
    <div class="form_input">
      <input type="text" class="user" <?php if (isset($err['user_no'])) echo 'is-invalid'; ?>  name="user_no" value="<?= $user_no ?>" placeholder="ユーザー番号">
      <div class="invalid-feedback" style="text-align: center; color: red; font-size: 18px;"><?= $err['user_no'] ?></div>
      <input type="text" class="user" <?php if (isset($err['user_name'])) echo 'is-invalid'; ?>  name="user_name" value="<?= $user_name ?>" placeholder="名前">
      <div class="invalid-feedback" style="text-align: center; color: red; font-size: 18px;"><?= $err['user_name'] ?></div>
      <input type="text" class="password" <?php if (isset($err['password'])) echo "is-invalid"; ?>  name="password" placeholder="パスワード">
      <div class="invalid-feedback" style="text-align: center; color: red; font-size: 18px;"><?= $err['password'] ?></div>
      <input type="text" class="password" <?php if (isset($err['title'])) echo "is-invalid"; ?>  name="title" placeholder="達成テーマ">
      <div class="invalid-feedback" style="text-align: center; color: red; font-size: 18px;"><?= $err['title'] ?></div>
      <div class="form_btn">
        <button type="button" class="login_btn" onclick="preLogin()">戻る</button>
        <button type="submit" class="login_btn" style="background-color: rgb(255, 135, 135); margin-left: 50px;">登録する</button>
      </div>
    </div>
  </form>

  <script>
    function preLogin(){
      location.href = './login.php';
    }
  </script>
</body>
</html>