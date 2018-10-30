<?php
/**
 * crontab-genssl.php for qumi
 * @author SamWu
 * @date 2018/7/17 17:54
 * @copyright boyaa.com
 */
//公共定义文件
require_once dirname(dirname(__DIR__)).'/common.php';
\App\Libraries\Config::setConfigPath(CONFIG_DIR);

define('LOCK_FILE', SCRIPT_DIR.'acmeSSL/timeLock.log');
define('LOCK_TIME', 10860);
define('TIMESTAMP', time());

$lockTime = file_exists(LOCK_FILE) ? (int)file_get_contents(LOCK_FILE) : 0;
if ($lockTime > TIMESTAMP) {
	exit(1);
}

//设置数据库
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection(\App\Libraries\Config::get('mysql'));
$capsule->setAsGlobal();
$capsule->bootEloquent();

//需要申请证书的域名条件
$filter = array('dns_status'=>1, 'ssl_status'=>0, 'dns_parse'=>1, 'suffix'=>'app');

//一次最多处理一百个
$count = 300;


//测试模拟
/*sleep(70);
exit;*/

$records = \App\Models\Domain::select('id', 'name')->multiWhere($filter)->orderBy('updated_at', 'ASC')->limit($count)->get()->toArray();
if(empty($records)){
	\App\Functions::getLogger()->debug('ssl domain empty');
} else {
	//开始申请证书
	foreach ($records as $domain) {
		$exitCode = execAcme($domain);
	}
}

//隔十分钟重试一次证书申请
$debug = 0;
if (date('i')%10 == 0 || $debug) {
	$filter['ssl_status'] = 3; //只重试状态为3，acme error 的
	$records = \App\Models\Domain::select('id', 'name')->multiWhere($filter)->orderBy('updated_at', 'ASC')->limit($count)->get()->toArray();
	if(empty($records)){
		\App\Functions::getLogger()->debug('reget ssl domain empty');
	} else {
		\App\Functions::getLogger()->notice('重试申请证书', $records);
		foreach ($records as $domain) {
			execAcme($domain);
		}
	}
}



function execAcme(array $domain) {
	$output = '';$exitCode = 0;
	exec('bash '.SCRIPT_DIR.'acmeSSL/genssl.sh '.$domain['name'],$output, $exitCode);
	$log = ['domain'=>$domain, 'output'=>$output, 'exitCode'=>$exitCode];
	$logger = \App\Functions::getLogger();
	if ($exitCode == 0) {
		$res = \App\Models\Domain::where('id', $domain['id'])->update(['ssl_status'=>1]);
		if ($res) {
			$logger->info('genssl.sh exec success', $log);
		} else {
			$logger->error('genssl.sh exec success,update sql error', $log);
		}
	} else {
		$res = \App\Models\Domain::where('id', $domain['id'])->update(['ssl_status'=>$exitCode+1]);
		if ($res) {
			$logger->error('genssl.sh exec error', $log);
		} else {
			$logger->error('genssl.sh exec error,update sql error', $log);
		}
		//申请超数量，停三个小时在申请
		if (isset($output[0]) && $output[0] == '529') {
			file_put_contents(LOCK_FILE, TIMESTAMP+LOCK_TIME);
			$logger->error('genssl.sh exec 529 over');
			exit(0);
		}
	}
	return $exitCode;
}