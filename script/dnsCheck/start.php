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

//设置数据库
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection(\App\Libraries\Config::get('mysql'));
$capsule->setAsGlobal();
$capsule->bootEloquent();

function getUserDns($uid)
{
	static $userDns = [];
	if (!isset($userDns[$uid])) {
		$dns = \App\Models\UserNs::getDnsServer($uid);
		$userDns[$uid] = $dns;
	}
	return $userDns[$uid];
}

function checkDns($uid, $domain)
{
	static $gDNS = [];
	if (empty($gDNS)) {
		$gDNS = \App\Libraries\Config::site('godaddyDNS');
	}
	$userDns = array_merge($gDNS, getUserDns($uid));
	$domainDNS = \App\Functions::getDomainDns($domain);
	if (count(array_intersect($domainDNS, $userDns)) == 2) {
		return true;
	}
	return false;
}

//每次最大执行数量
$max_number = 100;
$offset = 0;
$flag = true;

$sendEmailDay = 7;
$deleteDay = 10;

$sendEmailData = [];
$deleteData = [];
$deleteIds = [];

while ($flag) {
	//$res = \App\Models\Domain::select(array('created_at', 'name', 'uid', 'id'))->where('dns_status', 0)->orderBy('id', 'asc')->get()->toArray();
	$res = \App\Models\Domain::select(array('created_at', 'name', 'uid', 'id'))
		->where('dns_status', 0)
		->whereIn('uid', [100, 101])
		->offset($offset)
		->limit($max_number)
		->orderBy('id', 'asc')
		->get()->toArray();
	$offset += $max_number;
	if (empty($res)) {
		$flag = false;
		break;
	} else {
		foreach ($res as $item) {
			//校验域名dns
			if (checkDns($item['uid'], $item['name'])) {
				//已经改好了，则修改状态结束处理
				\App\Models\Domain::where('id', $item['id'])->update(array('dns_status'=>1));
				continue;
			} else {
				//未通过dns校验进行如下处理
				$time = time()-strtotime($item['created_at']);
				$days = (floor($time/86400));
				if ($days < $sendEmailDay) {
					//七天内的不处理
					continue;
				} else if ($days == $sendEmailDay) {
					//七天还没处理的，发邮件通知
					if (isset($sendEmailData[$item['uid']])) {
						$sendEmailData[$item['uid']][] = $item['name'];
					} else {
						$sendEmailData[$item['uid']] = [$item['name']];
					}
				} else if ($days >= $deleteDay) {
					//超过十天的，删掉
					if (isset($sendEmailData[$item['uid']])) {
						$deleteData[$item['uid']][] = $item['name'];
					} else {
						$deleteData[$item['uid']] = [$item['name']];
					}
					$deleteIds[] = $item['id'];
				}
			}
		}
	}
}

\App\Functions::getLogger()->notice('dns邮件通知域名', $sendEmailData);
\App\Functions::getLogger()->notice('dns邮件删除域名', $deleteData);

if (!empty($sendEmailData)) {
	$subject = '请尽快修改域名DNS服务器！';
	foreach ($sendEmailData as $uid=>$domains) {
		$user = \App\Models\User::select('email')->find($uid);
		$body = '您好，本站（趣米）停靠需要验证您的域名的DNS服务器，请尽快修改您的DNS服务器地址为'.implode(',', getUserDns($uid)).'，需要修改的域名如下：'.implode(', ', $domains)."\r\n感谢您的支持！";
		\App\Libraries\Email::factory()->insertQueue($user['email'], $body, $subject);
	}
}

if (!empty($deleteData)) {
	$subject = 'DNS未校验域名移除通知！';
	foreach ($deleteData as $uid=>$domains) {
		$user = \App\Models\User::select('email')->find($uid);
		$body = '您好，本站（趣米）停靠需要验证您的域名的DNS服务器，您账户的以下域名由于长期未校验DNS服务器已被移除：'.implode(', ', $domains)."\r\n感谢您的支持！";
		\App\Libraries\Email::factory()->insertQueue($user['email'], $body, $subject);
	}
	\App\Models\Domain::whereIn('id', $deleteIds)->delete();
}