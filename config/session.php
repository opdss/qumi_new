<?php
/**
 * Created by PhpStorm.
 * User: wuxin
 * Date: 2017/12/5
 * Time: 23:12
 */

return array(
	//'sessionDriver' => 'redis',
	'sessionDriver' => 'file',
	'sessionCookieName' => 'gscsess',
	'sessionExpiration' => 7200,
	'sessionSavePath' => CACHE_DIR.'session',
	//'sessionSavePath' => 'tcp://127.0.0.1:6379?auth=XIN~!%40%23%24%25%5E%26*123',
	'sessionMatchIP' => true,
	'sessionTimeToUpdate' => 300,
	'sessionRegenerateDestroy' => true,
	'sessionKeyPrefix' => 'mySSO_Session:',

	'cookieDomain' => '',
	'cookiePath' => '/',
	'cookieSecure' => false,
	'cookieHTTPOnly' => false,
);