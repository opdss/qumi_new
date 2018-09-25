<?php
/**
 * Base.php for tools.
 * @author SamWu
 * @date 2017/7/4 14:08
 * @copyright istimer.com
 */

namespace App\Controllers;

use App\Functions;
use App\Libraries\Config;
use App\Libraries\File;
use Opdss\Cisession\Session;
use Slim\Container;
use Slim\Http\Request;

class Base
{
	//批量处理的最大数量
	const BATCH = 100;

	protected static $page_number = 20;

	/**
	 * @var Container
	 */
	protected $ci;

	protected $settings;

	protected $css = array();
	protected $js = array();

	protected $userInfo = null;

	protected $uid = 100;

	/**
	 * @var Session
	 */
	protected $session;

	/**
	 * Ctrl constructor.
	 * @param Container $ci
	 */
	public function __construct(Container $ci)
	{
		$this->ci = $ci;
		$this->session = $this->ci->session;

		$this->settings = $this->ci->get('settings');

		//$this->userInfo = empty($this->ci->userInfo) ? [] : $this->ci->userInfo;
		$this->userInfo = $this->session->get('userInfo');
		if ($this->userInfo) {
            $this->uid = (int)$this->userInfo['uid'];
        }
		//$this->menus = $this->getMenus();
		$this->addJs('/statics/js/main.js', !ONLINE ? time() : 0);
		$this->addCss('/statics/css/style.css', !ONLINE ? time() : 0);
	}

	protected function addJs($file, $version = 0)
	{
		array_push($this->js, $version ? $file . '?' . $version : $file);
		return $this->js;
	}

	protected function addCss($file, $version = 0)
	{
		array_push($this->css, $version ? $file . '?' . $version : $file);
		return $this->css;
	}

	protected function addStaticsDir($dir, $dep = 1, $version = 0)
	{
		$statics = Config::get('statics');
		if (isset($statics[$dir])) {
			$files = $statics[$dir];
		} else {
			$path = PUBLIC_DIR . 'statics/'.ltrim($dir, '/');
			$files = File::getFileNames($path, 1, $dep);
		}
		if ($files) {
			foreach ($files as $item) {
				if (substr($item, -3) == '.js') {
					$f = str_replace(PUBLIC_DIR, '/', $item);
					$this->addJs($f, $version);
				} elseif (substr($item, -4) == '.css') {
					$f = str_replace(PUBLIC_DIR, '/', $item);
					$this->addCss($f, $version);
				}
			}
		}
		return $files;
	}

	/**
	 * 返回json
	 * @param $param
	 * @param array $data
	 * @return mixed
	 */
	protected function json($param, $data = array())
	{
		$extra = array();
		if (func_num_args() == 1) {
			$data = $extra;
		}
		return $this->ci->get('response')->withJson(Functions::formatApiData($param, $data, $extra));
	}

	protected function view($tpl, $data = array())
	{
		$render_data['site'] = Config::get('site');
		$render_data['statics'] = array('css' => $this->css, 'js' => $this->js);
		$render_data = array_merge($render_data, $data);
		$render_data['runtime'] = round(\App\Functions::runTime('run', true), 6);
		$render_data['userInfo'] = $this->userInfo;
		$render_data['online'] = RUN_ENV == 'production';

		//由auth中间件 注入的数据
		if ($this->ci->offsetExists('menuGroup')) {
			$render_data['menus'] = $this->getMenus($this->ci->offsetGet('menuGroup'));
			$render_data['currentMenu'] = $this->ci->offsetGet('currentMenu');
		} else {
			$render_data['menus'] = $this->getMenus('user');
		}

		return $this->ci->view->render($this->ci->response, $tpl, $render_data);
	}

	/**
	 * 记录日志
	 * @param $level
	 * @param $message
	 * @param null $content
	 */
    protected function log($level, $message, $content=null)
	{
		if ($content && !is_array($content)) {
			$content = [$content];
		}
		if ($content == null) {
			$content = [];
		}
		if (!is_string($message)) {
			$message = json_encode($message);
		}
		if ($this->uid) {
			$message = '[uid:'.$this->uid.'] '.$message;
		}
		$this->ci->logger->{$level}($message, $content);
	}

	protected function checkRole($role = 'admin')
	{
		if ($this->userInfo['role'] !== $role) {
			return false;
		}
		return true;
	}

	protected function getCaptchaImg($k, $w = 150, $h = 40)
	{
		$captchaUrl = $this->ci->router->pathFor('captcha.index', ['key'=>$k]).'?1';
		if ($w = intval($w)) {
			$captchaUrl .= '&w='.$w;
		}
		if ($h = intval($h)) {
			$captchaUrl .= '&h='.$h;
		}
		$html = '<img src="'.$captchaUrl.'" onclick="this.setAttribute(\'src\', \''.$captchaUrl.'&t=\'+Math.random())"';
		$html .= ($w ? 'width="'.$w.'"' : '').' '.($h ? 'height="'.$h.'"' : '').'">';
		return $html;
	}


	protected function getCurrentMenu(Request $request)
	{
		$currentRouteName = $request->getAttribute('route')->getName();
		$routes = $this->ci->routes;
		foreach ($routes as $route) {
			if ($route['name'] == $currentRouteName) {
				return isset($route['info']['auth']) && $route['info']['auth'] ? explode('|', $route['info']['auth'], 3) : [];
			}
		}
		return false;
	}

	/**
	 * 获取菜单
	 * @return array
	 */
    protected function getMenus($gruop = null)
    {
        $routes = $this->ci->routes;
        $cacheKey = md5(json_encode($routes));
        $cache = $this->ci->cache;
        if (!($menus = $cache->get($cacheKey))) {
            $menus = [];
            foreach ($routes as $routeName => $route) {
                $sub = $route['info'];
                if (isset($sub['auth']) && $sub['auth']) {
                    $arr = explode('|', $sub['auth'], 3);
                    if (count($arr) > 1) {
                        if (!isset($menus[$arr[0]])) {
                            $menus[$arr[0]] = [];
                        }
                        if (!isset($menus[$arr[0]][$arr[1]])) {
                            $menus[$arr[0]][$arr[1]] = array(
                                'routeName' => $routeName,
                                'name' => $arr[1],
                                'url' => $this->ci->router->pathFor($route['name']),
                                'sub' => []
                            );
                        }
                        if (isset($arr[2])) {
                            $one = [
								'routeName' => $routeName,
                                'name' => $arr[2],
                                'url' => $this->ci->router->pathFor($route['name'])
                            ];
                            $menus[$arr[0]][$arr[1]]['sub'][$arr[2]] = $one;
                        }
                    }
                }
            }
            $cache->save($cacheKey, $menus, 30*86400);
        }
        return $gruop && isset($menus[$gruop]) ? $menus[$gruop] : $menus;
    }

    protected function sessCaptcha($key, $val=null) {
    	if ($val === null) {
    		return $this->session->getFlashdata($key);
		} else {
    		return $this->session->setFlashdata($key, strtolower($val));
		}
	}
}