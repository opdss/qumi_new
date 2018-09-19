<?php
/**
 * proxy.php for qumi
 * @author SamWu
 * @date 2018/9/17 15:03
 * @copyright boyaa.com
 */
require_once '../common.php';

/*if ($_SERVER['HTTP_HOST'] == '47.75.155.84:80' || $_SERVER['HTTP_HOST'] == '47.75.155.84') {
	\App\Functions::redirect(HOMEPAGE);
}*/

//设置配置目录
\App\Libraries\Config::setConfigPath(CONFIG_DIR);

$url = \App\Functions::getFullUrl();
$urlInfo = \App\Functions::parseUrl($url);
$params = [];
parse_str($urlInfo['query'], $params);
$url = 'http://798.cx'.$params['api'].'?'.http_build_query($params);
//var_dump($url);exit;
$method = $_SERVER['REQUEST_METHOD'];
$res = \Opdss\Http\Request::factory($url)->send($method);
echo $res->getBody();