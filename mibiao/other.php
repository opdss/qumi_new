<?php
/**
 * other.php for qumi
 * @author SamWu
 * @date 2018/9/4 10:08
 * @copyright boyaa.com
 */

//公共定义文件
require_once '../common.php';

//设置配置目录
\App\Libraries\Config::setConfigPath(CONFIG_DIR);


$fromUrl = \App\Functions::getFullUrl();
$urlInfo = \App\Functions::parseUrl($fromUrl);
if ($urlInfo['path'] && substr($urlInfo['path'], -4) == '.css') {
	header('Content-Type:text/css');
}

$key = md5($fromUrl);
if (!($content = \App\Functions::getCache()->get($key))) {
	$toUrl = str_replace('https://npc.app', 'http://www.npc.gov.cn', $fromUrl);
	$res = \Opdss\Http\Request::get($toUrl);
	$content = $res->getBody();
	$content = str_replace('http://www.npc.gov.cn','https://npc.app', $content);
	\App\Functions::getCache()->save($key, $content, 3600);
}

echo $content;