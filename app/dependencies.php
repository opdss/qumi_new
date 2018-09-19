<?php
// DIC configuration

$container = $app->getContainer();

// Service factory for the ORM
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection(\App\Libraries\Config::get('mysql'));
$capsule->setAsGlobal();
$capsule->bootEloquent();
$container['db'] = $capsule;

$container['view'] = function ($c) {
	$settings = \App\Libraries\Config::get('twig');
	$view = new \Slim\Views\Twig($settings['template_path'], $settings['options']);
	//$basePath = rtrim(str_ireplace('index.php', '', $c['request']->getUri()->getBasePath()), '/');
	$baseUrl = $c['request']->getUri()->getBaseUrl();
	$view->addExtension(new Slim\Views\TwigExtension($c['router'], $baseUrl));
	$view->addExtension(new \App\Libraries\MyTwigExtension());
	return $view;
};

//monolog
$container['logger'] = function ($c) {
	//初始化日志类
	return \App\Functions::getLogger();
};

/*$container['renderer'] = function ($c) {
	$settings = $c->get('settings')['renderer'];
	return new Slim\Views\PhpRenderer($settings['template_path']);
};*/

$container['cache'] = function ($c) {
	return \App\Functions::getCache();
};

//设置session
$container['session'] = function ($c) {
	\App\Libraries\File::mkDir(CACHE_DIR.'session');
	$session = \Opdss\Cisession\Session::getInstance(\App\Libraries\Config::get('session'));
	$session->setLogger($c->logger);
	$session->start();
	return $session;
};

//设置session
/*\App\Libraries\File::mkDir(CACHE_DIR.'session');
$session = \Opdss\Cisession\Session::getInstance($container->get('settings')['session']);
$session->setLogger($container->logger);
$session->start();
$container['session'] = $session;*/

if (RUN_ENV == 'production') {
//500错误处理
	$container['errorHandler'] = function ($c) {
		return function ($request, $response, $exception) use ($c) {
			if ($request->isXhr()) {
				return $response->withStatus(500);
			} else {
				$res = array('errCode' => $exception->getCode(), 'errMsg' => $exception->getMessage());
				$c->logger->error('500 ERROR', $res);
				$data['site'] = \App\Libraries\Config::get('site');
				return $c->view->render($response, '500.twig', $data);
			}
		};
	};
}

//404
$container['notFoundHandler'] = function ($c) {
	return function ($request, $response) use ($c) {
		if ($request->isXhr()) {
			return $response->withStatus(404);
		} else {
			$data['site'] = \App\Libraries\Config::get('site');
			return $c->view->render($response, '404.twig', $data);
		}
	};
};

//405
$container['notAllowedHandler'] = function ($c) {
	return function ($request, $response, $methods) use ($c) {
		$return['errMsg'] = 'Method must be one of: ' . implode(', ', $methods);
		return $response
			->withStatus(405)
			->withHeader('Allow', implode(', ', $methods))
			->withJson($return);
	};
};
