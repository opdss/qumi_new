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
use App\Models\Article;
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
     * 首页
	 * @pattern /
	 * @name index
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function index(Request $request, Response $response, $args)
	{

        $data = array();
        $data['hotDomain'] = Domain::isDns()->orderBy('pvs', 'desc')->limit(12)->get()->toArray();
        $data['newDomain'] = Domain::isDns()->orderBy('id', 'desc')->limit(12)->get()->toArray();
        $data['topDomain'] = Domain::isDns()->orderBy('istop', 'desc')->limit(12)->get()->toArray();
        $data['newArticle'] = Article::orderBy('id', 'desc')->limit(12)->get()->toArray();
		$data['carousel'] = Config::get('carousel');
        //var_dump($data['carousel']);exit;
        //var_dump($data);exit;
		return $this->view('public/index.twig',$data);
	}

    /**
     * 域名聚合页
     * @pattern /domains
     * @method post|get
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function domains(Request $request, Response $response, $args)
    {
        $this->addJs('statics/js/offer.js');
        $allowOrder = array('name', 'price', 'len', 'dtype', 'suffix');
        $filter = [];
        $filter['kw'] = $request->getQueryParam('kw', '');
        $filter['page'] = (int)$request->getParam('page') ?: 1;
        $filter['suffix'] = $request->getParam('suffix');
        $filter['dtype'] = (int)$request->getParam('dtype');
        $filter['len'] = (int)$request->getQueryParam('len');
        $filter['order_by'] = $request->getQueryParam('order_by');
        $filter['limit'] = min((int)$request->getQueryParam('limit') ?: self::$page_number, 100);

        $builder = new Domain();
        $builder = $builder->where('dns_status', 1);
        if ($filter['dtype'] > 0 ) {
            $builder = $builder->where('dtype', $filter['dtype']);
        }
        if ($filter['len'] > 0) {
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
            $data['records'] = $builder->offset(($filter['page']-1)*$filter['limit'])->limit($filter['limit'])->orderBy('id', 'desc')->get();
        }
        $data['suffixs'] = Domain::getAllSuffix();
        $data['lens'] = Domain::getAllLen();
        $data['filterObj'] = json_encode($filter);
        return $this->view('public/domains.twig', $data);
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