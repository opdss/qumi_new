<?php
/**
 * Captcha.php for mySSO.
 * @author SamWu
 * @date 2017/7/4 14:08
 * @copyright istimer.com
 */

namespace App\Controllers;

use Gregwar\Captcha\PhraseBuilder;
use Slim\Http\Request;
use Slim\Http\Response;
use Gregwar\Captcha\CaptchaBuilder;

class Captcha extends Base
{
	/**
	 * url直接输出验证码
     *
	 * @pattern /captcha[/{w:[0-9]+}[/{h:[0-9]+}]]
	 * @param Request $request
	 * @param Response $response
	 * @param array $args
	 * @return static
	 */
	public function index(Request $request, Response $response, array $args)
	{
		$width = !empty($args['w']) ? (int)$args['w'] : ((int)$request->getQueryParam('w') ?: 150);
		$height = !empty($args['h']) ? (int)$args['h'] : ((int)$request->getQueryParam('h') ?: 40);
		$captchaName = $request->getQueryParam('k') ?: 'captcha';

		$builder = new CaptchaBuilder();
		$p = new PhraseBuilder();
		$builder->setPhrase($p->build(4));
		$builder->setBackgroundColor('240', '240', '240')->build($width, $height);
		$captcha = $builder->getPhrase();//验证码
		$this->setCaptcha($captchaName, $captcha);
		$body = $response->getBody();
		$body->write($builder->get());
		return $response->withHeader('Content-type', 'image/jpeg')->withBody($body);
	}

	public function setCaptcha($k, $captcha)
	{
		//将验证码存入session
		$this->session->setFlashdata($k, strtolower($captcha));
	}

	public function getCaptcha($k)
	{
		return $this->session->getFlashdata($k);
	}
}