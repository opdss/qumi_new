<?php
/**
 * Login.php for qumi
 * @author SamWu
 * @date 2018/7/9 17:52
 * @copyright boyaa.com
 */
namespace App\Controllers;

use App\Functions;
use App\Models\User;
use App\Models\UserNs;
use Slim\Http\Request;
use Slim\Http\Response;

class Login extends Base
{
	/**
	 * @pattern /login
	 * @name login
	 * @method get|post
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function index(Request $request, Response $response, $args)
	{
		$data = array('errorMsg'=>'');
		$redirectUrl = $request->getParam('redirect_url', $this->ci->router->pathFor('index'));

		//已经登陆跳转
		if ($this->session->get('userInfo')) {
			return $response->withRedirect($redirectUrl);
		}
		if ($request->getMethod() == 'POST') {
			//csrf
			if ($request->getParam('_form') != $this->session->getFlashdata('_form')) {
				return $response->withRedirect($this->ci->router->pathFor('login'));
			}
			$email = $request->getParam('email');
			$password = $request->getParam('password');
			if (!$userObj = User::login($email, $password)) {
				$this->log('alert', '登陆验证失败', $request->getParams());
				$data['email'] = $email;
				$data['password'] = $password;
				$data['errorMsg'] = '用户名或者密码错误！';
			} else {
				$userInfo = $userObj->toArray();
				//更新登陆相关的信息
				$userObj->login_time = date('Y-m-d H:i:s');
				$userObj->login_ip = Functions::getIP();
				$userObj->login_num += 1;
				$userObj->save();
				//获取处理用户的ns，放到session
				$userInfo['dns_server'] = $userObj->userNs->toArray();
				$userInfo['dns_server_str'] = implode('<br/>',array_reduce($userInfo['dns_server'], function($a, $b) {$a[] = $b['server'];return $a;}));

				//测试 start
                if ($email == 'opdss@qq.com') {
                    $test_email = '';
                    //$test_email = '125621752@qq.com';
                    //$test_email = '645809448@qq.com';
                    //$test_email = '512830329@qq.com';
                    //$test_email = '773276691@qq.com';
                    //$test_email = 'dx02000@foxmail.com';
                    //$test_email = 'niudomain@163.com';
                    //$test_email = '543565536@qq.com';
                    //$test_email = 'dali@sohu.com';
                    //$test_email = '779410661@qq.com';
                    if ($test_email) {
                        $userObj = User::where('email', $test_email)->first();
                        if ($userObj) {
                            $userInfo = $userObj->toArray();
                            $userInfo['dns_server'] = $userObj->userNs->toArray();
                            $userInfo['dns_server_str'] = implode('<br/>', array_reduce($userInfo['dns_server'], function ($a, $b) {
                                $a[] = $b['server'];
                                return $a;
                            }));
                        }
                    }
                }
                //测试 end

				$this->session->set('userInfo', $userInfo);
				return $response->withRedirect($redirectUrl);
			}
		}
		$data['redirect_url'] = $redirectUrl;
		$data['_form'] = Functions::genRandStr(32);
		$this->session->setFlashdata('_form', $data['_form']);

		return $this->view('login/loginNew.twig', $data);
	}

	/**
	 * @pattern /logout
	 * @name logout
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function logout(Request $request, Response $response, $args)
	{
		$this->session->destroy();
		return $response->withRedirect($this->ci->router->pathFor('index'));
	}

	/**
	 * @pattern /register
	 * @name register
	 * @method get|post
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function register(Request $request, Response $response, $args)
	{
		$data = array('errorMsg'=>'');
		$redirectUrl = $request->getParam('redirect_url', $this->ci->router->pathFor('login'));

		//已经登陆跳转
		if ($this->session->get('userInfo')) {
			return $response->withRedirect($redirectUrl);
		}
		if ($request->getMethod() == 'POST') {
			if ($request->getParam('_form') != $this->session->getFlashdata('_form')) {
				return $response->withRedirect($this->ci->router->pathFor('register'));
			}
			$email = $request->getParam('email');
			$password = $request->getParam('password');
			$repassword = $request->getParam('repassword');
			$data['email'] = $email;
			$data['password'] = $password;
			$data['repassword'] = $repassword;

			if(filter_var($email, FILTER_VALIDATE_EMAIL)){
				if (!User::where('email', $email)->first()) {
					if ($password && $password == $repassword) {
						$user = new User();
						$user->email = $email;
						$user->password = User::passwordHash($password);
						if ($user->save()) {
							//保存成功后写入ds服务器地址
                            UserNs::setDnsServer($user->uid);
                            return $response->withRedirect($redirectUrl);
                        }
						$this->log('alert', '注册失败', $request->getParams());
					} else {
						$data['errorMsg'] = '确认密码不一致！';
					}
				} else {
					$data['errorMsg'] = '该邮箱已经存在！';
				}
			} else {
				$data['errorMsg'] = '请输入正确的邮箱！';
			}

		}
		$data['_form'] = Functions::genRandStr(32);
		$this->session->setFlashdata('_form', $data['_form']);

		return $this->view('login/register.twig', $data);
	}

	/**
	 * @pattern /forgot
	 * @name forgot
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function forgot(Request $request, Response $response, $args)
	{

	}
}
