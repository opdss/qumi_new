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
	 * @pattern /captcha/{key}
	 * @param Request $request
	 * @param Response $response
	 * @param array $args
	 * @return static
	 */
	public function index(Request $request, Response $response, array $args)
	{
		$width = (int)$request->getQueryParam('w') ?: 150;
		$height = (int)$request->getQueryParam('h') ?: 40;
		$captchaName = $args['key'] ?: 'captcha';

		$builder = new CaptchaBuilder();
		$p = new PhraseBuilder();
		$builder->setPhrase($p->build(4));
		$builder->setBackgroundColor('240', '240', '240')->build($width, $height);
		$captcha = $builder->getPhrase();//验证码
		$this->sessCaptcha($captchaName, $captcha);
		$body = $response->getBody();
		$body->write($builder->get());
		return $response->withHeader('Content-type', 'image/jpeg')->withBody($body);
	}
}