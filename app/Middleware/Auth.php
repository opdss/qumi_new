<?php
/**
 * AuthMiddleware.php for wa_poker.
 * 检测登录
 * @author SamWu
 * @date 2017/4/25 16:26
 * @copyright istimer.com
 */
namespace App\Middleware;

use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

use \App\Functions;

class Auth
{
	/**
	 * @var Container
	 */
	protected $ci;

	/**
	 * Auth constructor.
	 * @param Container $ci
	 */
	public function __construct(Container $ci)
	{
		$this->ci = $ci;
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param $next
	 * @return Response
	 */
	public function __invoke(Request $request, Response $response, $next)
	{
	    $roleMap = ['admin'=>2, 'user' => 1, 'userss' => 1000];
		$userInfo = $this->ci->session->get('userInfo');
		$flag = -1;//-1未登陆 0 没权限 1 ok
		if (!empty($userInfo)) {
            $currentRouteName = $request->getAttribute('route')->getName();
            $currentRoute = $this->ci->routes[$currentRouteName];


            if (isset($currentRoute['info']['auth']) && $currentRoute['info']['auth']) {
                $_auth = explode('|', $currentRoute['info']['auth']);
                $currentRouteRoleLevel = $_auth[0] ? (is_numeric($_auth[0]) ? intval($_auth[0]) : $roleMap[$_auth[0]]) : 1;
            } else {
                $currentRouteRoleLevel = 1;
            }

			//注入当前用户信息
			$this->ci->offsetSet('userInfo', $userInfo);
			$this->ci->offsetSet('menuGroup', $currentRouteRoleLevel > 1 ? 'admin' : 'user');
			$this->ci->offsetSet('currentMenu', $currentRouteName);

			$roleLever = isset($userInfo['roleLevel']) ? intval($userInfo['roleLevel']) : 0;
            //权限级别越高越大
            if ($roleLever >= $currentRouteRoleLevel) {
                $flag = 1;
            } else {
                $flag = 0;
            }
        }

        $raw = $request->getParam('raw');
        //未登陆或者没有权限
        if ($flag < 1) {
            $ajax = $this->ci->request->getHeaderLine('HTTP_X_REQUESTED_WITH');
            $isAjax = $ajax && strtolower($ajax) == 'xmlhttprequest';
			$ajaxRet = $flag == -1 ? -1 : 2;

			if ($raw) {
				return $response->write('对不起，你没有权限访问！');
			}

            if ($isAjax) {
                return $response->withJson(Functions::formatApiData($ajaxRet));
            } else {
                $redirectUrl = $request->getUri();
                if ($ajaxRet == -1) {
					return $response->withRedirect($this->ci->router->pathFor('login') . ($redirectUrl ? '?redirect_url=' . urlencode($redirectUrl) : ''));
				} else {
					return $response->write('对不起，你没有权限访问！');
				}
            }
        }

		$response = $next($request, $response);
		return $response;
	}

}