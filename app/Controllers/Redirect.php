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
 * @middleware App\Middleware\Rtime
 * @package App\Controllers
 */
class Redirect extends Base
{
	/**
	 * @pattern /redirect
	 * @auth user|域名转发
	 * @name redirect
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function index(Request $request, Response $response, $args)
	{
		$data = array();
        $data['domains'] = \App\Models\Domain::isMy($this->uid)->isDns()->get();
		return $this->view('redirect/index.twig', $data);
	}

	/**
	 * @pattern /api/redirects
	 * @name api.redirect.get
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return mixed
	 */
	public function get(Request $request, Response $response, $args)
	{
		$data = array();
		$kw = trim($request->getQueryParam('kw', ''));
		$page = (int)$request->getParam('page') ?: 1;
		$domain_id = (int)$request->getParam('domain_id');
		$redirect_status = (int)$request->getParam('redirect_status');
		$order_name = trim($request->getQueryParam('order_name'));
		$order_type = trim($request->getQueryParam('order_type'));
		$limit = min((int)$request->getQueryParam('limit') ?: self::$page_number, 100);

		$builder = \App\Models\DomainRedirect::isMy($this->uid);
		if ($kw){
			$builder = $builder->where('redirect_url', 'like', '%'.$kw.'%');
		}
		if ($redirect_status > 0) {
			$builder = $builder->where('redirect_status', (string)$redirect_status);
		}
		if ($domain_id > 0) {
			$builder = $builder->where('domain_id', $domain_id);
		}

		$data['count'] = $builder->count();
		$data['records'] = [];
		if ($data['count']) {
			if ($order_type && $order_type != 'null' && in_array($order_name, array('created_at', 'title', 'domain_name', 'redirect_url', 'redirect_status', 'clicks'))) {
				$builder = $builder->orderBy($order_name, $order_type);
			}
			$data['records'] = $builder->offset(($page-1)*$limit)->limit($limit)->orderBy('id', 'desc')->get();
		}
		return $this->json($data);
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
	 * @pattern /api/redirect/add
     * @name api.redirect.add
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
			return $this->json(3);
		}

		if (!Functions::verifyUrl($data['redirect_url'])) {
			$this->log('error', '['.__METHOD__.']非法redirect_url', $data);
			return $this->json(3, '您输入的目标地址格式不对！');
		}

		if (!($domainModel = Domain::iCanUse($this->uid, $data['domain_id']))) {
			$this->log('error', '['.__METHOD__.']非法domain_id', $data);
			return $this->json(3);
		}

		if (DomainRedirect::where('prefix', $data['prefix'])->where('domain_id', $data['domain_id'])->count() > 0) {
			$this->log('error', '['.__METHOD__.']已经存在该源地址跳转', $data);
			return $this->json(3, '已经存在该源地址跳转');
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
	 * @pattern /api/redirect/delete
	 * @name api.redirect.del
	 * @method delete
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return mixed
	 */
	public function del(Request $request, Response $response, $args)
	{
		$errMsg = '';
		$id = $request->getParam('id');
		$ids = Functions::formatIds($id, self::BATCH, $errMsg);
		if (!$ids) {
			$this->log('error', $errMsg, $id);
			return $this->json(3, $errMsg);
		}

		$res = \App\Models\DomainRedirect::whereIn('id', $ids)->isMy($this->uid)->delete();
		if ($res) {
			$this->updateRedirectConf();
			return $this->json(0);
		}
		$this->log('error', 'domain.del error', [$ids]);
		return $this->json(1);
	}


	/**
	 * @pattern /api/redirect/update
     * @name api.redirect.update
	 * @method post
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function update(Request $request, Response $response, $args)
	{
		$id = intval($request->getParsedBodyParam('id'));
		if (!$id || !($domainRedirectModel = \App\Models\DomainRedirect::find($id)) || $domainRedirectModel->uid != $this->uid) {
			return $this->json(3);
		}
		$title = trim($request->getParsedBodyParam('title', ''));
		$redirect_url = trim($request->getParsedBodyParam('redirect_url', 0));
		$redirect_status = (int)$request->getParsedBodyParam('redirect_status', 0);

        if ($title) {
            $domainRedirectModel->title = $title;
        }

        if ($redirect_url) {
            if (!Functions::verifyUrl($redirect_url)) {
                $this->log('error', '['.__METHOD__.']非法redirect_url', $request->getParams());
                return $this->json(3, '您输入的目标地址格式不对！');
            } else {
                $domainRedirectModel->redirect_url = $redirect_url;
            }
        }

        if ($redirect_status) {
            $domainRedirectModel->redirect_status = $redirect_status == 301 ? 301 : 302;
        }

        if ($domainRedirectModel->save()) {
            $this->updateRedirectConf();
            return $this->json(0);
        }
		$this->log('error', 'redirect.update error', $domainRedirectModel->toArray());
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