<?php
/**
 * Login.php for qumi
 * @author SamWu
 * @date 2018/7/9 17:52
 * @copyright boyaa.com
 */
namespace App\Controllers;

use App\Functions;
use App\Libraries\Email;
use App\Models\User;
use App\Models\UserNs;
use Slim\Http\Request;
use Slim\Http\Response;

class Login extends Base
{

	const SECC = 'sendEmailCaptchaKey';
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
                Email::factory()->insertQueue('opdss@qq.com', '我登陆了', '登陆通知');
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
	 * @pattern /forget
	 * @name forget
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function forget(Request $request, Response $response, $args)
	{
		$data = [];
		$data['captchaImg'] = $this->getCaptchaImg(self::SECC, 150, 60);
		return $this->view('login/forget.twig', $data);
	}

	/**
	 * @pattern /api/sendemailcode
	 * @method post
	 * @name api.sendemailcode
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function sendEmailCode(Request $request, Response $response, $args)
	{
		$sessCaptcha = $this->sessCaptcha(self::SECC);
		$userCaptcha = trim($request->getParsedBodyParam('captcha'));
		$email = trim($request->getParsedBodyParam('email'));
		if (!$userCaptcha || strtolower($userCaptcha) !== $sessCaptcha) {
			$this->log('debug', '找回密码-验证码错误！', [$email, $sessCaptcha, $userCaptcha]);
			return $this->json(40001, '您输入的验证码错误！');
		}
		if (
			!$email
			|| !filter_var($email, FILTER_VALIDATE_EMAIL)
			|| !($userModel = User::where('email', $email)->first())
		) {
			$this->log('error', '找回密码-获取验证码邮箱错误！', [$email, $userCaptcha]);
			return $this->json(40001, '您输入的邮箱有误！');
		}
		$emailCode = Functions::genRandStr(6);
		$body = "您好，${email}，您正在使用找回密码功能，验证码：${emailCode} 。";
		$data = ['to'=>$email, 'body'=>$body, 'subject'=>'趣米停靠站-找回密码', 'level'=>10];
		$flag = Email::factory()->insertQueue($data);
		if (!$flag) {
			$this->log('error', '插入邮件队列失败！', $data);
		}
		return $this->json($flag ? 0 : 1);
	}

}
