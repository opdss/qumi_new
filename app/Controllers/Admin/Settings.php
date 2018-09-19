<?php
/**
 * Settings.php for qumi_new
 * @author SamWu
 * @date 2018/9/11 18:37
 * @copyright boyaa.com
 */
namespace App\Admin\Controllers;

use App\Controllers\Base;
use App\Functions;
use App\Models\SiteConf;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 *
 * Class Admin
 * @middleware App\Middleware\Auth|App\Middleware\Rtime
 * @package App\Controllers
 */
class Settings extends Base
{
	/**
	 * 系统设置
	 * @pattern /admin/setting
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return mixed
	 */
	public function index(Request $request, Response $response, $args)
	{
		if (!$this->checkRole()) {
			return $this->view('404.twig');
		}
		$res = SiteConf::all();
		$data['records'] = $res ? $res->toArray() : array();
		return $this->view('admin/settings/index.twig', $data);
	}
}