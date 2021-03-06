<?php
/**
 * Created by PhpStorm.
 * User: wuxin
 * Date: 2018/7/13
 * Time: 23:45
 */
namespace App\Controllers;

use App\Models\Domain;
use App\Models\Template;
use App\Models\Theme;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class Mibiao
 * @middleware App\Middleware\Auth|App\Middleware\Rtime
 * @package App\Controllers
 */
class Mibiao extends Base
{
	//个人米表最多数量
	const MAX_NUM = 5;

    /**
     * @pattern /mibiao
     * @auth user|我的米表
     * @name mibiao
     * @param Request $request
     * @param Response $response
     * @param $args
     */
    public function index(Request $request, Response $response, $args)
    {
        $res = \App\Models\Mibiao::isMy($this->uid)->get()->toArray();
        if ($res) {
        	foreach ($res as &$item) {
        		$item['domainCount'] = Domain::isDns()->where('template_id', $item['template_id'])->count();
        		$item['defaultUrl'] = HOMEPAGE.'/m/'.$item['path'];
        		$item['myUrl'] = '';
        		if ($item['domain_id'] && ($mm = Domain::find($item['domain_id']))) {
        			$item['myUrl'] = $mm->suffix == 'app' ? 'https://'.$mm->name : 'http://'.$mm->name;
				}
			}
		}
		$data['records'] = $res;
		$data['max_num'] = self::MAX_NUM;
		$data['show_add'] = count($res) < self::MAX_NUM;
        return $this->view('mibiao/index.twig', $data);
    }


