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
	 * @name ucenter
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function index(Request $request, Response $response, $args)
	{
	    $data = [];
	    $data['info'] = [
	    	'domainCount' => \App\Models\Domain::isMy($this->uid)->count(),
	    	'accessCount' => DomainAccessLog::isMy($this->uid)->count(),
		];
	    $this->addJs('/statics/js/echarts.min.js');
        $data['currentName'] = $request->getAttribute('route')->getName();
	    return $this->view('ucenter/index.twig', $data);
	}
}