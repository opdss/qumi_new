<?php
/**
 * Feedback.php for qumi
 * @author SamWu
 * @date 2018/9/13 17:19
 * @copyright boyaa.com
 */
namespace App\Controllers\Home;

use App\Controllers\Base;
use App\Functions;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class Feedback
 * @middleware App\Middleware\Auth|App\Middleware\Rtime
 * @package App\Controllers\Home
 */
class Feedback extends Base
{
	/**
	 * @pattern /feedback
	 * @method post|get
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return mixed
	 */
	public function index(Request $request, Response $response, $args)
	{
		$data = array('errorMsg'=>'');

		$data['captchaKey'] = 'fbkey';
		$data['captchaImg'] = $this->getCaptchaImg($data['captchaKey'], 140, 40);

		$redirectUrl = $request->getParam('redirect_url', $this->ci->router->pathFor('index'));

		if ($request->getMethod() == 'POST') {
			//csrf

			if ($request->getParam('_form') != $this->session->getFlashdata('_form')) {
				//return $response->withRedirect($this->ci->router->pathFor('home.feedback.index'));
			}
			$title = trim($request->getParam('title'));
			$content = trim($request->getParam('content'));
			$captcha = trim($request->getParam('captcha'));
			$cache_captcha = $this->session->getFlashdata($data['captchaKey']);

			if (!$captcha || $captcha != $cache_captcha) {
				$this->log('alert', '验证码错误', $request->getParams());
				$data['title'] = $title;
				$data['content'] = $content;
				$data['errorMsg'] = '验证码错误！';
			} else {
				$flag = true;
				if (!$title || strlen($title) > 100) {
					$flag = false;
					$data['errorMsg'] = '标题最长100个字！';
				}
				if (!$content || strlen($content) > 500) {
					$flag = false;
					$data['errorMsg'] = '内容最长500个字！';
				}
				if ($flag) {
					$insertData = [
						'uid' => $this->uid,
						'title' => $title,
						'content' => $content,
					];
					if (\App\Models\Feedback::insert($insertData)) {
						return '<script>alert("提交成功，非常感谢！");location.href="'.$redirectUrl.'"</script>';
					} else {
						$this->log('alert', '插入feedback出错', $request->getParams());
						$data['errorMsg'] = '系统出错，请重试！';
					}
				}
			}
		}
		$data['redirect_url'] = $redirectUrl;
		$data['_form'] = Functions::genRandStr(32);
		$this->session->setFlashdata('_form', $data['_form']);


		return $this->view('home/feedback/index.twig', $data);
	}
}