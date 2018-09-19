<?php
/**
 * SiteConf.php for qumi
 * @author SamWu
 * @date 2018/9/12 16:18
 * @copyright boyaa.com
 */
namespace App\Controllers\Admin;

use App\Controllers\Base;
use App\Functions;
use Slim\Http\Request;
use Slim\Http\Response;

class SiteConf extends Base
{
	/**
	 * 站点配置
	 * @pattern /admin/siteconf
     * @name admin.siteconf
     * @auth admin|系统配置
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return mixed
	 */
	public function index(Request $request, Response $response, $args)
	{
		$data['records'] = \App\Models\SiteConf::all();
		return $this->view('admin/siteconf/index.twig', $data);
	}


    /**
     * 删除域名
     * 支持批量删除
     * @pattern /admin/siteconf/delete
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return bool
     */
    public function del(Request $request, Response $response, $args)
    {
        $errMsg = '';
        $id = $request->getParam('id');
        $ids = Functions::formatIds($id, self::BATCH, $errMsg);
        if (!$ids) {
            $this->log('error', $errMsg, $ids);
            return $this->json(40001, $errMsg);
        }

        $res = \App\Models\SiteConf::whereIn('id', $ids)->delete();
        if ($res) {
            $this->log('error', 'siteconf.del error', [$ids]);
            return $this->json(0);
        }
        return $this->json(1);

    }

    /**
     * 添加域名
     * @pattern /admin/siteconf/add
     * @method post
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return static
     */
    public function add(Request $request, Response $response, $args)
    {
        $data['key'] = trim($request->getParsedBodyParam('key', ''));
        $data['val'] = trim($request->getParsedBodyParam('val', ''));
        $data['sort'] = (int)$request->getParsedBodyParam('sort', 0);
        if (!$data['key'] || !$data['val']) {
            return $this->json(40001);
        }
        if (\App\Models\SiteConf::insert($data)) {
            return $this->json(0);
        }
        return $this->json(1);
    }

    /**
     * 更新域名信息
     * 支持批量
     * @pattern /admin/siteconf/update
     * @method post
     * @param Request $request
     * @param Response $response
     * @param $args
     */
    public function update(Request $request, Response $response, $args)
    {
        $id = $request->getParsedBodyParam('id', null);
        $val = $request->getParsedBodyParam('val', '');
        $sort = $request->getParsedBodyParam('sort', 0);

        if (!$id) {
            return $this->json(40001);
        }
        //如果是修改一个的话，包装成数组交给下面去处理
        if (is_numeric($id)) {
            if (!$id = intval($id)) {
                return $this->json(40001);
            }
            $id = [$id];
            $val = [$val];
            $sort = [$sort];
        }
        //数据清洗处理
        if (is_array($id)) {
            $data = [];
            foreach ($id as $k => $_id) {
                $data[] = array(
                    'id' => $_id,
                    'val' => trim($val[$k]),
                    'sort' => (int)$sort[$k]
                );
            }
            if (\App\Models\SiteConf::updateBatch($data, 'id')) {
                return $this->json(0);
            }
        }
        return $this->json(1);
    }

    /**
     * @pattern /admin/siteconf/modal/{act}
     * @param Request $request
     * @param Response $response
     * @param $args
     */
    public function modal(Request $request, Response $response, $args)
    {
        $act = $args['act'];
        $data = [];
        if ($act == 'edit') {
            $errMsg = '';
            $id = $request->getParam('id');
            $ids = Functions::formatIds($id, self::BATCH, $errMsg);
            if (!$ids) {
                $this->log('error', $errMsg, $ids);
                return $errMsg;
            }
            if (!$records = \App\Models\SiteConf::whereIn('id', $ids)->get()) {
                //传递了非法域名id
                $this->log('error', '非法id', $ids);
                return '非法id';
            }
            if (count($records) == 1) {
                //修改一个
                $data['detail'] = $records[0];
                return $this->view('admin/siteconf/modal-edit-one.twig', $data);
            } else {
                //修改多个
                $data['records'] = $records;
                return $this->view('admin/siteconf/modal-edit-more.twig', $data);
            }
        } elseif ($act == 'add') {
            return $this->view('admin/siteconf/modal-add.twig', $data);
        }
    }
}