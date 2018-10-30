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
use App\Models\User;
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
	    	'accessCount' => DomainAccessLog::isMy($this->uid)->count(),
			//有效访问总数
			'trueCount' => DomainAccessLog::isMy($this->uid)->count(),
			//今日访问总数
			'todayCount' => DomainAccessLog::isMy($this->uid)->count(),
		];
	    return $this->view('ucenter/index.twig', $data);
	}

    /**
     * @pattern /ucenter/reset
     * @method post
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
	public function reset(Request $request, Response $response, $args)
    {
        $type = trim($request->getParsedBodyParam('type', ''));
        $val = trim($request->getParsedBodyParam('val', ''));
        if (!$type || !$val) {
            return $this->json(3);
        }
        if ($type == 'email') {
            if (!Functions::verifyEmail($val)) {
                return $this->json(3, '邮箱格式错误！');
            }
            if (User::where('email', $val)->count() > 0) {
                return $this->json(3, '此邮箱已经被使用！');
            }
            if (User::where('uid', $this->uid)->update(array('email', $val))) {
                return $this->json(0);
            }
        }
        else if ($type == 'passwd') {
            if (!Functions::verifyPasswd($val)) {
                return $this->json(3);
            }
            if (User::find($this->uid)->resetPasswd($val)) {
                return $this->json(0);
            }
        }
        $this->log('error', '重置'.$type.'错误', [$type, $val]);
		$this->session->destroy();
        return $this->json(1);
    }

}