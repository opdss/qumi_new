<?php
/**
 * Template.php for qumi
 * @author SamWu
 * @date 2018/7/12 10:41
 * @copyright boyaa.com
 */
namespace App\Controllers;

use App\Functions;
use App\Models\Theme;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 *
 * Class Template
 * @middleware App\Middleware\Auth|App\Middleware\Rtime
 * @package App\Controllers
 */
class Template extends Base
{
	/**
	 * 我的模板
	 * @pattern /template
	 * @name template
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function index(Request $request, Response $response, $args)
	{
		$data = array();

		$kw = $request->getParam('kw');
		$page = (int)$request->getParam('page') ?: 1;

		$builder = \App\Models\Template::isMy($this->uid);
		if ($kw){
			$builder = $builder->where('name', 'like', '%'.$kw.'%');
		}
		$data['filter'] = array(
			'kw' => $kw,
		);
		$data['count'] = $builder->count();
		$data['records'] = [];
		if ($data['count']) {
			$data['records'] = $builder->offset(($page-1)*self::$page_number)->limit(self::$page_number)->get();
		}
        $data['currentName'] = $request->getAttribute('route')->getName();
		return $this->view('template/index.twig', $data);
	}

	/**
	 * @pattern /template/modal/{act}
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function modal(Request $request, Response $response, $args)
	{
		$act = $args['act'];
		$data = [];
		$data['themes'] = \App\Models\Theme::getICanUse($this->uid);
		if ($act == 'edit') {
			$id = (int)$request->getParam('template_id');
			if (!$id || !$records = \App\Models\Template::where('id', $id)->isMy($this->uid)->first()) {
				$this->log('error', '修改模板id错误:'.$id);
				return 'id错误！';
			}
			//要修改的模板
			$data['template'] = $records;
			return $this->view('template/modal-edit.twig', $data);
		} elseif ($act == 'add') {
			return $this->view('template/modal-add.twig', $data);
		}
	}

	/**
	 * @pattern /template/add
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
			return $this->json(40001);
		}
		if (!Theme::iCanUse($this->uid, $data['theme_id'])) {
			$this->log('error', '非法theme_id:'. $data['theme_id']);
			return $this->json(40001);
		}
		$data['uid'] = $this->uid;
		if (\App\Models\Template::insert($data)) {
			return $this->json(0);
		}
		return $this->json(1);
	}

	/**
	 * @pattern /template/del
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
			return $this->json(40001, $errMsg);
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
     * @pattern /template/update
     * @method post
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
            return $this->json(40001);
        }
        $templateModel = \App\Models\Template::find($id);
        if (!$templateModel || $this->uid != $templateModel->uid) {
            $this->log('error', '非法template_id：'.$id);
            return $this->json(40001);
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
	 * @pattern /template/setdef
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