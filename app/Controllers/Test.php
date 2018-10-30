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
		$r = '16148910,
38525910,
16614800,
30356255,
39170158,
43286936,
34534337,
24054306,
38043511,
43949377,
43642526,
36965990,
36756367,
36756439,
34556492,
43741125,
34557396,
34556545,
36644414,
36965934,
36741764,
43612057,
23157542,
34534229,
34558527,
44050785,
34884747,
44626363,
40201572,
44626439,
44776646,
43510368,
36713396,
44814194,
44019084,
44813882,
38525910,
16614800,
21670540,
19686023,
17758984,
22548361,
24054306,
43901656,
36472654,
44605417,
44023349,
25778219,
44133242
';		$res = (explode(",", $r));
		$res = array_map('trim', $res);
		echo var_export($res, true);
//		var_dump($res);
		exit;
		var_dump(UserNs::getDnsServer(120));exit;
	   $res = Functions::getDomainDns('qq.com');
	   var_dump($res);
	}

}