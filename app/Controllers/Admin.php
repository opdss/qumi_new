<?php
/**
 * Admin.php for qumi
 * @author SamWu
 * @date 2018/8/8 17:38
 * @copyright boyaa.com
 */
namespace App\Controllers;

use App\Functions;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 *
 * Class Admin
 * @middleware App\Middleware\Auth|App\Middleware\Rtime
 * @package App\Controllers
 */
class Admin extends Base
{
	/**
	 * 我的域名列表
	 * @pattern /admin/userss
	 * @name admin.user
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return mixed
	 */
	public function user(Request $request, Response $response, $args)
	{
		if (!$this->checkRole()) {
			return $this->view('404.twig');
		}
		$data = array();
		$allowOrder = array('uid', 'email');
		$filter = [];
		$filter['kw'] = $request->getQueryParam('kw', '');
		$filter['page'] = (int)$request->getParam('page') ?: 1;
		$filter['order_by'] = $request->getQueryParam('order_by');

		$builder = new \App\Models\User();
		if ($filter['kw']) {
			$builder = $builder->where('email', 'like', '%' . $filter['kw'] . '%');
		}

		$data['filter'] = $filter;
		$data['count'] = $builder->count();
		$data['records'] = [];
		if ($data['count']) {
			if ($filter['order_by'] && is_array($filter['order_by'])) {
				foreach ($filter['order_by'] as $k => $v) {
					$v = $v == 'asc' ? 'asc' : 'desc';
					if (in_array($k, $allowOrder)) {
						$builder = $builder->orderBy($k, $v);
					}
				}
			}
			$data['records'] = $builder->offset(($filter['page'] - 1) * self::$page_number)->limit(self::$page_number)->get()->toArray();
			$data['records'] = array_map(function ($arr){
				$arr['domain_count'] = \App\Models\Domain::where('uid', $arr['uid'])->count();
				return $arr;
			}, $data['records']);
			$data['pagination'] = Functions::pagination($data['count'], self::$page_number);
		}
		//模板列表
		$data['currentName'] = $request->getAttribute('route')->getName();
		return $this->view('admin/user.twig', $data);
	}

}