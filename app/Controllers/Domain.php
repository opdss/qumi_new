<?php
/**
 * Domain.php for qumi
 * @author SamWu
 * @date 2018/7/12 15:08
 * @copyright boyaa.com
 */
namespace App\Controllers;

use App\Functions;
use App\Libraries\Config;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 *
 * Class Domain
 * @middleware App\Middleware\Auth|App\Middleware\Rtime
 * @package App\Controllers
 */
class Domain extends Base
{

	/**
	 * 我的域名列表
	 * @pattern /domain
     * @auth user|域名管理
	 * @name domain
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return mixed
	 */
	public function index(Request $request, Response $response, $args)
	{
		$data = [];
		$data['templates'] = \App\Models\Template::isMy($this->uid)->get();
		$data['coin_units'] = \App\Models\Domain::$coin_unit;
		return $this->view('domain/index.twig', $data);
	}

	/**
	 * 我的域名列表
	 * @pattern /api/domains
     * @auth user
	 * @name api.domain.get
	 * @method get
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

		$builder = \App\Models\Domain::isMy($this->uid);
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
			if ($order_type && $order_type != 'null' && in_array($order_name, array('id', 'name', 'price', 'dns_status'))) {
				$builder = $builder->orderBy($order_name, $order_type);
			}
			$data['records'] = $builder->offset(($page-1)*$limit)->limit($limit)->orderBy('id', 'desc')->get();
		}
		return $this->json($data);
	}

	/**
	 * 删除域名
	 * 支持批量删除
	 * @pattern /domain/delete
     * @auth user
	 * @name api.domain.del
	 * @method delete
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return bool
	 */
	public function del(Request $request, Response $response, $args)
    {
		$errMsg = '';
		$domain_id = $request->getParam('id');
		$domain_ids = Functions::formatIds($domain_id, self::BATCH, $errMsg);
        if (!$domain_ids) {
			$this->log('error', $errMsg, $domain_id);
        	return $this->json(3, $errMsg);
		}

		$res = \App\Models\Domain::whereIn('id', $domain_ids)->isMy($this->uid)->delete();
        if ($res) {
        	return $this->json(0);
		}
		$this->log('error', 'domain.del error', [$domain_ids]);
		return $this->json(1);

    }

    /**
     * 检查dns服务器来确认是否属于当前用户的域名
     * @pattern /api/domain/dnsCheck
     * @auth user
     * @name api.domain.dnscheck
     * @param Request $request
     * @param Response $response
     * @param $args
     */
    public function dnsCheck(Request $request, Response $response, $args)
    {
		$errMsg = '';
		$domain_id = $request->getParam('id');
		$domain_ids = Functions::formatIds($domain_id, self::BATCH, $errMsg);
		if (!$domain_ids) {
			$this->log('error', $errMsg, $domain_id);
			return $this->json(3, $errMsg);
		}

		$domains = \App\Models\Domain::whereIn('id', $domain_ids)->isMy($this->uid)->get()->toArray();
		if (empty($domains)) {
			$this->ci->logger->error('[uid:'.$this->uid.'] 非法域名id', $domain_ids);
			return $this->json(3);
		}
		//用户的ns服务器地址
        $userDns = array_merge(Config::site('godaddyDNS'), $this->userInfo['dns_server']);

		$result = [];
		$successIds = [];
		foreach ($domains as $item) {
            $check = false;
            if ($item['dns_status']) {
                $check = true;
            } else {
                //获取该域名的dns服务器
				$domainDns = Functions::getDomainDns($item['name']);
				if (!empty($domainDns)) {
					if (count(array_intersect($domainDns, $userDns)) == 2) {
						$check = true;
						$successIds[] = $item['id'];
					}
				}
            }
			$result[] = array(
				'domain_id' => $item['id'],
				'domain' => $item['name'],
				'dnsCheck' => $check
			);
		}
		if (!empty($successIds)) {
			//成功之后修改dns校验的状态字段
			if(!\App\Models\Domain::whereIn('id', $successIds)->update(array('dns_status'=>1))) {
				$this->log('error', 'update dns_status error', $successIds);
			}
		}
		return $this->json($result);
    }

