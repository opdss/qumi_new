<?php
/**
 * Statistic.php for qumi
 * @author SamWu
 * @date 2018/7/24 10:25
 * @copyright boyaa.com
 */
namespace App\Controllers\Home;

use App\Controllers\Base;
use App\Functions;
use App\Models\Domain;
use App\Models\DomainAccessLog;
use App\Models\DomainAccessLogCount;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class Statistic
 * @middleware App\Middleware\Rtime
 * @package App\Controllers
 */
class Statistic extends Base
{

    /**
     * 域名访问详情
     * //@pattern /api/statistic/detail[/{id:[0-9]+}]
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function detail(Request $request, Response $response, $args)
    {
        $this->addJs('/statics/js/echarts.min.js');
        $data = [];

        if (isset($args['id'])) {
            if (!($domainInfo = \App\Models\Domain::find($args['id'])) || $domainInfo->uid != $this->uid) {
                return $this->view('404.twig');
            }
            $data['currentDomain'] = $domainInfo;
        }

        $filter['kw'] = $request->getQueryParam('kw', '');
        $filter['page'] = (int)$request->getParam('page');
        $page = $filter['page'] ? $filter['page'] : 1;
        $number = 20;
        $filter['template_id'] = (int)$request->getParam('template_id');
        $filter['dns_status'] = (int)$request->getParam('dns_status');

        $builder = new \App\Models\DomainAccessLog();

        if (isset($args['id'])) {
            $builder = $builder->where('domain_id', $domainInfo->id);
        } else {
            $builder = $builder->isMy($this->uid);
        }

        $data['filter'] = $filter;
        $data['count'] = $builder->count();
        $data['records'] = [];
        if ($data['count']) {
            $data['records'] = $builder->offset(($page - 1) * $number)->limit($number)->orderBy('id', 'desc')->get();
            $data['pagination'] = Functions::pagination($data['count'], $number);
        }

        if (isset($args['id'])) {
            $data['echarts'] = $this->echarts($request, $response, $args);
        }

        $data['currentName'] = 'statistic';
        return $this->view('statistic/detail.twig', $data);
    }

    /**
     * //@pattern /api/statistic/day
     * @name home.api.statistic.day
     * @method get
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function day(Request $request, Response $response, $args)
    {
        $this->uid = 100;
        $data = [];
        $allowOrder = array('domain_name', 'pv', 'ip', 'bot', 'user', 'domestic', 'overseas', 'real_clicks', 'day');

        //获取校验参数
        $page = (int)$request->getQueryParam('page') ?: 1;
        $kw = $request->getQueryParam('kw');
        $domain_id = (int)$request->getQueryParam('domain_id');
        $date_time = $request->getQueryParam('date_time');
        $order_name = trim($request->getQueryParam('order_name'));
        $order_type = trim($request->getQueryParam('order_type'));
        $limit = min((int)$request->getQueryParam('limit') ?: self::$page_number, 100);

        //构造条件
        $builder = \App\Models\DomainAccessLogCount::isMy($this->uid);
        if ($domain_id) {
            $builder = $builder->where('domain_id', $domain_id);
        } else {
            if ($kw) {
                $builder = $builder->where('domain_name', 'like', '%' . $kw . '%');
            }
        }

        if ($date_time) {
            list($start_date, $end_date) = explode('-', $date_time);
            $start_date = strtotime($start_date);
            $end_date = strtotime($end_date);
            if ($start_date && $end_date) {
                $start_date = date('Y-m-d', $start_date);
                $end_date = date('Y-m-d', $end_date);
                $builder = $start_date == $end_date ? $builder->where('day', $start_date) : $builder->whereBetween('day', [$start_date, $end_date]);
            }
        }

        //最终获取数据
        $data['count'] = $builder->count();
        $data['records'] = [];
        if ($data['count']) {
            if ($order_type && $order_type != 'null' && in_array($order_name, $allowOrder)) {
                $builder = $builder->orderBy($order_name, $order_type);
            }
            $data['records'] = $builder->offset(($page - 1) * $limit)->limit($limit)->orderBy('day', 'desc')->get();
        }

        return $this->json($data);
    }

    /**
     * //@pattern /api/statistic/count
     * @name home.api.statistic.count
     * @method get
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function count(Request $request, Response $response, $args)
    {
        $data = array();
        $allowOrder = array('domain_name', 'pvs', 'ips', 'bots', 'users', 'domestics', 'overseass');
        $page = (int)$request->getQueryParam('page') ?: 1;
        $filter['kw'] = $request->getQueryParam('kw', '');
        $filter['order_by'] = $request->getQueryParam('order_by');

        $data['filter'] = $filter;

        //这是一个分组查询
        $builder = \App\Models\DomainAccessLogCount::isMy($this->uid)->groupBy('domain_id');
        if ($filter['kw']) {
            $builder = $builder->where('domain_name', 'like', '%' . $filter['kw'] . '%');
        }

        $data['count'] = count($builder->selectRaw('domain_id')->get()->toArray());
        $data['records'] = [];
        if ($data['count']) {
			if ($filter['order_by'] && is_array($filter['order_by'])) {
				foreach ($filter['order_by'] as $k => $v) {
					if (in_array($k, $allowOrder)) {
						$builder = $builder->orderBy($k, $v == 'asc' ? 'asc' : 'desc');
					}
				}
			}
            $select = array(
                '(select name from domain where id=domain_id) as domain_name',
                'count(*) as total',
                'SUM(pv) as pvs',
                'SUM(uv) as uvs',
                'SUM(ip) as ips',
                'SUM(bot) as bots',
                'SUM(user) as users',
                'SUM(domestic) as domestics',
                'SUM(overseas) as overseass',
            );
			$records = $builder->selectRaw(implode(',', $select))
                ->offset(($page - 1) * self::$page_number)
                ->limit(self::$page_number)->get()->toArray();
            $data['records'] = $records;
            $data['pagination'] = Functions::pagination($data['count'], self::$page_number);
        }

        $data['currentName'] = 'statistic';
        return $this->view('statistic/count.twig', $data);

    }

    /**
     * 详细的访问记录
     * //@pattern /api/statistic/logs
     * @name home.api.statistic.logs
     * @method get
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function logs(Request $request, Response $response, $args)
    {
        $this->uid = 100;
        $data = [];
        //获取校验参数
        $page = (int)$request->getQueryParam('page') ?: 1;
        $domain_id = (int)$request->getQueryParam('domain_id', 0);
        $is_bot = (int)$request->getQueryParam('is_bot');
        $is_real_clicks = (int)$request->getQueryParam('is_real_clicks');
        $date_time = $request->getQueryParam('date_time');
        $limit = min((int)$request->getQueryParam('limit') ?: self::$page_number, 100);

        if ($domain_id) {
            if (($domainModel = \App\Models\Domain::find($domain_id)) && $domainModel->uid == $this->uid) {
                $data['currentDomain'] = $domainModel;
            } else {
                $domain_id = 0;
            }
        }

        //构造条件
        $builder = new \App\Models\DomainAccessLog();
        if ($domain_id) {
            $builder = $builder->where('domain_id', $domain_id)->isMy($this->uid);
        } else {
            $builder = $builder->isMy($this->uid);
        }
        if ($is_bot) {
            $builder = $builder->where('is_bot', $is_bot);
        }
        if ($is_real_clicks > 0) {
            $builder = $builder->where('is_real_clicks', $is_real_clicks - 1);
        }

        if ($date_time) {
            list($start_date, $end_date) = explode('-', $date_time);
            $start_date = strtotime($start_date);
            $end_date = strtotime($end_date);
            if ($start_date && $end_date) {
                $start_date = date('Y-m-d', $start_date).' 00:00:00';
                $end_date = date('Y-m-d', $end_date).' 23:59:59';
                $builder = $builder->whereBetween('created_at', [$start_date, $end_date]);
            }
        }

        //最终获取数据
        $data['count'] = $builder->count();
        $data['records'] = [];
        if ($data['count']) {
            $data['records'] = $builder->offset(($page - 1) * $limit)->limit($limit)->orderBy('id', 'desc')->get();
        }
        return $this->json($data);
    }

}