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

while ($loop < $max_number) {
	$res = \App\Models\EmailQueue::where('status', 1)->orderBy('level', 'desc')->orderBy('id', 'asc')->limit($max_number)->get()->toArray();
	if ($res) {
		foreach ($res as $item) {
			$email = \App\Libraries\Email::factory();
			$email->setSubject($item['subject'])
				->addAddress($item['to'])
				->setBody($item['body']);
			if ($item['attachment']) {
				foreach (json_decode($item['attachment']) as $one) {
					$email->addAttachment($one);
				}
			}
			if ($item['from_name']) {
				$email->setFromName($item['from_name']);
			}
			$status = 2;
			if (!$email->send()) {
				$status = 3;
				\App\Functions::getLogger()->error('发送队列邮件失败!', $item);
			}
			\App\Models\EmailQueue::where('id', $item['id'])->update(array('status' => $status));
			$loop++;
		}
	}
	sleep(1);
}