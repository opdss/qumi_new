<?php
/**
 * Statistic.php for qumi
 * @author SamWu
 * @date 2018/7/24 10:25
 * @copyright boyaa.com
 */
namespace App\Controllers;

use App\Functions;
use App\Models\Domain;
use App\Models\DomainAccessLog;
use App\Models\DomainAccessLogCount;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class Statistic
 * @middleware App\Middleware\Auth|App\Middleware\Rtime
 * @package App\Controllers
 */
class Statistic extends Base
{
    /**
     * @pattern /statistic
     * @auth user|数据统计|数据表
     * @name statistic
     * @param Request $request
     * @param Response $response
     * @param $args
     */
    public function index(Request $request, Response $response, $args)
    {
        $data = array();
        $filter = [];
        $data['domains'] = \App\Models\Domain::select('id', 'name')->isMy($this->uid)->get()->toArray();
        return $this->view('statistic/index.twig', $data);
    }

    /**
     * 域名访问详情
     * @pattern /statistic/detail[/{id:[0-9]+}]
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

    public function echarts(Request $request, Response $response, $args)
    {
        $startDay = date('Y-m-d', strtotime('-7 day'));
        $endDay = date('Y-m-d');
        $domain_id = $args['id'];

        $builder = DomainAccessLogCount::where('domain_id', $domain_id)->whereBetween('day', [$startDay, $endDay]);
        $_res = $builder->get()->toArray();
        $res = [];
        foreach ($_res as $item) {
            $res[$item['day']] = $item;
        }

        $data = [];
        $legendData = [
            'pv' => 'PV',
            'ip' => 'IP',
            'bot' => '搜索引擎',
            'user' => '自然用户',
            'domestic' => '国内用户',
            'overseas' => '国外用户'
        ];
        $xAxisData = [];
        $_series = [];
        $startTime = strtotime($startDay);
        for ($i = 0; $i <= 30; $i++) {
            $day = date('Y-m-d', $startTime + ($i * 86400));
            if ($day != $endDay) {
                $xAxisData[] = $day;
                foreach ($legendData as $k => $v) {
                    $_series[$k][] = isset($res[$day]) ? $res[$day][$k] : 0;
                }
            } else {
                break;
            }
        }
        $series = [];
        foreach ($legendData as $k => $v) {
            $series[] = [
                'name' => $v,
                'type' => 'line',
                'stack' => '总量',
                'data' => $_series[$k]
            ];
        }
        return [
            'legendData' => json_encode(array_values($legendData)),
            'xAxisData' => json_encode($xAxisData),
            'series' => json_encode($series)
        ];
    }

    /**
     * @pattern /statistic/day
     * @auth user|数据统计|每日统计
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function day(Request $request, Response $response, $args)
    {
        $data = [];
        $data['currDay'] = date('Y-m-d');
        return $this->view('statistic/day.twig', $data);
    }

    /**
     * @pattern /api/statistic/day
     * @name api.statistic.day
     * @method get
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function dayApi(Request $request, Response $response, $args)
    {
        $data = [];
        $allowOrder = array('domain_name', 'pv', 'ip', 'bot', 'user', 'domestic', 'overseas', 'real_clicks', 'day');

        //获取校验参数
        $page = (int)$request->getQueryParam('page') ?: 1;
        $kw = trim($request->getQueryParam('kw', ''));
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
            if ($kw !== '') {
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
     * @pattern /statistic/count
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function count(Request $request, Response $response, $args)
    {
        $data = array();
        return $this->view('statistic/count.twig', $data);
    }

	/**
	 * @pattern /api/statistic/count
	 * @name api.statistic.count
	 * @method get
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return mixed
	 */
	public function countApi(Request $request, Response $response, $args)
	{
		$data = array();
		$page = (int)$request->getQueryParam('page') ?: 1;
		$kw = trim($request->getQueryParam('kw', ''));
		$order_name = trim($request->getQueryParam('order_name'));
		$order_type = trim($request->getQueryParam('order_type'));
		$limit = min((int)$request->getQueryParam('limit') ?: self::$page_number, 100);

		$allowOrder = array('domain_name', 'pvs', 'ips', 'bots', 'users', 'domestics', 'overseass', 'realclicks');

		//这是一个分组查询
		$builder = \App\Models\DomainAccessLogCount::isMy($this->uid)->groupBy('domain_id');
		if ($kw !== '') {
			$builder = $builder->where('domain_name', 'like', '%' . $kw . '%');
		}

		$data['count'] = count($builder->selectRaw('domain_id')->get()->toArray());
		$data['records'] = [];
		if ($data['count']) {
			if ($order_type && $order_type != 'null' && in_array($order_name, $allowOrder)) {
				$builder = $builder->orderBy($order_name, $order_type);
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
				'SUM(real_clicks) as realclicks',
			);
			$records = $builder->selectRaw(implode(',', $select))
				->offset(($page - 1) * $limit)
				->limit($limit)->get()->toArray();
			$data['records'] = $records;
		}
		return $this->json($data);
	}

