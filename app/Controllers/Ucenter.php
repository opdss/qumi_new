<?php
/**
 * Ucenter.php for qumi
 * @author SamWu
 * @date 2018/7/11 10:35
 * @copyright boyaa.com
 */
namespace App\Controllers;

use App\Functions;
use App\Models\DomainAccessLog;
use App\Models\DomainAccessLogCount;
use Illuminate\Support\Facades\DB;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class User
 * @middleware App\Middleware\Auth|App\Middleware\Rtime
 * @package App\Controllers
 */
class Ucenter extends Base
{

	/**
	 * @pattern /ucenter
	 * @auth user|个人中心
	 * @name ucenter
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function index(Request $request, Response $response, $args)
	{
	    $data = [];
	    $data['info'] = [
	    	//当前域名总数
	    	'domainCount' => \App\Models\Domain::isMy($this->uid)->count(),
            'domainCounts' => \App\Models\Domain::isMy($this->uid)->selectRaw('count(*) as cc, suffix')->groupBy('suffix')->get()->toArray(),
			//访问总数
	    	'accessCount' => DomainAccessLog::isMy($this->uid)->count(),
			//有效访问总数
			'trueCount' => DomainAccessLog::isMy($this->uid)->count(),
			//今日访问总数
			'todayCount' => DomainAccessLog::isMy($this->uid)->count(),
		];
	    return $this->view('ucenter/index.twig', $data);
	}
}