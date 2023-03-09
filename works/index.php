<?php
// ユーザー側日報登録画面
// require_once(dirname(__FILE__)).'./function.php';
/* ini_set('display_errors', 1);
ini_set('output_buffering', 1); */

// 1．ログイン状態のチェック
session_start();

if (!isset($_SESSION['USER'])){
  // ログインされていない場合はログイン画面へ遷移
  header('Location: ./login.php');
  exit();
}


//　ログインユーザーの情報をセッションから取得
$session_user = $_SESSION['USER'];


$DB_HOST = 'mysql:dbname=works;host=localhost;port=8889;charset=utf8';
$DB_USER = 'root';
$DB_PASSWORD = 'root';

$pdo = new PDO($DB_HOST,$DB_USER,$DB_PASSWORD);
$pdo->query('SET NAMES utf8;');
$pdo ->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    // 日報をデータベースに登録処理

    // 入力をPOSTパラメータから取得
    $target_date = $_POST['target_date'];
    $modal_start_time = $_POST['start_time_content'];
    $modal_end_time = $_POST['end_time_content'];
    $modal_break_time = $_POST['break_time_content'];
    $modal_comment = $_POST['modal_comment'];

    // 対象日のデータがあるかどうかチェックする
    $sql = "SELECT id FROM work WHERE user_id =:user_id AND date =:date LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id', (int)$session_user['id'], PDO::PARAM_INT);
    $stmt->bindValue(':date', $target_date, PDO::PARAM_STR);
    $stmt->execute();
    $work = $stmt->fetch();

    if ($work){
        // 対象日のデータがあればUPDATE
        $sql = "UPDATE work SET start_time =:start_time,end_time =:end_time,break_time =:break_time,comment =:comment WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', (int)$work['id'], PDO::PARAM_INT);
        $stmt->bindValue(':start_time', $modal_start_time, PDO::PARAM_STR);
        $stmt->bindValue(':end_time', $modal_end_time, PDO::PARAM_STR);
        $stmt->bindValue(':break_time', $modal_break_time, PDO::PARAM_STR);
        $stmt->bindValue(':comment', $modal_comment, PDO::PARAM_STR);
        $stmt->execute();
    }else{
        // 対象日のデータがなければINSERT
        $sql = "INSERT INTO work (user_id,date,start_time,end_time,break_time,comment) VALUE (:user_id,:date,:start_time,:end_time,:break_time,:comment)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', (int)$session_user['id'], PDO::PARAM_INT);
        $stmt->bindValue(':date', $target_date, PDO::PARAM_STR);
        $stmt->bindValue(':start_time', $modal_start_time, PDO::PARAM_STR);
        $stmt->bindValue(':end_time', $modal_end_time, PDO::PARAM_STR);
        $stmt->bindValue(':break_time', $modal_break_time, PDO::PARAM_STR);
        $stmt->bindValue(':comment', $modal_comment, PDO::PARAM_STR);
        $stmt->execute();
    }
}

/* var_dump($session_user);
exit(); */

//　2．ユーザーの日報データを取得
if (isset($_GET['m'])){
    $yyyymm = $_GET['m'];
    $day_count = date('t', strtotime($yyyymm));
}else{
    $yyyymm = date('Y-m');
    $day_count = date('t');
}


$sql = "SELECT date,id,start_time,end_time,break_time,comment FROM work WHERE user_id =:user_id AND DATE_FORMAT(date, '%Y-%m') =:date";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':user_id', (int)$session_user['id'], PDO::PARAM_INT);
$stmt->bindValue(':date', $yyyymm, PDO::PARAM_STR);
$stmt->execute();
$work_list = $stmt->fetchAll(PDO::FETCH_UNIQUE);

/* echo '<pre>';
var_dump($work_list);
echo '</pre>';
exit(); */


// 当日のデータがあるかどうかチェックする
$sql = "SELECT id,start_time,end_time,break_time,comment FROM work WHERE user_id =:user_id AND date =:date LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':user_id', (int)$session_user['id'], PDO::PARAM_INT);
$stmt->bindValue(':date', date('Y-m-d'), PDO::PARAM_STR);
$stmt->execute();
$today_work = $stmt->fetch();

if ($today_work){
    $modal_start_time = $today_work['start_time'];
    $modal_end_time = $today_work['end_time'];
    $modal_break_time = $today_work['break_time'];
    $modal_comment = $today_work['comment'];
}else{
    $modal_start_time = '';
    $modal_end_time = '';
    $modal_break_time = '';
    $modal_comment = '';
}


function time_format_dw($date){
    $format_date = NULL;
    $week = array('日','月','火','水','木','金','土');

    if ($date){
        $format_date = date('j('.$week[date('w', strtotime($date))].')', strtotime($date));
    }

    return $format_date;
}

