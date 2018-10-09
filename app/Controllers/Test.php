<?php
/**
 * Test.php for qumi
 * @author SamWu
 * @date 2018/9/25 15:09
 * @copyright boyaa.com
 */
namespace App\Controllers;

use App\Functions;
use App\Models\DomainAccessLog;
use App\Models\DomainAccessLogCount;
use App\Models\UserNs;
use Illuminate\Support\Facades\DB;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class User
 * @middleware App\Middleware\Auth|App\Middleware\Rtime
 * @package App\Controllers
 */
class Test extends Base
{

	/**
	 * @pattern /test
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function index(Request $request, Response $response, $args)
	{
		$r = '44422466';		$res = (explode(",", $r));
		$res = array_map('trim', $res);
		echo var_export($res, true);
//		var_dump($res);
		exit;
		var_dump(UserNs::getDnsServer(120));exit;
	   $res = Functions::getDomainDns('qq.com');
	   var_dump($res);
	}

}