	/**
	 * @pattern /mibiao/modal/{act}
	 * @auth user
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function modal(Request $request, Response $response, $args)
	{
		$act = $args['act'];
		$data = [];
		$data['themes'] = \App\Models\Theme::getICanUse($this->uid, '*', Theme::THEME_TYPE_MIBIAO);
		$data['templates'] = \App\Models\Template::isMy($this->uid)->get();
		$data['domains'] = \App\Models\Domain::isMy($this->uid)->isDns()->get();

		if ($act == 'edit') {
			$id = (int)$request->getParam('id');
			if (!$id || !($records = \App\Models\Mibiao::find($id)) || $records->uid != $this->uid) {
				return 'id错误！';
			}
			$data['info'] = $records;
			return $this->view('mibiao/modal-edit.twig', $data);
		} elseif ($act == 'add') {
			return $this->view('mibiao/modal-add.twig', $data);
		}
	}

    /**
     * @pattern /api/mibiao/add
	 * @auth user
	 * @name api.mibiao.add
     * @method post
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function add(Request $request, Response $response, $args)
    {
        $data['name'] = $request->getParsedBodyParam('name', '');
        $data['description'] = $request->getParsedBodyParam('description',  '');
        $data['theme_id'] = (int)$request->getParsedBodyParam('theme_id', 0);
        $data['template_id'] = (int)$request->getParsedBodyParam('template_id', 0);
        $data['domain_id'] = (int)$request->getParsedBodyParam('domain_id', 0);
        $data['path'] = $request->getParsedBodyParam('path', '');

        if (!$data['name'] || !$data['theme_id'] || !$data['template_id']) {
            return $this->json(3);
        }

        if (\App\Models\Mibiao::isMy($this->uid)->count() >= self::MAX_NUM) {
			return $this->json(3, '个人米表最多不超过'.self::MAX_NUM.'个！');
		}

        if (!Theme::iCanUse($this->uid, $data['theme_id'], Theme::THEME_TYPE_MIBIAO)) {
            $this->log('error', '非法theme_id:'. $data['theme_id']);
            return $this->json(3);
        }
        if ($data['template_id']) {
            if (!Template::iCanUse($this->uid, $data['template_id'])) {
                $this->log('error', '非法template_id:'. $data['template_id']);
                return $this->json(3);
            }
        }
        if ($data['domain_id']) {
            if (!Domain::iCanUse($this->uid, $data['domain_id'])) {
                $this->log('error', '非法domain_id:'. $data['domain_id']);
                return $this->json(3);
            }
			if (\App\Models\Mibiao::where('domain_id', $data['domain_id'])->count()) {
				$this->log('error', '该绑定域名已经被启用:'. $data['domain_id']);
				return $this->json(3, '该绑定域名已经被启用');
			}
        }
        if ($data['path']) {
            $preg = '/^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,30}[a-zA-Z0-9])?)+/';
            if (!preg_match($preg, $data['path']) || \App\Models\Mibiao::where('path', $data['path'])->count()) {
                $this->log('error', '非法path:'. $data['path']);
                return $this->json(3, 'path无效或者已经被占用');
            }
        } else {
        	//此处有bug
            $c = \App\Models\Mibiao::count();
            $data['path'] = $this->uid.($c+1);
        }
        $data['uid'] = $this->uid;

        if (\App\Models\Mibiao::insert($data)) {
            return $this->json(0);
        }
        return $this->json(1);
    }

    /**
     * @pattern /api/mibiao/delete
	 * @auth user
     * @name api.mibiao.del
     * @method delete
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function del(Request $request, Response $response, $args)
    {
        $id = $request->getParam('id');
        if (!$id) {
			return $this->json(3);
		}
        if (\App\Models\Mibiao::where('id', $id)->isMy($this->uid)->delete()) {
        	$this->log('error', 'mibiao.delete error', [$id]);
            return $this->json(0);
        }
        return $this->json(1);
    }


    /**
     * @pattern /api/mibiao/update
	 * @name api.mibiao.update
	 * @auth user
     * @method post
     * @param Request $request
     * @param Response $response
     * @param $args
     */
    public function update(Request $request, Response $response, $args)
    {
        $id = (int)$request->getParsedBodyParam('id');
		if (!$id || !($current = \App\Models\Mibiao::find($id)) || $current->uid != $this->uid) {
			return $this->json(3);
		}
		$data['name'] = $request->getParsedBodyParam('name', '');
		$data['description'] = $request->getParsedBodyParam('description',  '');
		$data['theme_id'] = (int)$request->getParsedBodyParam('theme_id', 0);
		$data['template_id'] = (int)$request->getParsedBodyParam('template_id', 0);
		$data['domain_id'] = (int)$request->getParsedBodyParam('domain_id', 0);
		$data['path'] = $request->getParsedBodyParam('path', '');

		if (!$data['name'] || !$data['theme_id'] || !$data['path'] || !$data['template_id']) {
			return $this->json(3);
		}

		if ($current->theme_id != $data['theme_id'] && !Theme::iCanUse($this->uid, $data['theme_id'], Theme::THEME_TYPE_MIBIAO)) {
			$this->log('error', '非法theme_id:'. $data['theme_id']);
			return $this->json(3);
		}
		if ($data['template_id'] && $current->template_id != $data['template_id']) {
			if (!Template::iCanUse($this->uid, $data['template_id'])) {
				$this->log('error', '非法template_id:'. $data['template_id']);
				return $this->json(3);
			}
		}
		if ($data['domain_id']  && $current->domain_id != $data['domain_id']) {
			if (!Domain::iCanUse($this->uid, $data['domain_id'])) {
				$this->log('error', '非法domain_id:'. $data['domain_id']);
				return $this->json(3);
			}
			if (\App\Models\Mibiao::where('domain_id', $data['domain_id'])->count()) {
				$this->log('error', '该绑定域名已经被启用:'. $data['domain_id']);
				return $this->json(3, '该绑定域名已经被启用');
			}
		}
		if ($data['path'] && $current->path != $data['path']) {
			$preg = '/^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,30}[a-zA-Z0-9])?)+/';
			if (!preg_match($preg, $data['path']) || \App\Models\Mibiao::where('path', $data['path'])->count()) {
				$this->log('error', '非法path:'. $data['path']);
				return $this->json(3, 'path无效或者已经被占用');
			}
		}

		if (\App\Models\Mibiao::where('id', $id)->update($data)) {
			$this->log('error', 'mibiao.update error', ['id'=>$data]);
			return $this->json(0);
		}
		return $this->json(1);
    }
}