    /**
     * 详细的访问记录
     * @pattern /statistic/logs
     * @auth user|数据统计|访问详情
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function logs(Request $request, Response $response, $args)
    {
        $data = [];
        $data['currDay'] = date('Y-m-d');
        $data['domains'] = \App\Models\Domain::select('id', 'name')->isMy($this->uid)->get()->toArray();
        return $this->view('statistic/logs.twig', $data);
    }


    /**
     * 详细的访问记录
     * @pattern /api/statistic/logs
     * @name api.statistic.logs
     * @method get
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function logsApi(Request $request, Response $response, $args)
    {
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

    /**
     * @pattern /api/statistic/echarts_count
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function echarts_count(Request $request, Response $response, $args)
    {
        $start_date = (int)$request->getQueryParam('start_date');
        $end_date = (int)$request->getQueryParam('end_date');
        $start_date = date('Y-m-d', strtotime('-15day'));
        $end_date = date('Y-m-d');

        $_res = DomainAccessLogCount::selectRaw(implode(',', array(
            'day',
            'count(*) as total',
            'SUM(pv) as pvs',
            'SUM(uv) as uvs',
            'SUM(ip) as ips',
            'SUM(bot) as bots',
            'SUM(user) as users',
            'SUM(domestic) as domestics',
            'SUM(overseas) as overseass',
        )))->groupBy('day')->isMy($this->uid)->whereBetween('day', [$start_date, $end_date])->get();

        $res = [];
        if ($_res) {
            foreach ($_res as $item) {
                $res[$item['day']] = $item;
            }
        }

        $legendData = [
            'pvs' => 'PV',
            'ips' => 'IP',
            'bots' => '搜索引擎',
            'users' => '自然用户',
            'domestics' => '国内用户',
            'overseass' => '国外用户'
        ];
        $xAxisData = [];
        $_series = [];
        $startTime = strtotime($start_date);
        for($i = 0; $i <= 30; $i++) {
            $day = date('Y-m-d', $startTime+($i*86400));
            if ($day != $end_date) {
                $xAxisData[] = $day;
                foreach ($legendData as $k => $v) {
                    $_series[$k][] = isset($res[$day]) ? $res[$day][$k] : 0;
                }
            } else {
                break;
            }
        }
        $series = [];
        foreach ($legendData as $k => $v) {
            $series[] = [
                'name' => $v,
                'type' => 'line',
                'stack' => '总量',
                'data' => $_series[$k]
            ];
        }
        $records = [
            'legendData' => (array_values($legendData)),
            'xAxisData' => ($xAxisData),
            'series' => ($series)
        ];
        return $this->json($records);
    }

    /**
     * @pattern /api/statistic/echarts_detail
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function echarts_detail(Request $request, Response $response, $args)
    {
        $domain_id = (int)$request->getQueryParam('domain_id');
        $start_date = (int)$request->getQueryParam('start_date');
        $end_date = (int)$request->getQueryParam('end_date');
        $start_date = date('Y-m-d', strtotime('-15day'));
        $end_date = date('Y-m-d');

        if (!$domain_id || !($domainModel = Domain::find($domain_id)) || $domainModel->uid != $this->uid) {
            return $this->json(3);
        }

        $_res = DomainAccessLogCount::selectRaw(implode(',', array(
            'day',
            'pv as pvs',
            'uv as uvs',
            'ip as ips',
            'bot as bots',
            'user as users',
            'domestic as domestics',
            'overseas as overseass',
        )))->where('domain_id', $domain_id)->whereBetween('day', [$start_date, $end_date])->get();
        $res = [];
        foreach ($_res as $item) {
            $res[$item['day']] = $item;
        }

        $legendData = [
            'pvs' => 'PV',
            'ips' => 'IP',
            'bots' => '搜索引擎',
            'users' => '自然用户',
            'domestics' => '国内用户',
            'overseass' => '国外用户'
        ];
        $xAxisData = [];
        $_series = [];
        $startTime = strtotime($start_date);
        for($i = 0; $i <= 30; $i++) {
            $day = date('Y-m-d', $startTime+($i*86400));
            if ($day != $end_date) {
                $xAxisData[] = $day;
                foreach ($legendData as $k => $v) {
                    $_series[$k][] = isset($res[$day]) ? $res[$day][$k] : 0;
                }
            } else {
                break;
            }
        }
        $series = [];
        foreach ($legendData as $k => $v) {
            $series[] = [
                'name' => $v,
                'type' => 'line',
                'stack' => '总量',
                'data' => $_series[$k]
            ];
        }
        $records = [
            'legendData' => (array_values($legendData)),
            'xAxisData' => ($xAxisData),
            'series' => ($series)
        ];
        return $this->json($records);
    }



    /**
     * 域名后缀统计 --饼状图
     * @pattern /api/statistic/echarts/suffix
     * @auth user
     * @name api.statistic.echarts.suffix
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function echartsSuffix(Request $request, Response $response, $args)
    {
        $data = [];
        $res = \App\Models\Domain::isMy($this->uid)->selectRaw('count(*) as cc, suffix')->groupBy('suffix')->get()->toArray();
        foreach ($res as $item) {
            $data['legendData'][] = '.'.$item['suffix'];
            $data['seriesData'][] = ['name'=>'.'.$item['suffix'], 'value'=>$item['cc']];
        }
        return $this->json($data);
    }

    /**
     * 昨日的最高访问统计
     * @pattern /api/statistic/echarts/yestoday
     * @auth user
     * @name api.statistic.echarts.yestoday
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function echartsYestoday(Request $request, Response $response, $args)
    {
        $data = [];
        $res = \App\Models\Domain::isMy($this->uid)->selectRaw('count(*) as cc, suffix')->groupBy('suffix')->get()->toArray();
        foreach ($res as $item) {
            $data['legendData'][] = '.'.$item['suffix'];
            $data['seriesData'][] = ['name'=>'.'.$item['suffix'], 'value'=>$item['cc']];
        }
        return $this->json($data);
    }

    /**
     * 所有域名总访问统计 --折线图数据
     * @pattern /api/statistic/echarts/count
     * @auth user
     * @name api.statistic.echarts.count
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function echartsCount(Request $request, Response $response, $args)
    {
        //最近 7 15 30 天
        $days = (int)$request->getQueryParam('days');
        $days = in_array($days, [7, 15, 30]) ? $days : 15;

        $start_date = date('Y-m-d', strtotime('-'.$days.'day'));
        $end_date = date('Y-m-d');

        $_res = DomainAccessLogCount::selectRaw(implode(',', array(
            'day',
            'count(*) as total',
            'SUM(pv) as pvs',
            'SUM(uv) as uvs',
            'SUM(ip) as ips',
            'SUM(bot) as bots',
            'SUM(user) as users',
            'SUM(domestic) as domestics',
            'SUM(overseas) as overseass',
            'SUM(real_clicks) as realclicks',
        )))->groupBy('day')->isMy($this->uid)->whereBetween('day', [$start_date, $end_date])->get();

        $res = [];
        if ($_res) {
            foreach ($_res as $item) {
                $res[$item['day']] = $item;
            }
        }

        $legendData = [
            'pvs' => 'PV',
            'ips' => 'IP',
            'bots' => '搜索引擎',
            'users' => '自然用户',
            'domestics' => '国内IP',
            'overseass' => '国外IP',
            'realclicks' => '真实用户'
        ];
        $xAxisData = [];
        $_series = [];
        $startTime = strtotime($start_date);
        for($i = 0; $i <= 30; $i++) {
            $day = date('Y-m-d', $startTime+($i*86400));
            if ($day != $end_date) {
                $xAxisData[] = $day;
                foreach ($legendData as $k => $v) {
                    $_series[$k][] = isset($res[$day]) ? $res[$day][$k] : 0;
                }
            } else {
                break;
            }
        }
        $series = [];
        foreach ($legendData as $k => $v) {
            $series[] = [
                'name' => $v,
                'type' => 'line',
                'stack' => '总量',
                'data' => $_series[$k]
            ];
        }
        $records = [
            'legendData' => (array_values($legendData)),
            'xAxisData' => ($xAxisData),
            'series' => ($series)
        ];
        return $this->json($records);
    }

    /**
     * @pattern /api/statistic/echarts/domain
     * @auth user
     * @name api.statistic.echarts.domain
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function echartsDomain(Request $request, Response $response, $args)
    {
        $domain_id = (int)$request->getQueryParam('domain_id');
        //最近 7 15 30 天
        $days = (int)$request->getQueryParam('days');
        $days = in_array($days, [7, 15, 30]) ? $days : 15;
        $start_date = date('Y-m-d', strtotime('-'.$days.'day'));
        $end_date = date('Y-m-d');

        if (!$domain_id || !($domainModel = Domain::find($domain_id)) || $domainModel->uid != $this->uid) {
            return $this->json(3);
        }

        $_res = DomainAccessLogCount::selectRaw(implode(',', array(
            'day',
            'pv as pvs',
            'uv as uvs',
            'ip as ips',
            'bot as bots',
            'user as users',
            'domestic as domestics',
            'overseas as overseass',
            'real_clicks as realclicks',
        )))->where('domain_id', $domain_id)->whereBetween('day', [$start_date, $end_date])->get();
        $res = [];
        foreach ($_res as $item) {
            $res[$item['day']] = $item;
        }

        $legendData = [
            'pvs' => 'PV',
            'ips' => 'IP',
            'bots' => '搜索引擎',
            'users' => '自然用户',
            'domestics' => '国内IP',
            'overseass' => '国外IP',
            'realclicks' => '真实用户'
        ];
        $xAxisData = [];
        $_series = [];
        $startTime = strtotime($start_date);
        for($i = 0; $i <= 30; $i++) {
            $day = date('Y-m-d', $startTime+($i*86400));
            if ($day != $end_date) {
                $xAxisData[] = $day;
                foreach ($legendData as $k => $v) {
                    $_series[$k][] = isset($res[$day]) ? $res[$day][$k] : 0;
                }
            } else {
                break;
            }
        }
        $series = [];
        foreach ($legendData as $k => $v) {
            $series[] = [
                'name' => $v,
                'type' => 'line',
                'stack' => '总量',
                'data' => $_series[$k]
            ];
        }
        $records = [
            'legendData' => (array_values($legendData)),
            'xAxisData' => ($xAxisData),
            'series' => ($series)
        ];
        return $this->json($records);
    }

}