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



//已废弃使用本脚本



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

//需要添加到dns服务器的域名条件
$filter = array('dns_status'=>1, 'dns_parse'=>0);
//每次最大执行数量
$max_number = 100;
//dns服务器
$dns_server = '119.29.156.18:22';

$res = \App\Models\Domain::multiWhere($filter)->orderBy('updated_at', 'desc')->limit($max_number)->get()->toArray();

$domain_names = [];
$domain_updates = [];
if ($res) {
	foreach ($res as $domain) {
		$domain_names[] = $domain['name'];
		$domain_updates[] = array('id'=>$domain['id'],'dns_parse'=>1);
	}

	//dns服务器添加dns解析记录的脚本
	$addCommand = 'bash /root/qumi/acmeDNS.sh addDNS '.implode(' ', array_values($domain_names));

	if (runRemoteCommand($dns_server, $addCommand)) {
		//更新dns_parse字段
		if (!\App\Models\Domain::updateBatch($domain_updates, 'id')) {
			\App\Functions::getLogger()->error('update dns_parse error');
		}
		exit(0);
	} else {
		//需要邮件报警通知
		exit(3);
	}
}



function runRemoteCommand($remote, $command)
{
	$exitCode = 0;
	$output = null;
	list($remoteHost, $port) = explode(':', $remote);
	$localCommand = sprintf('ssh -T -p %s -q -o ConnectTimeout=30 -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no %s ', $port, $remoteHost);
	$remoteCommand = str_replace('"', '\\"', trim($command));
	$localCommand .= ' " ' . $remoteCommand . ' " ';
	exec($localCommand,$output, $exitCode);
	$log = array('command'=>$localCommand, 'output'=>$output, 'exitCode'=>$exitCode);
	if ($exitCode == 0) {
		\App\Functions::getLogger()->debug('exec acmeDNS.sh success ', $log);
		return true;
	}
	\App\Functions::getLogger()->error('exec acmeDNS.sh error', $log);
	return false;
}