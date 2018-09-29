<?php
/**
 * 每分钟执行一次检查dns
 * 本地执行的绑定域名与dns的脚本
 * 执行本脚本将执行远程机器的 bindDNS.sh
 * 前提是本机器ssh能免密访问远程机器
 * User: wuxin
 * Date: 2018/7/5
 * Time: 20:41
 */

//公共定义文件
require_once dirname(dirname(__DIR__)).'/common.php';

// 命令行模式
if (PHP_SAPI != 'cli') {
	exit('run mode error!');
}

\App\Libraries\Config::setConfigPath(CONFIG_DIR);

//设置数据库
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection(\App\Libraries\Config::get('mysql'));
$capsule->setAsGlobal();
$capsule->bootEloquent();

//每次最大执行数量
$max_number = 100;
$loop = 0;

$sendEmailDay = 7;
$deleteDay = 10;

$data = [];

while ($loop < $max_number) {
	$res = \App\Models\EmailQueue::select(array('created_at', 'name'))->where('dns_status', 0)->orderBy('id', 'asc')->limit($max_number)->get()->toArray();
	if ($res) {
		foreach ($res as $item) {

		    $time = time()-strtotime($item['created_at']);
		    $days = (floor($time/86400));
		    if ($days == $sendEmailDay) {

            }
		}
	}
}