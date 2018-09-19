<?php
/**
 * jxdang.php for qumi
 * @author SamWu
 * @date 2018/9/3 10:12
 * @copyright boyaa.com
 */


define("ROOT", realpath(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR); //根目录
define("VENDOR_DIR", ROOT . "vendor" . DIRECTORY_SEPARATOR);
require_once VENDOR_DIR . "autoload.php";
//设置时区
date_default_timezone_set('ETC/GMT-8');

//全局cookies
$COOKIES = [];
$HEADERS = [
	'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
	'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36',
];


login('350125196303214111', '888888');
readVideo('00005');

function readVideo($vid) {
	global $COOKIES, $HEADERS;

	$url = 'http://119.29.22.54:8082/StudentClass5/WatchVideo5.aspx/UpdateStudyType?dt='.urlencode(date('D M d Y'));
	$data = ['RVCode'=>intval($vid)];

	$totaltime = 2409;
	$nowtime = 2400;
	$cookie = [
		'RID' => $vid,
		'checkUid' => $COOKIES['userid'],
		'id' => $vid,
		'nowtime' => $nowtime,
		'st' => urlencode(date('Y/m/d H:i:s', time()-$nowtime)),
		'totaltime' => $totaltime,
		'url' => urlencode('http://119.29.22.54:8082/StudentClass5/WatchVideo5.aspx?ids='.$vid),
	];
	$cookie = array_merge($COOKIES, $cookie);
	$header = array_merge($HEADERS, [
		'X-Requested-With' => 'XMLHttpRequest',
		'Accept' => 'application/json, text/javascript, */*; q=0.01',
		'Content-Type' => 'application/json; charset=UTF-8',
		'Content-Length' => strlen(json_encode($data)),
		'Referer' => 'http://119.29.22.54:8082/StudentClass5/WatchVideo5.aspx?ids='.$vid,
	]);

	$req = \Opdss\Http\Request::factory();
	$req->cookies($cookie);
	$req->headers($header);
	$res = $req->post($url, json_encode($data));
	var_dump($res->getBody());
}


//登陆
function login($user, $pass) {
	global $COOKIES, $HEADERS;
	$params = [
		'txtUserName' => $user,
		'txtPwd' => $pass,
		'ddlType' => 2,
		'btnLogin' => '',
		'txtLoginName' => '',
		'txtDM' => '',
		'txtFR' => '',
		'zhjg' => '',
		'zhfr' => '',
	];
	$viewParams = getLoginViewParams();
	if (count($viewParams) != 3) {
		debug('view 参数获取有误');
	}
	$params = array_merge($params, $viewParams);

	$url = 'http://119.29.22.54:8082/Login.aspx?ZY=5';

	$header = ['Referer' => 'http://119.29.22.54:8082/Login.aspx?ZY=5', 'Content-Type'=>'application/x-www-form-urlencoded'];
	$header = array_merge($HEADERS, $header);
	debug('login headers: '.json_encode($header));
	$req = \Opdss\Http\Request::factory();
	$req->headers($header);
	$req->cookies($COOKIES);

	$res = $req->post($url, $params);

	setCooKies($res->getCookies());
	debug('login cookie: '.json_encode($COOKIES));

	if ($COOKIES['userid']) {
		return $COOKIES['userid'];
	}
	return false;
}

//获取登陆参数
function getLoginViewParams() {
	global $COOKIES;
	$data = [];
	$login_url = 'http://119.29.22.54:8082/Login.aspx?ZY=5';

	$res = \Opdss\Http\Request::get($login_url);

	$body = $res->getBody();

	$pregs = [
		'__VIEWSTATE' => '/id="__VIEWSTATE" value="([0-9a-zA-Z-_=\+\/]*)"/',
		'__VIEWSTATEGENERATOR' => '/id="__VIEWSTATEGENERATOR" value="([0-9a-zA-Z-_=\+\/]*)"/',
		'__EVENTVALIDATION' => '/id="__EVENTVALIDATION" value="([0-9a-zA-Z-_=\+\/]*)"/',
	];

	foreach ($pregs as $k=>$preg) {
		$arr = [];
		if (preg_match($preg, $body, $arr)) {
			$data[$k] = $arr[1];
		}
	}
	setCooKies($res->getCookies());

	debug($data);
	debug($COOKIES);
	return $data;
}

//调试日志
function debug($data) {
	$file = dirname(__FILE__).DIRECTORY_SEPARATOR.'jxdang.log';
	if (!is_string($data)) {
		$data = json_encode($data);
	}
	$data = '['.date('Y-m-d H:i:s').'] '.$data.PHP_EOL;
	return file_put_contents($file, $data, FILE_APPEND);
}

//设置全局cookie
function setCooKies($cookie) {
	global $COOKIES;
	if (!$cookie) {
		return false;
	}
	$data = [];
	foreach ($cookie as $k=>$v) {
		$data[$k] = $v['value'];
	}
	$COOKIES = array_merge($COOKIES, $data);
	return true;
}
