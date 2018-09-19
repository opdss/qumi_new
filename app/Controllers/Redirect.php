<?php
/**
 * Redirect.php for qumi
 * @author SamWu
 * @date 2018/8/27 11:41
 * @copyright boyaa.com
 */
namespace App\Controllers;

use App\Functions;
use App\Libraries\File;
use App\Models\Domain;
use App\Models\DomainRedirect;
use App\Models\Template;
use App\Models\Theme;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class Redirect
 * @middleware App\Middleware\Auth|App\Middleware\Rtime
 * @package App\Controllers
 */
class Redirect extends Base
{
	/**
	 * @pattern /redirect
	 * @name redirect
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function index(Request $request, Response $response, $args)
	{
		$data = array();
		$allowOrder = array('created_at', 'domain_name', 'redirect_url', 'redirect_status');
		$filter = [];
		$filter['kw'] = $request->getQueryParam('kw', '');
		$filter['page'] = (int)$request->getParam('page') ?: 1;
		$filter['domain_id'] = (int)$request->getParam('domain_id');
		$filter['redirect_status'] = (int)$request->getParam('redirect_status');
		$filter['order_by'] = $request->getQueryParam('order_by');

		$builder = \App\Models\DomainRedirect::isMy($this->uid);
		if ($filter['kw']){
			$builder = $builder->where('redirect_url', 'like', '%'.$filter['kw'].'%');
		}
		if ($filter['redirect_status'] > 0) {
			$builder = $builder->where('redirect_status', (string)$filter['redirect_status']);
		}
		if ($filter['domain_id'] > 0) {
			$builder = $builder->where('domain_id', $filter['domain_id']);
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
		//模板列表
		$data['currentName'] = $request->getAttribute('route')->getName();
		return $this->view('redirect/index.twig', $data);
	}

	/**
	 * @pattern /redirect/modal/{act}
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function modal(Request $request, Response $response, $args)
	{
		$act = $args['act'];
		$data = [];
		$data['domains'] = \App\Models\Domain::isMy($this->uid)->isDns()->get();
		if ($act == 'edit') {
			$id = (int)$request->getParam('redirect_id');
			if (!$id || !($records = \App\Models\DomainRedirect::find($id)) || $records->uid != $this->uid) {
				return 'id错误！';
			}
			$data['detail'] = $records;
			return $this->view('redirect/modal-edit.twig', $data);
		} elseif ($act == 'add') {
			return $this->view('redirect/modal-add.twig', $data);
		}
	}

	/**
	 * @pattern /redirect/add
	 * @method post
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return mixed
	 */
	public function add(Request $request, Response $response, $args)
	{
		$data['title'] = trim($request->getParsedBodyParam('title', ''));
		$data['prefix'] = trim($request->getParsedBodyParam('prefix',  ''));
		$data['domain_id'] = (int)$request->getParsedBodyParam('domain_id', 0);
		$data['redirect_url'] = trim($request->getParsedBodyParam('redirect_url', 0));
		$data['redirect_status'] = (int)$request->getParsedBodyParam('redirect_status', 0);
		$data['redirect_status'] = $data['redirect_status'] == 301 ? '301' : '302';

		if (!$data['title'] || !$data['prefix'] || !$data['domain_id'] || !$data['redirect_url']) {
			return $this->json(40001);
		}

		if (!Functions::verifyUrl($data['redirect_url'])) {
			$this->log('error', '['.__METHOD__.']非法redirect_url', $data);
			return $this->json(40001, '您输入的目标地址格式不对！');
		}

		if (!($domainModel = Domain::iCanUse($this->uid, $data['domain_id']))) {
			$this->log('error', '['.__METHOD__.']非法domain_id', $data);
			return $this->json(40001);
		}

		if (DomainRedirect::where('prefix', $data['prefix'])->where('domain_id', $data['domain_id'])->count() > 0) {
			$this->log('error', '['.__METHOD__.']已经存在该源地址跳转', $data);
			return $this->json(40001, '已经存在该源地址跳转');
		}

		$data['uid'] = $this->uid;
		$data['domain_name'] = $domainModel->name;

		if (\App\Models\DomainRedirect::insert($data)) {
			$this->updateRedirectConf();
			return $this->json(0);
		}
		$this->log('error', '['.__METHOD__.'] add error', $data);
		return $this->json(1);
	}

	/**
	 * @pattern /redirect/delete
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return mixed
	 */
	public function del(Request $request, Response $response, $args)
	{
		$errMsg = '';
		$domain_id = $request->getParam('redirect_id');
		$domain_ids = Functions::formatIds($domain_id, self::BATCH, $errMsg);
		if (!$domain_ids) {
			$this->log('error', $errMsg, $domain_id);
			return $this->json(40001, $errMsg);
		}

		$res = \App\Models\DomainRedirect::whereIn('id', $domain_ids)->isMy($this->uid)->delete();
		if ($res) {
			$this->updateRedirectConf();
			return $this->json(0);
		}
		$this->log('error', 'domain.del error', [$domain_ids]);
		return $this->json(1);
	}


	/**
	 * @pattern /redirect/update
	 * @method post
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function update(Request $request, Response $response, $args)
	{
		$id = intval($request->getParsedBodyParam('redirect_id'));
		if (!$id || !($current = \App\Models\DomainRedirect::find($id)) || $current->uid != $this->uid) {
			return $this->json(40001);
		}
		$data['title'] = trim($request->getParsedBodyParam('title', ''));
		$data['prefix'] = trim($request->getParsedBodyParam('prefix',  ''));
		$data['domain_id'] = (int)$request->getParsedBodyParam('domain_id', 0);
		$data['redirect_url'] = trim($request->getParsedBodyParam('redirect_url', 0));
		$data['redirect_status'] = (int)$request->getParsedBodyParam('redirect_status', 0);
		$data['redirect_status'] = $data['redirect_status'] == 301 ? '301' : '302';

		if (!$data['title'] || !$data['prefix'] || !$data['domain_id'] || !$data['redirect_url']) {
			return $this->json(40001);
		}

		if (!Functions::verifyUrl($data['redirect_url'])) {
			$this->log('error', '['.__METHOD__.']非法redirect_url', $data);
			return $this->json(40001, '您输入的目标地址格式不对！');
		}

		if (!($domainModel = Domain::iCanUse($this->uid, $data['domain_id']))) {
			$this->log('error', '['.__METHOD__.']非法domain_id', $data);
			return $this->json(40001);
		}

		$reModel = DomainRedirect::where('prefix', $data['prefix'])->where('domain_id', $data['domain_id'])->first();
		if ($reModel && $reModel->id != $id) {
			$this->log('error', '['.__METHOD__.']已经存在该源地址跳转', $data);
			return $this->json(40001, '已经存在该源地址跳转');
		}

		$data['domain_name'] = $domainModel->name;

		if (\App\Models\DomainRedirect::where('id', $id)->update($data)) {
			$this->updateRedirectConf();
			return $this->json(0);
		}
		$this->log('error', 'redirect.update error', ['id'=>$data]);
		return $this->json(1);
	}


	private function updateRedirectConf()
	{
		$res = DomainRedirect::all()->toArray();
		$data = [];
		foreach ($res as $item) {
			$data[$item['prefix'].'.'.$item['domain_name']] = [
				'url' => $item['redirect_url'],
				'status' => $item['redirect_status'],
			];
		}
		if (!File::writeRetPhp(CONFIG_DIR.'redirect.php', $data)) {
			$this->log('error', '['.__METHOD__.']写入redirect.php 失败:'.CONFIG_DIR.'redirect.php');
			return false;
		}
		return true;
	}
}