if (isset($_SESSION['TITLE'])){
    $title_comment = $_SESSION['TITLE'];
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/style.css">
    <title>月別リスト</title>
</head>
<body style="background-color: rgb(236, 249, 255);">
    <h1 class="title">達成度登録システム</h1>
    <hr class="title_hr">

    
    <div class="user_info">
        <p class="title_text">達成テーマ</p>
        <?php echo '<p class="title_text">'.$title_comment.'</p>' ?>
        <div class="user_info_text">
            <p>ユーザー名: <?php echo $session_user['name']; ?></p>
            <p>前回来訪日:   <?php
                            $log_time = date("Y/m/d");
                            // cookieが設定されていなければ(初回アクセス)、cookieを設定する
                            if (! isset($_COOKIE['visit_count'])) {
                                // cookieを設定
                                setcookie('visit_count', 1);
                                setcookie('visit_history', $log_time);
                                echo "初めてのアクセスです<br>";
                                echo "現在の日時は" . $log_time . "<br>";
                            }// cookieがすでに設定されていれば(2回目以降のアクセス)、cookieで設定した数値を加算する
                            else {
                                if ($log_time == $_COOKIE['visit_history']){
                                    $count = $_COOKIE['visit_count'];
                                    $visit_history = $_COOKIE['visit_history'];
                                    setcookie('visit_count', $count);
                                    setcookie('visit_history', $log_time); //←追加
                                    echo "訪問回数は" . $count . "回<br>";
                                    echo "現在の日時は" . $log_time . "<br>";
                                    echo "前回のアクセス日時は" . $visit_history . "<br>";
                                }else{
                                    $count = $_COOKIE['visit_count'] + 1;
                                    $visit_history = $_COOKIE['visit_history'];
                                    setcookie('visit_count', $count);
                                    setcookie('visit_history', $log_time); //←追加
                                    echo "訪問回数は" . $count . "回<br>";
                                    echo "現在の日時は" . $log_time . "<br>";
                                    echo "前回のアクセス日時は" . $visit_history . "<br>";
                                }
                            }
                        ?>
            </p>
            <!-- <button id="listBtn" class="logout_btn" onclick="openList()">OpenList</button>
            <button id="calenderBtn" class="logout_btn" onclick="openCalender()">OpenCalender</button> -->
            <button id="logout_btn" class="logout_btn" onclick="Logout()">Logout</button>
        </div>
    </div>


    <div class="form_body" id="form_body" style="margin-bottom:40px; text-align:center;">
        <form action="" class="list_form">
            <h1 class="form_title">達成リスト</h1>

            <select id="form_select" class="form_select" name="m" onchange="submit(this.form)">
                <option value="<?= date('Y-m'); ?>"><?= date('Y/m'); ?></option>
                <?php for ($i=1; $i <12; $i++): ?>
                    <?php $target_yyyymm = strtotime("-{$i}months"); ?>
                <option value="<?= date('Y-m', $target_yyyymm); ?>" <?php if ($yyyymm == date('Y-m', $target_yyyymm)) echo 'selected'; ?>><?= date('Y/m', $target_yyyymm); ?></option>
                <?php endfor; ?>
            </select>

            <table class="form_table">
                <thead>
                    <tr>
                        <th scope="col" class="th_majo" style="background-color: rgb(204, 228, 255);">日</th>
                        <th scope="col" class="th_majo" style="background-color: rgb(204, 228, 255);">開始</th>
                        <th scope="col" class="th_majo" style="background-color: rgb(204, 228, 255);">終了</th>
                        <th scope="col" class="th_majo" style="background-color: rgb(204, 228, 255);">休憩</th>
                        <th scope="col" class="th_long" style="background-color: rgb(204, 228, 255);">達成内容</th>
                        <th scope="col" class="th_maino" style="background-color: rgb(204, 228, 255);">編集</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 1; $i <= $day_count; $i++): ?>
                        <?php
                            $start_time = '';
                            $end_time = '';
                            $break_time = '';
                            $comment = '';

                            if (isset($work_list[date('Y-m-d', strtotime($yyyymm.'-'.$i))])){

                                $work = $work_list[date('Y-m-d', strtotime($yyyymm.'-'.$i))];
                                if ($work['start_time']){
                                    $start_time = date('H:i', strtotime($work['start_time']));
                                }
                                if ($work['end_time']){
                                    $end_time = date('H:i', strtotime($work['end_time']));
                                }
                                if ($work['break_time']){
                                    $break_time = date('H:i', strtotime($work['break_time']));
                                }   
                                if ($work['comment']){
                                    $comment = mb_strimwidth($work['comment'], 0, 40, '...');
                                }
                            }
                        ?>
                        <tr>
                            <th scope="row" class="th_majo" style="background-color: rgb(204, 228, 255);"><?= time_format_dw($yyyymm.'-'.$i) ?></th>
                            <td class="th_majo"><?= $start_time ?></td>
                            <td class="th_majo"><?= $end_time ?></td>
                            <td class="th_majo"><?= $break_time ?></td>
                            <td class="th_long"><?= $comment ?></td>
                            <td class="th_maino">
                                <button type="button" name="check_btn" class="check_btn" id="check_btn" onclick="GetDayModal(this)"
                                    data-day="<?= $yyyymm.'-'.sprintf('%02d', $i) ?>">✔︎</button>
                            </td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </form>
        <!-- <button id="modalOpen" class="modalOpen" onclick="openModal()">Open</button> -->
        <div id="modal" class="modal" onload="LoadProc();">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal_header_title">
                        <h1>達成度登録</h1>
                        <span class="modalClose" onclick="closeModal()">&times;</span>
                    </div>
                    <hr class="modal_hr">
                </div>
                <form class="modal_body" method="POST">
                    <div class="modal_date">
                        <p><?= date('n', strtotime($yyyymm)) ?>/<span id="modal_day"><?= time_format_dw(date('Y-m-d')); ?></span></p>
                    </div>
                    <div class="time_content">
                        <div class="start_content">
                            <input type="text" name="start_time_content" class="start_time_content" id="start_time_content" value="<?= $modal_start_time ?>" placeholder="開始">
                            <!-- <input type="time" name="" id="start_time" class="start_time" value="09:00"> -->
                            <button type="button" name="start_btn" id="start_time" class="start_time">ボタン</button>
                        </div>
                        <div class="start_content">
                            <input type="text" name="end_time_content" class="start_time_content" id="end_time_content" value="<?= $modal_end_time ?>" placeholder="終了">
                            <!-- <input type="time" name="" id="start_time" class="start_time" value="20:00"> -->
                            <button type="button" name="end_btn" id="end_time" class="start_time">ボタン</button>
                        </div>
                        <div class="start_content">
                            <input type="text" name="break_time_content" id="break_time" class="break_time" value="<?= $modal_break_time ?>" placeholder="休憩">
                        </div>
                    </div>
                    <!-- <input type="text" name="modal_comment" id="modal_comment" class="clear_content" value="<?= $modal_comment ?>"> -->
                    <textarea name="modal_comment" id="modal_comment" class="clear_content" rows="5" placeholder="達成内容"><?= $modal_comment ?></textarea>
                    <div class="modal_form_btn">
                        <button type="submit" class="modal_close_btn">Close</button>
                        <button type="submit" class="modal_save_btn">Save</button>
                    </div>
                    <input type="hidden" name="target_date" id="target_date">
                </form>
            </div>
        </div>
    </div>

    <!-- <div class="calender" style="width: 900px; height: 750px; display: none;" id="calender">
        <?php //include_once './calender.php'; ?>
    </div> -->


    <script src="https://code.jquery.com/jquery-3.6.3.min.js" integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous"></script>
    <script>
        const buttonOpen = document.getElementById("modalOpen");
        const modal = document.getElementById("modal");
        const buttonClose = document.querySelector(".modalClose");
        const check_btn = document.getElementById('check_btn');


        function openModal(){
            modal.style.display = "block";
        }
        function closeModal(){
            modal.style.display = "none";
        }

        //モーダルコンテンツ以外がクリックされた時
        addEventListener("click", (e) => {
            if (e.target == modal) {
                modal.style.display = "none";
            }
        });

        const start_time = document.getElementById('start_time');
        start_time.addEventListener('click', ()=> {
            const now = new Date();
            const hour = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            $('#start_time_content').val(hour+':'+minutes);
        });

        const end_time = document.getElementById('end_time');
        end_time.addEventListener('click', ()=> {
            const now = new Date();
            const hour = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            $('#end_time_content').val(hour+':'+minutes);
        });

        function GetDayModal(modalWindow){
            var dataDay = modalWindow.getAttribute("data-day");
            
            // 編集ボタンが押された対象日のデータを取得
            var day = modalWindow.closest('tr').getElementsByTagName('th')[0].innerText;
            var start_time = modalWindow.closest('tr').getElementsByTagName('td')[0].innerText;
            var end_time = modalWindow.closest('tr').getElementsByTagName('td')[1].innerText;
            var break_time = modalWindow.closest('tr').getElementsByTagName('td')[2].innerText;
            var comment = modalWindow.closest('tr').getElementsByTagName('td')[3].innerText;

            modal.style.display = "block";
            
            $('#modal_day').text(day);
            $('#start_time_content').val(start_time);
            $('#end_time_content').val(end_time);
            $('#break_time_content').val(break_time);
            $('#modal_comment').val(comment);
            $('#target_date').val(dataDay);
        }
        
        const logout_btn = document.getElementById('logout_btn');

        logout_btn.addEventListener('click', function(){
            const result = window.confirm('本当にログアウトしますか？');
    
            if( result ) {
                location.href = './logout.php'
            }
        })

        /* const form_body = document.getElementById('form_body');
        const calender = document.getElementById('calender');

        function openCalender(){
            form_body.style.display = "none";
            calender.style.display = "block";
        }

        function openList(){
            calender.style.display = "none";
            form_body.style.display = "block";
        } */

        
    </script>
</body>
</html>