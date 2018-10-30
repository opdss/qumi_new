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

//需要添加到dns服务器的域名条件
$filter = array('dns_status'=>1, 'dns_parse'=>0);
//每次最大执行数量
$max_number = 100;
//多个dns服务器，没有做主从，都是独立的
$dns_server = ['119.29.156.18:22', '140.143.225.238:22', '47.90.29.244:22'];

$res = \App\Models\Domain::multiWhere($filter)->orderBy('updated_at', 'desc')->limit($max_number)->get()->toArray();

$domain_names = [];
$domain_updates = [];
if ($res) {
	foreach ($res as $domain) {
		$domain_names[] = $domain['name'];
		$domain_updates[] = array('id'=>$domain['id'],'dns_parse'=>1);
	}

	//dns服务器添加dns解析记录的脚本
	$command = 'bash /root/qumi/acmeDNS.sh addDNS '.implode(' ', $domain_names);

	$succServ = 0;
	foreach ($dns_server as $server) {
		if (runDNSCommand($server, $command)) {
			$succServ ++;
		}
	}

	//只要一个dns服务器添加成功则更新数据库
	if ($succServ > 0) {
		//更新dns_parse字段
		if (!\App\Models\Domain::updateBatch($domain_updates, 'id')) {
			$title = '紧急：更新数据库出错啦！';
			$body = '更新数据库出错啦：'.json_encode($domain_updates);
			\App\Libraries\Email::factory()->send('opdss@qq.com', $body, $title);
			\App\Functions::getLogger()->error($title);
		}
		if ($succServ == count($dns_server)) {
			exit(0);
		}
	}
	//需要邮件报警通知
	$title = '紧急：域名添加dns出错！';
	$body = '域名添加dns服务器出错,成功了'.$succServ.'个(共'.count($dns_server).'), 添加域名：'.implode(',', $domain_names);
	\App\Libraries\Email::factory()->send('opdss@qq.com', $body, $title);
	\App\Functions::getLogger()->error($title);
	exit(1);
}

/**
 * 向dns服务器添加解析域名
 * @param $server
 * @param $domains
 * @return bool
 */
function runDNSCommand($server, $command)
{
	//本机IP
	$localHost = '47.90.29.244';
	$exitCode = 0;
	$output = null;

	list($remoteHost, $port) = explode(':', $server);
	//判断是否远程机执行，确保有远程免密登陆
	if ($remoteHost != $localHost) {
		$localCommand = sprintf('ssh -T -p %s -q -o ConnectTimeout=30 -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no %s ', $port, $remoteHost);
		$remoteCommand = str_replace('"', '\\"', trim($command));
		$localCommand .= ' " ' . $remoteCommand . ' " ';
	} else {
		$localCommand = $command;
	}

	exec($localCommand,$output, $exitCode);
	$log = array('server'=> $server, 'command'=>$localCommand, 'output'=>$output, 'exitCode'=>$exitCode);
	if ($exitCode == 0) {
		\App\Functions::getLogger()->debug('操作dns解析成功', $log);
		return true;
	}
	\App\Functions::getLogger()->error('操作dns解析失败', $log);
	return false;
}