<?php
/**
 * Template.php for qumi
 * @author SamWu
 * @date 2018/7/12 10:41
 * @copyright boyaa.com
 */
namespace App\Controllers\Home;

use App\Controllers\Base;
use App\Functions;
use App\Models\Domain;
use App\Models\Theme;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 *
 * Class Template
 * @middleware App\Middleware\Rtime
 * @package App\Controllers
 */
class Template extends Base
{
	/**
	 * 我的模板
	 * @pattern /api/template
     * @name home.api.template.get
     * @method get
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function get(Request $request, Response $response, $args)
	{
	    $this->uid = 100;
		$data = array();
		$kw = $request->getQueryParam('kw');
		$order_name = trim($request->getQueryParam('order_name'));
		$order_type = trim($request->getQueryParam('order_type'));
		$page = (int)$request->getParam('page') ?: 1;
        $limit = min((int)$request->getQueryParam('limit') ?: self::$page_number, 100);

		$builder = \App\Models\Template::isMy($this->uid);
		if ($kw){
			$builder = $builder->where('name', 'like', '%'.$kw.'%');
		}

		$data['count'] = $builder->count();
		$data['records'] = [];
		if ($data['count']) {
            if ($order_type && $order_type != 'null' && in_array($order_name, array('id', 'name', 'qq', 'wechat', 'email', 'phone'))) {
                $builder = $builder->orderBy($order_name, $order_type);
            }
			$data['records'] = $builder->offset(($page-1)*$limit)->limit($limit)->get()->toArray();
            $data['records'] = array_map(function($arr){
                $arr['domainCount'] = Domain::where('template_id', $arr['id'])->count();
                return $arr;
            }, $data['records']);
		}
		return $this->json($data);
	}

	/**
	 * //@pattern /api/template/add
	 * @method post
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return mixed
	 */
	public function add(Request $request, Response $response, $args)
	{
		$data['name'] = $request->getParsedBodyParam('name', '');
		$data['theme_id'] = (int)$request->getParsedBodyParam('theme_id', 0);
		$data['qq'] = $request->getParsedBodyParam('qq',  '');
		$data['wechat'] = $request->getParsedBodyParam('wechat', '');
		$data['phone'] = $request->getParsedBodyParam('phone', '');
		$data['email'] = $request->getParsedBodyParam('email', '');
		if (!$data['name']) {
			return $this->json(3);
		}
		if (!Theme::iCanUse($this->uid, $data['theme_id'])) {
			$this->log('error', '非法theme_id:'. $data['theme_id']);
			return $this->json(3);
		}
		$data['uid'] = $this->uid;
		if (\App\Models\Template::insert($data)) {
			return $this->json(0);
		}
		return $this->json(1);
	}

	/**
	 * @pattern /api/template
     * @name home.api.template.del
     * @method delete
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return mixed
	 */
	public function del(Request $request, Response $response, $args)
	{
		$errMsg = '';
		$id = $request->getParam('template_id');
		$ids = Functions::formatIds($id, self::BATCH, $errMsg);
		if (!$ids) {
			$this->log('error', $errMsg, $id);
			return $this->json(3, $errMsg);
		}

		//不要删掉默认的
		if ($defId = $this->userInfo['def_template_id']) {
			if (count($ids) == 1 && $ids[0] == $defId) {
				return $this->json(1, '默认模板不允许被删除');
			} else {
				foreach ($ids as $k=>$v) {
					if ($v == $defId) {
						unset($ids[$k]);
						break;
					}
				}
			}
		}

		$res = \App\Models\Template::whereIn('id', $ids)->isMy($this->uid)->delete();
		if ($res) {
			return $this->json(0);
		}
		return $this->json(1);
	}

    /**
     * @pattern /api/template
     * @name home.api.template.update
     * @method put
     * @param Request $request
     * @param Response $response
     * @param $args
     */
    public function update(Request $request, Response $response, $args)
    {
        $id = (int)$request->getParsedBodyParam('template_id');
        $data['name'] = $request->getParsedBodyParam('name', '');
        $data['theme_id'] = (int)$request->getParsedBodyParam('theme_id', 0);
        $data['qq'] = $request->getParsedBodyParam('qq',  '');
        $data['wechat'] = $request->getParsedBodyParam('wechat', '');
        $data['phone'] = $request->getParsedBodyParam('phone', '');
        $data['email'] = $request->getParsedBodyParam('email', '');
        if (!$id) {
            return $this->json(3);
        }
        $templateModel = \App\Models\Template::find($id);
        if (!$templateModel || $this->uid != $templateModel->uid) {
            $this->log('error', '非法template_id：'.$id);
            return $this->json(3);
        }

        foreach ($data as $k=>$v) {
            $templateModel->{$k} = $v;
        }

        if ($templateModel->save()) {
            return $this->json(0);
        }
        return $this->json(1);
    }

	/**
	 * //@pattern /api/template/setdef
     * @name home.api.template.setdef
     * @method get
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function setdef(Request $request, Response $response, $args)
	{
		$id = (int)$request->getQueryParam('template_id');
		if ($id && \App\Models\Template::iCanUse($this->uid, $id)) {
			if (\App\Models\User::find($this->uid)->setDefTemplate($id)) {
				$this->userInfo['def_template_id'] = $id;
				$this->session->set('userInfo', $this->userInfo);
				return $this->json(0);
			}
		}
		return $this->json(1);
	}
}