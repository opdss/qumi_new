<?php
//公共定义文件
require_once '../common.php';

if ($_SERVER['HTTP_HOST'] == '47.75.155.84:80' || $_SERVER['HTTP_HOST'] == '47.75.155.84') {
	\App\Functions::redirect(HOMEPAGE);
}

//设置配置目录
\App\Libraries\Config::setConfigPath(CONFIG_DIR);

//访问域名
//$hostName = \App\Functions::trimDomain($_SERVER['HTTP_HOST']);
$fromUrl = \App\Functions::getFullUrl();
$urlInfo = \App\Functions::parseUrl($fromUrl);
$hostName = $urlInfo['domain'];

if (!$hostName) {
    \App\Functions::redirect(HOMEPAGE);
}
//var_dump($urlInfo);
//检查跳转
$redirectConf = \App\Libraries\Config::get('redirect');
if ($redirectConf) {
    $rd = ($urlInfo['prefix'] == '' ? '@' : $urlInfo['prefix']).'.'.$urlInfo['domain'];
    if (isset($redirectConf[$rd])) {
        \App\Functions::redirect($redirectConf[$rd]['url'], $redirectConf[$rd]['status']);
    }
    $rd = '*.'.$urlInfo['domain'];
    if (isset($redirectConf[$rd])) {
		\App\Functions::redirect($redirectConf[$rd]['url'], $redirectConf[$rd]['status']);
    }
}

//开关控制是否全局跳转
if (\App\Libraries\Config::site('isRedirct')) {
    //\App\Functions::redirect(HOMEPAGE.'/d/'.$hostName.'?from='.base64_encode($fromUrl));
}

//设置数据库
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection(\App\Libraries\Config::get('mysql'));
$capsule->setAsGlobal();
$capsule->bootEloquent();


//域名不存在了
$domainModel = \App\Models\Domain::multiWhere(array('name'=>$hostName))->first();
if (empty($domainModel)) {
    \App\Functions::getLogger()->alert('detail domain not found: '. $_SERVER['HTTP_HOST']);
	\App\Functions::redirect(HOMEPAGE);
}

//记录访问日志 start
$logModel = \App\Functions::saveAccessLog($domainModel, $fromUrl);
//记录访问日志 end

//检查有没有绑定米表
$mibiaoModel = \App\Models\Mibiao::where('domain_id', $domainModel->id)->first();
if ($mibiaoModel) {
	//有米表直接跳转
	\App\Functions::redirect(HOMEPAGE.'/m/'.$mibiaoModel->path);
}

//根据ip地理位置设置语言
if (!isset($_GET['l'])) {
    if (strpos($logModel->region, '中国') === false && strpos($logModel->region, '台湾') === false) {
        $_GET['l'] = 'en';
    }
}


$temp['logid'] = $logModel->id;
//开始准备渲染模板页面
if ($domainModel->template_id && $templateInfo = \App\Models\Template::find($domainModel->template_id)) {
	$log = ['domain_id' => $domainModel->id, 'template_id' => $templateInfo->id, 'theme_id' => $templateInfo->theme_id];
	//默认主题模板为1
	if (!$templateInfo->theme_id || !$themeInfo = \App\Models\Theme::find($templateInfo->theme_id)) {
		\App\Functions::getLogger()->error('mibiao theme_id error:', $log);
		$themeInfo = \App\Models\Theme::find(1);
	}
	$temp['domain'] = $domainModel;
	$temp['template'] = $templateInfo;
	$temp['site'] = \App\Libraries\Config::get('site');
	try {
		\App\Functions::getTwig()->display($themeInfo->path, $temp);
		exit();
	} catch (Exception $e) {
		\App\Functions::getLogger()->error('mibiao error => ' . $e->getMessage(), ['path' => $themeInfo->path, $log]);
		header('location:' . HOMEPAGE);
	}
} else {
	//未设置模板的自动跳到默认域名展示页
	\App\Functions::getLogger()->debug('domain_id:' . $domainModel->id . '未设置模板 =>' . $domainModel->template_id);
	\App\Functions::redirect(HOMEPAGE.'/detail/'.$hostName);
}