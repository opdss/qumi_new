<?php
/**
 * Api.php for qumi
 * @author SamWu
 * @date 2018/7/17 12:31
 * @copyright boyaa.com
 */
namespace App\Controllers;

use App\Libraries\Email;
use App\Models\DomainAccessLog;
use App\Models\DomainAccessLogCount;
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

	/**
	 * routes
	 * @pattern /realclicks/{logid}
	 * @name api.realclicks
	 * @method get
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function realclicks(Request $request, Response $response, $args) {
		$outtime = 10;
		$logid = isset($args['logid']) ? (int)$args['logid'] : 0;
		if ($logid && ($logModel = DomainAccessLog::select('domain_id', 'is_bot', 'is_real_clicks', 'created_at')->find($logid))) {
			if ($logModel->is_bot == 0 && $logModel->is_real_clicks == 0 && time() - strtotime($logModel->created_at) <= $outtime ) {
				DomainAccessLog::where('id', $logid)->update(['is_real_clicks'=>1]);
				DomainAccessLogCount::where('domain_id', $logModel->domain_id)->where('day', substr($logModel->created_at, 0, 10))->increment('real_clicks', 1);
				return $this->json(0);
			}
		}
		return $this->json(1);
	}
}