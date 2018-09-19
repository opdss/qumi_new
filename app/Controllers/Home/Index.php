<?php
/**
 * Index.php for qumi
 * @author SamWu
 * @date 2018/9/14 12:34
 * @copyright boyaa.com
 */
namespace App\Controllers\Home;

use App\Controllers\Base;
use App\Functions;
use App\Models\Domain;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class Feedback
 * @middleware App\Middleware\Rtime
 * @package App\Controllers\Home
 */
class Index extends Base
{
	/**
	 * @pattern /domains
	 * @method post|get
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return mixed
	 */
	public function domains(Request $request, Response $response, $args)
	{
		$allowOrder = array('name', 'price', 'len', 'dtype', 'suffix');
		$filter = [];
		$filter['kw'] = $request->getQueryParam('kw', '');
		$filter['page'] = (int)$request->getParam('page') ?: 1;
		$filter['suffix'] = $request->getParam('suffix');
		$filter['dtype'] = (int)$request->getParam('dtype');
		$filter['len'] = (int)$request->getQueryParam('len');
		$filter['order_by'] = $request->getQueryParam('order_by');

		$builder = new Domain();
		$builder = $builder->where('dns_status', 1);
		if ($filter['dtype']) {
			$builder = $builder->where('dtype', $filter['dtype']);
		}
		if ($filter['len']) {
			$builder = $builder->where('len', $filter['len']);
		}
		if ($filter['suffix']) {
			$builder = $builder->where('suffix', $filter['suffix']);
		}

		if ($filter['kw']) {
			$builder = $builder->where('name', 'like', '%'.$filter['kw'].'%');
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
			$data['records'] = $builder->offset(($filter['page']-1)*self::$page_number)->limit(self::$page_number)->orderBy('id', 'desc')->get();
			$data['pagination'] = Functions::pagination($data['count'], self::$page_number);
		}
		$data['suffixs'] = Domain::getAllSuffix();
		$data['lens'] = Domain::getAllLen();
		$data['filterObj'] = json_encode($filter);
		return $this->view('home/index/domains.twig', $data);
	}
}