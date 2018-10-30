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
use App\Libraries\Email;
use App\Libraries\File;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class SiteConf
 * @middleware App\Middleware\Auth|App\Middleware\Rtime
 * @package App\Controllers\Admin
 */
class SiteConf extends Base
{
	/**
	 * 站点配置
	 * @pattern /admin/siteconf
	 * @auth admin|系统配置
     * @name admin.siteconf
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return mixed
	 */
	public function index(Request $request, Response $response, $args)
	{
		$data = [];
		return $this->view('admin/siteconf/index.twig', $data);
	}

	/**
	 * 站点配置
	 * @pattern /api/admin/siteconfs
	 * @auth admin
     * @name api.admin.siteconf.get
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return mixed
	 */
	public function get(Request $request, Response $response, $args)
	{
		$data = [];
		$kw = $request->getQueryParam('kw');
		$order_name = trim($request->getQueryParam('order_name'));
		$order_type = trim($request->getQueryParam('order_type'));
		$page = (int)$request->getParam('page') ?: 1;
		$limit = min((int)$request->getQueryParam('limit') ?: self::$page_number, 100);

		$builder = new \App\Models\SiteConf();
		if ($kw){
			$builder = $builder->where('title', 'like', '%'.$kw.'%');
		}

		$data['count'] = $builder->count();
		$data['records'] = [];
		if ($data['count']) {
			if ($order_type && $order_type != 'null' && in_array($order_name, array('id','title', 'key', 'val', 'created_at'))) {
				$builder = $builder->orderBy($order_name, $order_type);
			}
			$data['records'] = $builder->offset(($page-1)*$limit)->limit($limit)->get()->toArray();
			$data['records'] = array_map(function($arr){$arr['val'] = htmlspecialchars($arr['val']);return $arr;},$data['records']);
		}
		return $this->json($data);
	}



    /**
     * 删除
     * @pattern /admin/siteconf/del
	 * @auth admin
	 * @name api.admin.siteconf.del
	 * @method delete
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
            return $this->json(3, $errMsg);
        }

        $res = \App\Models\SiteConf::whereIn('id', $ids)->delete();
        if ($res) {
            return $this->json(0);
        }
		$this->log('error', 'siteconf.del error', [$ids]);
        return $this->json(1);

    }

    /**
     * @pattern /admin/siteconf/add
	 * @auth admin
	 * @name api.admin.siteconf.add
     * @method post
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return static
     */
    public function add(Request $request, Response $response, $args)
    {
        $data['title'] = trim($request->getParsedBodyParam('title', ''));
        $data['key'] = trim($request->getParsedBodyParam('key', ''));
        $data['val'] = trim($request->getParsedBodyParam('val', ''));
        if (!$data['title'] || !$data['key']) {
            return $this->json(3);
        }
        if (\App\Models\SiteConf::where('key', $data['key'])->count() > 0) {
			return $this->json(3, '系统已经存在该配置项');
		}
        if (\App\Models\SiteConf::insert($data)) {
            return $this->json(0);
        }
        return $this->json(1);
    }

    /**
     * 更新
     * @pattern /admin/siteconf/update
	 * @auth admin
	 * @name api.admin.siteconf.update
     * @method post
     * @param Request $request
     * @param Response $response
     * @param $args
     */
    public function update(Request $request, Response $response, $args)
    {
        $id = (int)$request->getParsedBodyParam('id', null);
        $title = trim($request->getParsedBodyParam('title', ''));
        $val = trim($request->getParsedBodyParam('val', null));

        if (!$id || !($siteConfModel = \App\Models\SiteConf::find($id))) {
            return $this->json(3);
        }

        if ($title) {
        	$siteConfModel->title = $title;
		}
		if ($val !== null) {
        	$siteConfModel->val = $val;
		}

		if ($siteConfModel->save()) {
        	return $this->json(0);
		}
        return $this->json(1);
    }

	/**
	 * @pattern /admin/siteconf/file
	 * @auth admin
	 * @name api.admin.siteconf.file
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return mixed
	 */
	public function updateSiteConfFile(Request $request, Response $response, $args)
	{
		$siteConfFile = CONFIG_DIR.'config.php';
		$data = \App\Models\SiteConf::all();
		$_data = [];
		foreach ($data as $item) {
			$_data[$item['key']] = $item['val'];
		}
		if (File::writeRetPhp($siteConfFile, $_data)) {
			return $this->json(0);
		}
		$this->log('error', '写入配置文件失败了');
		Email::factory()->insertQueue('opdss@qq.com', '写入配置文件失败了', '出错啦！');
		return $this->json(1, '写入配置文件失败了');
	}

}