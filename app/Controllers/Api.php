<?php
/**
 * Api.php for qumi
 * @author SamWu
 * @date 2018/7/17 12:31
 * @copyright boyaa.com
 */
namespace App\Controllers;

use App\Libraries\Email;
use Slim\Http\Request;
use Slim\Http\Response;

class Api extends Base
{

	/**
	 * @pattern /offer/{domain_id}
	 * @name api.offer
	 * @method post
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return mixed
	 */
	public function offer(Request $request, Response $response, $args) {
		$domain_id = isset($args['domain_id']) ? (int)$args['domain_id'] : 0;
		$captcha = $request->getParsedBodyParam('captcha', 'null');

		if (!$captcha) {
			return $this->json(3);
		}

		if (!$domain_id || !($domainModel = \App\Models\Domain::with('user')->find($domain_id))) {
			$this->log('error', __METHOD__.' => 非法域名id', [$domain_id, $request->getParsedBody()]);
			return $this->json(3);
		}
		$price = $request->getParsedBodyParam('price');
		$content = $request->getParsedBodyParam('content');
		$email = $request->getParsedBodyParam('email');

		$body = '你好，有人对你的域名('.$domainModel->name.')很感兴趣，报价：'.$price.'，并留言：'.$content.'，他的邮箱是：'.$email.'，有空联系吧，祝老板交易成功';
		if (!Email::factory()->insertQueue($domainModel->user->email, $body, '客户来啦！')) {
			return $this->json(1);
		}
		return $this->json(0);
	}
}