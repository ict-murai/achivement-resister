<?php
ini_set('display_errors', 1);
ini_set('output_buffering', 1);
//GoogleカレンダーAPIから祝日を取得
 
$year = date("Y");

function getHolidays($year) {
	$api_key = 'AIzaSyD6CTJIgl8r3EXcBL4T8gW6Rdp51thlMZ8';
	$holidays = array();
	$holidays_id = 'japanese__ja@holiday.calendar.google.com'; 
	$url = sprintf(
        'https://www.googleapis.com/calendar/v3/calendars/%s/events?'.
		'key=%s&timeMin=%s&timeMax=%s&maxResults=%d&orderBy=startTime&singleEvents=true',
		$holidays_id,
		$api_key,
		$year.'-01-01T00:00:00Z' ,
		$year.'-12-31T00:00:00Z' ,
		150
	);
 
	if ( $results = file_get_contents($url, true)) {
        //URLの中に情報が入っていれば（trueなら）以下を実行する
		$results = json_decode($results);
        //JSON形式で取得した情報を配列に格納
		foreach ($results->items as $item ) {
			$date = strtotime((string) $item->start->date);
			$title = (string) $item->summary;
			$holidays[date('Y-m-d', $date)] = $title;
            //年月日をキー、祝日名を配列に格納
		}
		ksort($holidays);
        //ksort関数で配列をキーで逆順に（１月からの順番にした）
	}
	return $holidays; 
}
 
 
$Holidays_array = getHolidays($year); 
 
 
function display_to_Holidays($date,$Holidays_array) {
    //display_to_Holidays("Y-m-d","Y-m-d") → 引数1の日付と引数2の日付が一致すればその日の祝日名を取得する
    
	if(array_key_exists($date,$Holidays_array)){
        //各日付と祝日の配列データを照らし合わせる
        
		$holidays = "<br/>".$Holidays_array[$date];
		return $holidays; 
	}
}   

 
// Calender
date_default_timezone_set('Asia/Tokyo');

//前月・次月リンクが選択された場合は、GETパラメーターから年月を取得
if(isset($_GET['ym'])){ 
    $ym = $_GET['ym'];
}else{
    //今月の年月を表示
    $ym = date('Y-m');
}

$timestamp = strtotime($ym . '-01'); 
if($timestamp === false){
    $ym = date('Y-m');
    $timestamp = strtotime($ym . '-01');
}

 
$today = date('Y-m-j');
 
$html_title = date('Y年n月', $timestamp);

$prev = date('Y-m', strtotime('-1 month', $timestamp));
$next = date('Y-m', strtotime('+1 month', $timestamp));
 
$day_count = date('t', $timestamp);

$youbi = date('w', $timestamp);
 
$weeks = [];
$week = '';
 
$week .= str_repeat('<td></td>', $youbi);
 
for($day = 1; $day <= $day_count; $day++, $youbi++){
    
    $date = $ym . '-' . $day; 
    $Holidays_day = display_to_Holidays(date("Y-m-d",strtotime($date)),$Holidays_array);



    $DB_HOST = 'mysql:dbname=works;host=localhost;port=8889;charset=utf8';
    $DB_USER = 'root';
    $DB_PASSWORD = 'root';

    $pdo = new PDO($DB_HOST,$DB_USER,$DB_PASSWORD);
    $pdo->query('SET NAMES utf8;');
    $pdo ->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $sql = "SELECT date,id,start_time,end_time,break_time,comment FROM work WHERE user_id =:user_id AND DATE_FORMAT(date, '%Y-%m') =:date";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id', (int)$session_user['id'], PDO::PARAM_INT);
    $stmt->bindValue(':date', $yyyymm, PDO::PARAM_STR);
    $stmt->execute();
    $work_list = $stmt->fetchAll(PDO::FETCH_UNIQUE);

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
    
    if($today == $date){
        $week .= '<td class="today">'  . $day .'\n' .$start_time.'~'.$end_time.'\n'.$comment;
    }elseif(display_to_Holidays(date("Y-m-d"),$Holidays_array)){
        $week .= '<td class="holiday">'  . $day .'\n' . $Holidays_day .$start_time.'~'.$end_time.'\n'.$comment;
    }else{
        $week .= '<td>'  . $day .'\n' .$start_time.'~'.$end_time.'\n'.$comment;
    }
    $week .= '</td>';
    
    if($youbi % 7 == 6 || $day == $day_count){
        
        if($day == $day_count){
            $week .= str_repeat('<td></td>', 6 - ($youbi % 7));
        }
        
        $weeks[] = '<tr>' . $week . '</tr>';
        
        $week = '';
    }
}
    
?>
 
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>PHPカレンダー</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css?family=Noto+Sans" rel="stylesheet">
    <link rel="stylesheet" href="./css/calender.css">
</head>
 
<body>
    <div class="container" style="background-color: #fff;">
        <h3><a href="?ym=<?php echo $prev; ?>">&lt;</a><?php echo $html_title; ?><a href="?ym=<?php echo $next; ?>">&gt;</a></h3>
        <table class="table table-bordered">
            <tr>
                <th>日</th>
                <th>月</th>
                <th>火</th>
                <th>水</th>
                <th>木</th>
                <th>金</th>
                <th>土</th>
            </tr>
            <?php
                foreach ($weeks as $week) {
                    echo $week;
                }
            ?>
        </table>

    </div>
   
 
    
</body>
 
</html>