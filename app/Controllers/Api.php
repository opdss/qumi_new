<?php
/**
 * Api.php for qumi
 * @author SamWu
 * @date 2018/7/17 12:31
 * @copyright boyaa.com
 */
namespace App\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;

class Api extends Base
{
	/**
	 * @pattern /api/domains
	 * @middleware App\Middleware\Auth
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return mixed
	 */
	public function domain(Request $request, Response $response, $args)
	{
		$domain_id = $request->getQueryParam('domain_id');
		if ($domain_id) {
			if(is_string($domain_id) && strpos($domain_id, ',') !== false) {
				$domain_id = explode(',', $domain_id);
			}

			$domain_ids = [];
			if (is_numeric($domain_id)) {
				$domain_id>0 AND $domain_ids[] = $domain_id;
			} elseif(is_array($domain_id)) {
				$domain_ids = array_filter($domain_id, function($item){
					if (is_numeric($item) && $item > 0) {
						return true;
					}
					return false;
				});
			}

			if (!empty($domain_ids)) {
				$records = \App\Models\Domain::whereIn('id', $domain_ids)->where('uid', $this->userInfo['uid'])->toArray();
				return $this->json(count($domain_ids) == 1 ? $records[0] : $records);
			}
			return $this->json(40001);
		} else {
			$filter = [];
			$filter['kw'] = $request->getQueryParam('kw', '');
			$filter['page'] = (int)$request->getParam('page');
			$page = $filter['page'] ? $filter['page'] : 1;
			$number = 20;
			$filter['template_id'] = (int)$request->getParam('template_id');
			$filter['dns_status'] = (int)$request->getParam('dns_status');

			$builder = new \App\Models\Domain();
			if ($filter['kw']){
				$builder = $builder->where('name', 'like', '%'.$filter['kw'].'%');
			}
			if ($filter['dns_status'] >= 0) {
				$builder = $builder->where('dns_status', $filter['dns_status']);
			}
			if ($filter['template_id'] > 0) {
				$builder = $builder->where('template_id', $filter['template_id']);
			}

			$builder = $builder->where('uid', $this->userInfo['uid']);

			$filter['count'] = $builder->count();
			$records = [];
			if ($filter['count']) {
				$records = $builder->offset(($page-1)*$number)->limit($number)->get();
			}

			return $this->json(array('pageInfo'=>$filter, 'records'=>$records));
		}
	}

	/**
	 * @pattern /api/templatess
	 * @middleware App\Middleware\Auth
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return mixed
	 */
	public function template(Request $request, Response $response, $args)
	{
		$template_id = (int)$request->getQueryParam('template_id');
		if ($template_id) {
			$records = \App\Models\Template::where('id', $template_id)->where('uid', $this->userInfo['uid'])->first()->toArray();
			return $this->json($records);
		} else {
			$filter = [];
			$filter['kw'] = $request->getQueryParam('kw', '');
			$filter['page'] = (int)$request->getParam('page');
			$page = $filter['page'] ? $filter['page'] : 1;
			$number = 20;

			$builder = new \App\Models\Template();
			if ($filter['kw']){
				$builder = $builder->where('name', 'like', '%'.$filter['kw'].'%');
			}

			$builder = $builder->where('uid', $this->userInfo['uid']);

			$filter['count'] = $builder->count();
			$records = [];
			if ($filter['count']) {
				$records = $builder->offset(($page-1)*$number)->limit($number)->get();
			}

			return $this->json(array('pageInfo'=>$filter, 'records'=>$records));
		}
	}
}