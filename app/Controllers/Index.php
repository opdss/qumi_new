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
use App\Libraries\Email;
use App\Models\Domain;
use App\Models\DomainAccessLog;
use App\Models\DomainAccessLogCount;
use App\Models\DomainRedirect;
use App\Models\Template;
use App\Models\Theme;
use App\Models\User;
use Opdss\Cicache\Cache;
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

		$data['filter'] = $filter;
		$data['count'] = 0;
		$data['records'] = [];

		$builder = \App\Models\Domain::isDns()->where('template_id', $mibiaoModel->template_id);

		if ($filter['kw']){
			$builder = $builder->where('name', 'like', '%'.$filter['kw'].'%');
		}

		$data['count'] = $builder->count();
		if ($data['count']) {
			$data['records'] = $builder->offset(($filter['page']-1)*self::$page_number)->limit(self::$page_number)->orderBy('id', 'desc')->get();
			$data['pagination'] = Functions::pagination($data['count'], self::$page_number);
		}
        $data['mibiao'] = $mibiaoModel;
        $data['template'] = Template::find($mibiaoModel->template_id);
		return $this->view(Theme::find($mibiaoModel->theme_id)->path, $data);
	}

	/**
	 * @pattern /test
     * @name test
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function test(Request $request, Response $response, $args)
	{
	    //$preg = '/login|logout|forget|register/';
	    $preg = '/\/login|\/logout|\/forget|\/register/';
	    $str = 'gweg/agaeg/aweg/aewleoginregister/loegout';
	    var_dump(preg_match($preg, $str));
	    exit;
        $userModel = User::where('email', 'opdss@qq.com')->first();
       $res = $userModel->resetPassword('222222');
	   var_dump($res);
       exit;
        $emailCode = Functions::genRandStr(6);
        $body = "您好，opdss@qq.com，您正在使用找回密码功能，验证码：${emailCode} 。";
        $data = ['to'=>'opdss@qq.com', 'body'=>$body, 'subject'=>'趣米停靠站-找回密码', 'level'=>10];
        $flag = Email::factory()->insertQueue($data);
        var_dump($flag);
	    exit;
		Functions::runTime('test');
		$email = Email::factory();
		$email->setSubject('登陆通知')->setBody('你好，登陆成功')->addAddress(['opdss@qq.com', '479531993@qq.com'])->send();
		//$email->send('opdss@qq.com', 'test');
		var_dump(Functions::runTime('test', 1));
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

        $from = $request->getQueryParam('from', '');
        if ($from) {
            $from = base64_decode($from);
        }
		//记录访问日志 start
		$logModel = Functions::saveAccessLog($domainModel, $from);

		//记录访问日志 end

        //检查有没有跳转记录
        if ($from) {
            $fromInfo = parse_url($from);
            if ($fromInfo && $fromInfo['host']) {
                //Functions::
            }
            //DomainRedirect::where('domain_id', $domainModel->id)->where('');
        }

		//检查有没有绑定米表
		$mibiaoModel = \App\Models\Mibiao::where('domain_id', $domainModel->id)->first();
		if ($mibiaoModel) {
		    $url = $this->ci->router->pathFor('index.mibiao', ['path'=> $mibiaoModel->path]);
			return $response->withRedirect($url, 301);
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
			return $this->view('public/domain.twig', $temp);
			//header('location:' . HOMEPAGE);
		}
    }

    /**
     * routes
     * @pattern /realclicks/{logid}
     * @method get
     * @param Request $request
     * @param Response $response
     * @param $args
     */
    public function realclicks(Request $request, Response $response, $args) {
        $outtime = 10;
        $logid = isset($args['logid']) ? (int)$args['logid'] : 0;
        if ($logid && ($logModel = DomainAccessLog::select('domain_id', 'is_bot', 'is_real_clicks', 'created_at')->find($logid))) {
            if ($logModel->is_bot == 0 && $logModel->is_real_clicks == 0 && time() - strtotime($logModel->created_at) <= $outtime ) {
                DomainAccessLog::where('id', $logid)->update(['is_real_clicks'=>1]);
                DomainAccessLogCount::where('domain_id', $logModel->domain_id)->where('day', substr($logModel->created_at, 0, 10))->increment('real_clicks', 1);
                return $this->json(0);
            }
            $this->log('error', 'realclicks', [$logid, $logModel->toArray()]);
        }
        return $this->json(1);
    }

}