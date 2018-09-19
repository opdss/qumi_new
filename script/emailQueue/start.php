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
//设置数据库q
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection(\App\Libraries\Config::get('mysql'));
$capsule->setAsGlobal();
$capsule->bootEloquent();

//需要添加到dns服务器的域名条件
$filter = array('dns_status'=>1, 'dns_parse'=>0);
//每次最大执行数量
$max_number = 200;

$res = \App\Models\EmailQueue::where('status', 1)->orderBy('id', 'desc')->limit($max_number)->get()->toArray();

if ($res) {
    foreach ($res as $item) {

    }
}