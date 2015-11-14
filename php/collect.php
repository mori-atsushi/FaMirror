<?php
$access_token = ''; //google用

function google($user) {
	global $access_token;
	// アプリケーション設定
	define('CONSUMER_KEY', '482245107839-ltqcd5n13loc9oerhh00gpaehsn6okt3.apps.googleusercontent.com');
	define('CONSUMER_SECRET', '56bapESDOFaQgDdeBg-M4-_W');

	// URL
	define('TOKEN_URL', 'https://accounts.google.com/o/oauth2/token');

	$params = array(
		'grant_type' => 'refresh_token',
		'refresh_token' => $user['refresh_token'],
		'client_id' => CONSUMER_KEY,
		'client_secret' => CONSUMER_SECRET,
	);

	// POST送信
	$options = array('http' => array(
		'method' => 'POST',
		'content' => http_build_query($params)
	));
	$res = file_get_contents(TOKEN_URL, false, stream_context_create($options));

	// レスポンス取得
	$token = json_decode($res, true);

	if(isset($token['error'])){
		echo 'エラー発生';
		exit;
	}

	$access_token = $token['access_token'];
}

function weather($user) {
	$url = 'http://weather.livedoor.com/forecast/webservice/json/v1?city=' . $user['weather_area'];
	$res = json_decode(file_get_contents($url), true );
	$return = '今日の' . $res['location']['city'] . 'の天気は、' . $res['forecasts'][0]['telop'] . '、です。';

	if($user['weather_temperature']) {
		$min = $res['forecasts'][0]['temperature']['min'];
		$max = $res['forecasts'][0]['temperature']['max'];

		if($min)
			$return = $return . '最低気温は、' . $min['celsius'] . '度、';
		if($max)
			$return = $return . '最高気温は、' . $max['celsius'] . '度';
		if($min || $max)
			$return = $return . 'の、予報です。';
	}

	if($user['weather_detail'])
		$return = $return . $res['description']['text'];

	if($user['weather_tomorrow'])
		$return = $return . '明日の天気は、' . $res['forecasts'][0]['telop'] . 'でしょう。';
	return $return;
}

function trash($user, $conn, $day, $week) {
	$return = '今日回収されるゴミは、';

	if($user['trash_area2'] == '-3')
		$trash_id = $user['trash_area1'];
	else
		$trash_id = $user['trash_area2'];

	if(($m = check_trash($conn, $day, $week, $trash_id)) == '')
		$return = $return . 'ありません。';
	else
		$return = $return . $m . 'です。';

	if($user['trash_tomorrow']) {
		$return = $return . '明日回収されるゴミは、';
	if(($m = check_trash($conn, $day + 1, $week + 1, $trash_id)) == '')
		$return = $return . 'ありません。';		
	else
		$return = $return . $m . 'です。';
	}

	return $return;	
}

function check_trash($conn, $day, $week, $trash_id) {
	$week_ja = array('日', '月', '火', '水', '木', '金', '土');
	$week_num = ceil($day / 7);
	$week_to = $week_ja[$week];

	if($conn && $_POST['user_id'] !== '') {
		mysql_select_db('famirror', $conn);
		$sql = 'SELECT type, wday FROM trash_types WHERE trash_id = ' . $trash_id;
		$result = mysql_query($sql);
		while($row = mysql_fetch_assoc($result)) {
			if(strpos($row['wday'], $week_to) !== false) {
				if(strpos($row['wday'], $week_to . '2') !== false) {
					$return = $return . $row['type'] . '、';
				} else {
					for($i = 1; $i <= 5; $i++) {
						if(strpos($row['wday'], $week_to . $i) !== false) {
							break;
						}
					}
					if($i > 5)
						$return = $return . $row['type'] . '、';
				}
			}
		}
	}

	return $return;	
}

function calendar($user) {
	global $access_token;
	if($access_token == '') {
		google($user);
	}

	$year = date(Y); $month = date(m); $day = date(d);
	$today = $year . '-' . $month . '-' . $day;
	$url = 'https://www.googleapis.com/calendar/v3/calendars/' . $user['user_mail'] . '/events';
	$url = $url . '?access_token=' . $access_token;
	$url = $url . '&timeMin=' . $today . 'T00:00:00%2b0900&timeMax=' . $today .'T23:59:59%2b0900';
	$res = file_get_contents($url);
	$cal = json_decode($res, true);
	if(isset($cal['error'])){
		echo 'エラー発生';
		exit;
	}

	$num = count($cal['items']);

	if(count($cal['items']) == 0) {
		return '今日の予定はありません。';
	}
	
	$return = '今日の予定は' . $num . '件です。';

	//ソート処理
	for($i = 1; $i < count($cal['items']); $i++) {
		$min = $cal['items'][$i - 1]['start']['dateTime'];
		$k = $i - 1;
		for($j = $i; $j < count($cal['items']); $j++) {
			if(strnatcmp($cal['items'][$j]['start']['dateTime'], $min) == '-1') {
				$min = $cal['items'][$j]['start']['dateTime'];
				$k = $j;
			}
		}
		if(($i - 1) != $k) {
			$tmp = $cal['items'][$i - 1];
			$cal['items'][$i - 1] = $cal['items'][$k];
			$cal['items'][$k] = $tmp;
		}
	}

	for($i = 0; $i < count($cal['items']); $i++) {	
		if($user['calendar_start']) {
			if(isset($cal['items'][$i]['start']['dateTime']))
				$return = $return . change_time($cal['items'][$i]['start']['dateTime'], $month, $day) . 'から';				
		}
		if($user['calendar_end']) {
			if(isset($cal['items'][$i]['start']['dateTime']))
				$return = $return . change_time($cal['items'][$i]['end']['dateTime'], $month, $day) . 'まで';
			else
				$return = $return . change_date($cal['items'][$i]['end']['date'], $month) . 'まで';				
		}
		if($user['calendar_location'] && isset($cal['items'][$i]['location']))
			$return = $return . $cal['items'][$i]['location'] . 'で';	
		$return = $return . $cal['items'][$i]['summary'] . 'です。';

		if($user['calendar_description'] && isset($cal['items'][$i]['description']))
			$return = $return . $cal['items'][$i]['description'] . '。';	

	}

	$return = $return . '以上です。';
	return $return;
}

function change_time($time, $month, $day) {
	$m = $time[5] . $time[6];
	$d = $time[8] . $time[9];
	$hour = $time[11] . $time[12];
	$min = $time[14] . $time[15];

	if($m != $month)
		$return = check_zero($m) . '月';
	if($d != $day)
		$return = $return . check_zero($d) . '日';	
	$return = $return . check_zero($hour) . '時';
	if($min != '00')
		$return = $return . check_zero($min) . '分';

	return $return;
}

function change_date($time, $month) {
	$m = $time[5] . $time[6];
	$d = $time[8] . $time[9];

	if($m != $month)
		$return = check_zero($m) . '月';
	$return = $return . check_zero($d) . '日';	

	return $return;
}

function check_zero($str) {
	if($str[0] == '0')
		return $str[1];
	return $str;
}
?>