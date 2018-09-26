<?php
/**
 * common.php for qumi
 * @author SamWu
 * @date 2018/7/23 11:26
 * @copyright boyaa.com
 */

// 定义运行环境
if (PHP_SAPI != 'cli') {
	if (isset($_SERVER['SERVER_NAME']) && strpos($_SERVER['SERVER_NAME'], 'test.798.cx') !== false) {
		define('RUN_ENV', 'development');
	} else {
		define('RUN_ENV', 'production');
	}
} else {
	define("RUN_ENV", 'production');
}

//define("RUN_ENV", 'production');
define("ONLINE", 'development');
define("HOMEPAGE", 'http://ni.cx');
define("ROOT", realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR); //根目录
define("VENDOR_DIR", ROOT . "vendor" . DIRECTORY_SEPARATOR);
define("APP_DIR", ROOT . "app" . DIRECTORY_SEPARATOR); //项目目录
define("LOG_DIR", ROOT . "logs" . DIRECTORY_SEPARATOR); //日志目录 运行时需要读写权限
define("CACHE_DIR", ROOT . "cache" . DIRECTORY_SEPARATOR); //系统缓存目录 运行时需要读写权限
define("TPL_DIR", ROOT . "templates" . DIRECTORY_SEPARATOR); //系统缓存目录 运行时需要读写权限
define("PUBLIC_DIR", ROOT . "public" . DIRECTORY_SEPARATOR);  //web访问目录
define("CONFIG_DIR", ROOT . 'config' . DIRECTORY_SEPARATOR);
define("SCRIPT_DIR", ROOT . 'script' . DIRECTORY_SEPARATOR);

// 自动载入类库
if (file_exists(VENDOR_DIR . "autoload.php")) {
	require_once VENDOR_DIR . "autoload.php";
} else {
	die("<pre>vendor目录不存在，请运行`composer install`</pre>");
}



/**
 * 设置时区
 */
date_default_timezone_set('ETC/GMT-8');