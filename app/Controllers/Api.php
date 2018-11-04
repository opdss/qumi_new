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