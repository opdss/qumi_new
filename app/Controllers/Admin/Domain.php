<?php
/**
 * Domain.php for qumi
 * @author SamWu
 * @date 2018/9/12 15:59
 * @copyright boyaa.com
 */
namespace App\Controllers\Admin;

use App\Controllers\Base;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class Domain
 * @middleware App\Middleware\Auth|App\Middleware\Rtime
 * @package App\Controllers\Admin
 */
class Domain extends Base {

	/**
	 * 我的域名列表
	 * @pattern /admin/domain
	 * @auth admin|域名管理
	 * @name admin.domain
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return mixed
	 */
	public function index(Request $request, Response $response, $args)
	{
		$data = [];
		return $this->view('admin/domain/index.twig', $data);
	}

	/**
	 * @pattern /api/admin/domains
	 * @auth admin
	 * @name api.admin.domain.get
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return mixed
	 */
	public function get(Request $request, Response $response, $args)
	{

		$data = array();
		$page = (int)$request->getParam('page') ?: 1;
		$kw = trim($request->getQueryParam('kw', ''));
		$template_id = (int)$request->getParam('template_id');
		$dns_status = (int)$request->getParam('dns_status');
		$order_name = trim($request->getQueryParam('order_name'));
		$order_type = trim($request->getQueryParam('order_type'));
		$limit = (int)$request->getQueryParam('limit') ?: self::$page_number;
		$limit = min($limit, 100);

		$builder = new \App\Models\Domain();
		if ($kw !== ''){
			$builder = $builder->where('name', 'like', '%'.$kw.'%');
		}
		if ($dns_status > 0) {
			$builder = $builder->where('dns_status', $dns_status-1);
		}
		if ($template_id > 0) {
			$builder = $builder->where('template_id', $template_id);
		}

		$data['count'] = $builder->count();
		$data['records'] = [];
		if ($data['count']) {
			if ($order_type && $order_type != 'null' && in_array($order_name, array('id', 'uid', 'name', 'price', 'dns_status'))) {
				$builder = $builder->orderBy($order_name, $order_type);
			}
			$data['records'] = $builder->offset(($page-1)*$limit)->limit($limit)->orderBy('id', 'desc')->get();
		}
		return $this->json($data);
	}
}