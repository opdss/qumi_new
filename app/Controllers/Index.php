<?php
/**
 * Index.php for qumi
 * @author SamWu
 * @date 2018/7/9 15:55
 * @copyright boyaa.com
 */
namespace App\Controllers;

use App\Functions;
use App\Libraries\Config;
use App\Models\Domain;
use App\Models\DomainAccessLog;
use App\Models\DomainAccessLogCount;
use App\Models\Template;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class Index
 * @middleware App\Middleware\Rtime
 * @package App\Controllers
 */
class Index extends Base
{
	/**
	 * @pattern /
	 * @name index
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function index(Request $request, Response $response, $args)
	{

        $data = array();
        $filter = [];
        $filter['kw'] = $request->getQueryParam('kw', '');
        $filter['page'] = (int)$request->getParam('page') ?: 1;

        $builder = \App\Models\Domain::isDns();
        if ($filter['kw']){
            $builder = $builder->where('name', 'like', '%'.$filter['kw'].'%');
        }
        $data['filter'] = $filter;
        $data['count'] = $builder->count();
        $data['records'] = [];
        if ($data['count']) {
            $data['records'] = $builder->offset(($filter['page']-1)*self::$page_number)->limit(self::$page_number)->orderBy('id', 'desc')->get();
            $data['pagination'] = Functions::pagination($data['count'], self::$page_number);
        }
		$data['carousel'] = Config::get('carousel');
        //var_dump($data['carousel']);exit;
		return $this->view('public/index.twig',$data);
	}

	/**
	 * @pattern /search
	 * @name search
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function search(Request $request, Response $response, $args)
	{
		$data = array();
		$filter = [];
		$filter['kw'] = $request->getQueryParam('kw', '');
		$filter['page'] = (int)$request->getParam('page') ?: 1;

		$data['filter'] = $filter;

		$builder = \App\Models\Domain::isDns();
		if ($filter['kw']){
			$builder = $builder->where(function ($query) use ($filter){
			    return $query->where('name', 'like', '%'.$filter['kw'].'%')->orWhere('description', 'like', '%'.$filter['kw'].'%');
            });
		}

        $data['count'] = $builder->count();
        $data['records'] = [];
        if ($data['count']) {
            $data['records'] = $builder->offset(($filter['page']-1)*self::$page_number)->limit(self::$page_number)->orderBy('id', 'desc')->get();
            $data['pagination'] = Functions::pagination($data['count'], self::$page_number);
        }
		return $this->view('public/searchPage.twig',$data);
	}

	/**
	 * @pattern /m/{path}
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return mixed
	 */
	public function mibiao(Request $request, Response $response, $args)
	{
		if (!$args['path'] || !($mibiaoModel = \App\Models\Mibiao::where('path', $args['path'])->first())) {
			return $this->view('404.twig');
		}

		$data = array();
		$filter = [];
		$filter['kw'] = $request->getQueryParam('kw', '');
		$filter['page'] = (int)$request->getParam('page') ?: 1;
		$limit = (int)$request->getQueryParam('limit') ?: self::$page_number;
		$limit = min($limit, 100);

		$data['filter'] = $filter;
		$data['filter']['limit'] = $limit;
		$data['count'] = 0;
		$data['records'] = [];

		$builder = \App\Models\Domain::isDns()->where('template_id', $mibiaoModel->template_id);

		if ($filter['kw']){
			$builder = $builder->where('name', 'like', '%'.$filter['kw'].'%');
		}

		$data['count'] = $builder->count();
		if ($data['count']) {
			$data['records'] = $builder->offset(($filter['page']-1)*$limit)->limit($limit)->orderBy('id', 'desc')->get();
		}
        $data['mibiao'] = $mibiaoModel;
        $data['template'] = Template::find($mibiaoModel->template_id);
		//return $this->view(Theme::find($mibiaoModel->theme_id)->path, $data);
		return $this->view('public/mibiao.twig', $data);
	}

	/**
	 * routes
	 * @pattern /routes
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function routes(Request $request, Response $response, $args)
	{
		$type = $request->getParam('type');
		$routes = $this->ci->offsetGet('routes');
		if ($type == 'api') {
			foreach ($routes as $k => $v) {
				if (substr($v['pattern'], 0, 4) != '/api') {
					unset($routes[$k]);
				}
			}
		}
		return $this->json($routes);
	}

    /**
     * @pattern /d/{domainIdOrName}
     * @param Request $request
     * @param Response $response
     * @param $args
     */
    public function detail(Request $request, Response $response, $args)
    {
        $domainIdOrName = $args['domainIdOrName'];
        if (is_numeric($domainIdOrName)) {
            $domainModel = Domain::find($domainIdOrName);
        } else {
            $domainModel = Domain::where('name', $domainIdOrName)->first();
        }
        //域名不存在了
        if (!$domainModel) {
            $this->log('alert', 'detail domain not found: '.$domainIdOrName);
            return $response->withRedirect(HOMEPAGE, 301);
        }

        //记录访问日志 start
        $from = $request->getQueryParam('from', '');
		$logModel = Functions::saveAccessLog($domainModel, $from ? base64_decode($from) : '');
		//记录访问日志 end

		//检查有没有绑定米表
		$mibiaoModel = \App\Models\Mibiao::where('domain_id', $domainModel->id)->first();
		if ($mibiaoModel) {
		    $url = $this->ci->router->pathFor('index.mibiao', ['path'=> $mibiaoModel->path]);
			return $response->withRedirect($url, 301);
		}

		//根据ip地理位置设置语言
		if (!isset($_GET['l'])) {
            if (strpos($logModel->region, '中国') === false && strpos($logModel->region, '台湾') === false) {
                $_GET['l'] = 'en';
            }
        }

        $temp['logid'] = $logModel->id;
		//开始准备渲染模板页面
		if ($domainModel->template_id && $templateInfo = \App\Models\Template::find($domainModel->template_id)) {
			$log = ['domain_id' => $domainModel->id, 'template_id' => $templateInfo->id, 'theme_id' => $templateInfo->theme_id];
			//默认主题模板为1
			if (!$templateInfo->theme_id || !$themeInfo = \App\Models\Theme::find($templateInfo->theme_id)) {
				\App\Functions::getLogger()->error('mibiao theme_id error:', $log);
				$themeInfo = \App\Models\Theme::find(1);
			}
            $temp['domain'] = $domainModel;
            $temp['template'] = $templateInfo;
            $temp['site'] = Config::get('site');
			return $this->ci->view->render($this->ci->response, $themeInfo->path, $temp);
		} else {
			\App\Functions::getLogger()->debug('domain_id:' . $domainModel->id . '('.$domainModel->name.')未设置模板 =>' . $domainModel->template_id);
			$temp['domain'] = $domainModel;
			$temp['site'] = Config::get('site');
			return $this->view('theme/default/big.twig', $temp);
		}
    }
}