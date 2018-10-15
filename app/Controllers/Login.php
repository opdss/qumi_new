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
        $data = [];

        $redirectUrl = $request->getParam('redirect_url');
        $redirectUrl AND $this->session->set('redirect_url', $redirectUrl);
        //已经登陆跳转
        if ($this->session->get('userInfo')) {
            return $response->withRedirect($this->ci->router->pathFor('index'));
        }
		return $this->view('login/loginNew.twig', $data);
	}

    /**
     * @pattern /api/login
     * @method post
     * @name api.login
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
	public function loginApi(Request $request, Response $response, $args)
    {
        $email = trim($request->getParsedBodyParam('email'));
        $password = trim($request->getParsedBodyParam('password'));
        if (!$userObj = User::login($email, $password)) {
            $this->log('alert', '登陆验证失败', $request->getParams());
            return $this->json('3', '用户名或者密码错误！');
        }
        $userInfo = $userObj->toArray();
        //更新登陆相关的信息
        $userObj->login_time = date('Y-m-d H:i:s');
        $userObj->login_ip = Functions::getIP();
        $userObj->login_num += 1;
        $userObj->save();
        //获取处理用户的ns，放到session
        $userInfo['dns_server'] = UserNs::getDnsServer($userInfo['uid']);
        $userInfo['dns_server_str'] = implode('<br/>',$userInfo['dns_server']);
        //测试 end
        $this->session->set('userInfo', $userInfo);

        $redirectUrl = $this->session->get('redirect_url');
        if ($redirectUrl && preg_match('/\/login|\/logout|\/forget|\/register/', $redirectUrl)) {
            $redirectUrl = $this->ci->router->pathFor('index');
        }
        !$redirectUrl AND $redirectUrl =  $this->ci->router->pathFor('index');
        return $this->json(array('redirect_url'=>$redirectUrl));
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
	    $data = [];
		//已经登陆跳转
		if ($this->session->get('userInfo')) {
			return $response->withRedirect($this->ci->router->pathFor('index'));
		}
        $data['captchaImg'] = $this->getCaptchaImg($this->getCaptchaKey('register'), 110, 38);
		return $this->view('login/register.twig', $data);
	}

    /**
     * @pattern /api/register
     * @name api.login.register
     * @method post
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
	public function registerApi(Request $request, Response $response, $args)
    {
        $email = trim($request->getParsedBodyParam('email'));
        $password = trim($request->getParsedBodyParam('password'));
        $repassword = trim($request->getParsedBodyParam('repassword'));
        $captcha = trim($request->getParsedBodyParam('captcha'));

        $localCaptcha = $this->sessCaptcha($this->getCaptchaKey('register'));
        if (!$captcha || strtolower($captcha) != $localCaptcha) {
            return $this->json('3', '您输入的验证码不正确!');
        }

        if (!Functions::verifyEmail($email)) {
            return $this->json('3', '您输入的邮箱不正确!');
        }

        if (User::where('email', $email)->count() > 0) {
            return $this->json('3', '您输入的邮箱已经被注册，请重新输入!');
        }

        if (!Functions::verifyPasswd($password)) {
            return $this->json('3', '您输入的密码格式不正确，请输入6-20位密码');
        }

        if ($password !== $repassword) {
            return $this->json('3', '您两次输入的密码不一致，请重新输入！');
        }

        $user = new User();
        $user->email = $email;
        $user->password = User::passwordHash($password);
        if (!$user->save()) {
            $this->log('alert', '注册失败', $request->getParams());
            return $this->json(1);
        }
        //保存成功后写入ds服务器地址
        UserNs::setDnsServer($user->uid);
        return $this->json(0);
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
		//已经登陆跳转
        if ($this->session->get('userInfo')) {
            return $response->withRedirect($this->ci->router->pathFor('index'));
        }
		$data['captchaImg'] = $this->getCaptchaImg($this->getCaptchaKey('forget'), 150, 60);
		return $this->view('login/forget.twig', $data);
	}

	/**
	 * @pattern /api/sendemailcode
	 * @method post
	 * @name api.login.sendemailcode
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function sendEmailCode(Request $request, Response $response, $args)
	{
		$sessCaptcha = $this->sessCaptcha($this->getCaptchaKey('forget'));
		$userCaptcha = trim($request->getParsedBodyParam('captcha'));
		$email = trim($request->getParsedBodyParam('email'));
		if (!$userCaptcha || strtolower($userCaptcha) !== $sessCaptcha) {
			$this->log('debug', '找回密码-验证码错误！', [$email, $sessCaptcha, $userCaptcha]);
			return $this->json(3, '您输入的验证码错误！');
		}
		if (!Functions::verifyEmail($email) || !($userModel = User::where('email', $email)->first())
		) {
			$this->log('error', '找回密码-获取验证码邮箱错误！', [$email, $userCaptcha]);
			return $this->json(3, '您输入的邮箱有误！');
		}
		$emailCode = Functions::genRandStr(6, false);
		$expire = 600;
		$this->findPasswdCode($email, $emailCode, $expire);
		$body = "您好，${email}，您正在使用找回密码功能，验证码：${emailCode} , ".($expire/60)."分钟内有效。";
		$data = ['to'=>$email, 'body'=>$body, 'subject'=>'趣米停靠站-找回密码', 'level'=>10];
		$flag = Email::factory()->insertQueue($data);
		if (!$flag) {
			$this->log('error', '插入邮件队列失败！', $data);
		}
		return $this->json($flag ? 0 : 1);
	}

    /**
     * @pattern /api/updatepasswd
     * @method post
     * @name api.login.updatepasswd
     * @param Request $request
     * @param Response $response
     * @param $args
     */
	public function updatePasswd(Request $request, Response $response, $args)
    {
        $email = trim($request->getParsedBodyParam('email'));
        $code = trim($request->getParsedBodyParam('code'));
        $password = trim($request->getParsedBodyParam('password'));
        $repassword = trim($request->getParsedBodyParam('repassword'));

        if (!Functions::verifyEmail($email) || !($userModel = User::where('email', $email)->first())) {
            return $this->json('3', '您输入的邮箱错误！');
        }

        $localCode = $this->findPasswdCode($email);
        if (!$localCode) {
            return $this->json('3', '您输入的邮箱验证码已过期，请重新获取！');
        }

        if ($localCode !== $code) {
            return $this->json('3', '您输入的邮箱验证码错误，请重新输入！');
        }

        if (!Functions::verifyPasswd($password)) {
            return $this->json('3', '您输入的密码格式不正确，请输入6-20位密码');
        }

        if ($password !== $repassword) {
            return $this->json('3', '您两次输入的密码不一致，请重新输入！');
        }

        $flag = $userModel->resetPassword($password);

        return $this->json($flag ? 0 : 1);
    }

    private function getCaptchaKey($act)
    {
        return $act.'_capt';
    }

    /**
     * 设置获取找回密码的code
     * @param $email
     * @param null $code
     * @param int $timeout
     * @return mixed
     */
	private function findPasswdCode($email, $code = null, $timeout = 120)
    {
        $cacheKey = 'fck_'.$email;
        if ($code === null) {
            return $this->ci->cache->get($cacheKey);
        } else {
            return $this->ci->cache->save($cacheKey, $code, $timeout);
        }

    }

}