	/**
	 * 添加域名
	 * @pattern /api/domain/add
     * @auth user
     * @name api.domain.add
	 * @method post
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return static
	 */
    public function add(Request $request, Response $response, $args)
	{
		$domains = Functions::formatDomains($request->getParsedBodyParam('domains'));
		if (empty($domains)) {
			return $this->json(3);
		}
		$description = trim($request->getParsedBodyParam('description', ''));
		$price = (int)$request->getParsedBodyParam('price', '');
        $price = $price > 0 ? $price : 0;
        $sale_type = $price ? 0 : 1;
		$unit = (int)$request->getParsedBodyParam('unit', '');
		$unit = $unit && in_array($unit, \App\Models\Domain::$coin_unit) ? $unit : \App\Models\Domain::COIN_UNIT_CNY;
		$buy_link = trim($request->getParsedBodyParam('buy_link', ''));
		$buy_link = $buy_link && Functions::verifyUrl($buy_link) ? $buy_link : '';

		//$sale_type = (int)$request->getParsedBodyParam('sale_type',  0);
		$template_id = (int)$request->getParsedBodyParam('template_id', 0);


		//获取用户已经添加的域名
		$existsArray = [];
		$exists = \App\Models\Domain::select('name')->whereIn('name', $domains)->isMy($this->uid)->get()->toArray();
		if ($exists) {
            $existsArray = array_map(function ($item){ return $item['name'];}, $exists);
        }
		$insertData = array();
		foreach ($domains as $domain) {
			//过滤掉已经存在的域名
		    if ($existsArray && in_array($domain, $existsArray)) {
		        continue;
            }
			$insertData[] = array(
				'uid'=>$this->uid,
				'name'=>$domain,
				'description' => $description,
				'price' => $price,
				'sale_type'=>$sale_type,
				'suffix' => substr($domain, strpos($domain, '.')+1),
				'template_id'=>$template_id,
				'dns_status' => 0,
				'dtype' => $this->getDtype($domain),
				'unit' => $unit,
				'buy_link' => $buy_link,
				'len' => strpos($domain, '.')
			);
		}
		if (!$insertData || \App\Models\Domain::insert($insertData)) {
			return $this->json(0);
		}
		return $this->json(1);
	}


	/**
	 * 更新域名信息
	 * 支持批量
	 * @pattern /domain/update
     * @auth user
	 * @name api.domain.update
	 * @method post
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function update(Request $request, Response $response, $args)
	{
		$domain_id = $request->getParsedBodyParam('id', null);
		$allow_fields = ['description' => 'trim', 'price' => 'intval', 'template_id' => 'intval'];
		$updateData = [];
		foreach (array_keys($allow_fields) as $key) {
			$_res = $request->getParsedBodyParam($key, null);
			$_res && $updateData[$key]  = $_res;
		}

		if (!$domain_id) {
			return $this->json(3);
		}
		//如果是修改一个的话，包装成数组交给下面去处理
		if (is_numeric($domain_id)) {
			if (!$domain_id = intval($domain_id)) {
				return $this->json(3);
			}
			$domain_id = [$domain_id];
			foreach ($updateData as $key => $val) {
				$updateData[$key] = [$val];
			}
		}
		//数据清洗处理
		if (is_array($domain_id)) {
			//获取属于用户的域名
			$existsIds = [];
			$exists = \App\Models\Domain::select('id')->whereIn('id', $domain_id)->isMy($this->uid)->get()->toArray();
			if ($exists) {
				$existsIds = array_map(function ($item){ return $item['id'];}, $exists);
			}
			if (empty($existsIds)) {
				//应该是恶意修改
				$this->log('error', 'update domain error', $request->getParams());
				return $this->json(3, '你要修改的域名有问题！');
			}
			$data = [];
			foreach ($domain_id as $k => $_domain_id) {
				//只能修改属于的我的域名
				if (in_array($_domain_id, $existsIds)) {
					$one['id'] = $_domain_id;
					foreach ($updateData as $key => $val) {
						if ($val) {
							$one[$key] = call_user_func($allow_fields[$key], $val[$k]);
						}
					}
					if (isset($one['price'])) {
						$one['sale_type'] = $one['price'] ? 1 : 0;
					}
					$data[] = $one;
				}
			}
			if (\App\Models\Domain::updateBatch($data, 'id')) {
				return $this->json(0);
			}
		}
		return $this->json(1);
	}

	/**
	 * @pattern /domain/modal/{act}
     * @auth user
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
    public function modal(Request $request, Response $response, $args)
	{
		$act = $args['act'];
		$data = [];
		$data['templates'] = \App\Models\Template::isMy($this->uid)->get();
        $data['coin_units'] = \App\Models\Domain::$coin_unit;
		if ($act == 'edit') {
			$errMsg = '';
			$domain_id = $request->getParam('id');
			$domain_ids = Functions::formatIds($domain_id, self::BATCH, $errMsg);
			if (!$domain_ids) {
				$this->log('error', $errMsg, $domain_id);
				return $errMsg;
			}
			if (!$records = \App\Models\Domain::whereIn('id', $domain_ids)->isMy($this->uid)->get()) {
				//传递了非法域名id
				$this->log('error', '非法域名id', $domain_id);
				return '非法域名id';
			}
			if (count($records) == 1) {
				//修改一个
				$data['domain'] = $records[0];
				return $this->view('domain/modal-edit-one.twig', $data);
			} else {
				//修改多个
				$data['records'] = $records;
				return $this->view('domain/modal-edit-more.twig', $data);
			}
		} elseif ($act == 'add') {
			return $this->view('domain/modal-add.twig', $data);
		}
	}

	private function getDtype($domain)
	{
		if (preg_match('/^[a-zA-Z]+\.[a-zA-Z]+$/', $domain)) {
			$dtype = 1;
		} elseif (preg_match('/^[0-9]+\.[a-zA-Z]+$/', $domain)) {
			$dtype = 2;
		} else {
			$dtype = 3;
		}
		return $dtype;
	}
}