<?php

//公共定义文件
require_once '../common.php';

// 命令行模式
if (PHP_SAPI == 'cli') {
    exit('run mode error!');
}

/**
 * 定义错误级别
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);


//运行时间打点
\App\Functions::runTime('run');

\App\Libraries\Config::setConfigPath(CONFIG_DIR);

// 实例化App
$app = new \Slim\App(array('settings' => \App\Libraries\Config::get('settings')));

// 设置依赖
require APP_DIR . 'dependencies.php';

//var_dump(\App\Functions::decTo36($_GET['id']));exit;
// 根据注释注册路由
$nroute = \Opdss\Nroute\Nroute::factory(array('cacheDir'=>CACHE_DIR));
$nroute->attachInfoField('auth');
$nroute->register($app, array(APP_DIR . 'Controllers' => 'App\\Controllers'));

//\Opdss\Nroute\Nroute::factory(array('cacheDir'=>CACHE_DIR))->register($app, array(APP_DIR . 'Controllers' => 'App\\Controllers'));
//exit;
$app->run();