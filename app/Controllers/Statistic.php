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
    public function __construct(Container $ci)
    {
        parent::__construct($ci);
        $this->addStaticsDir('bootstrap-datetimepicker');
        $this->addJs('/statics/bootstrap-datetimepicker/locales/bootstrap-datetimepicker.zh-CN.js');
    }

    /**
     * @pattern /statistic
     * @name statistic
     * @param Request $request
     * @param Response $response
     * @param $args
     */
    public function index(Request $request, Response $response, $args)
    {
        $data = array();
        $filter = [];
        $this->addJs('/statics/js/echarts.min.js');
        $data['currentName'] = $request->getAttribute('route')->getName();
        $data['domains'] = \App\Models\Domain::select('id', 'name')->isMy($this->uid)->get()->toArray();
        if ($this->uid == 100) {
        	$totalLog = DomainAccessLog::isMy($this->uid)->count();
        	$totalDomain = Domain::isMy($this->uid)->count();
        	//$realClicks = DomainAccessLogCount::
		}
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
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function day(Request $request, Response $response, $args)
    {
        $data = [];
        $allowOrder = array('domain_name', 'pv', 'ip', 'bot', 'user', 'domestic', 'overseas');

        //获取校验参数
        $page = (int)$request->getQueryParam('page') ?: 1;
        $filter['kw'] = $request->getQueryParam('kw');
        $filter['start_date'] = $request->getQueryParam('start_date');
        $filter['end_date'] = $request->getQueryParam('end_date');
        $filter['order_by'] = $request->getQueryParam('order_by');
        if (($domain_id = (int)$request->getQueryParam('domain_id')) && $domainModel = Domain::find($domain_id)) {
            $filter['kw'] = $domainModel->name;
        }
        $data['filter'] = $filter;

        //构造条件
        $builder = \App\Models\DomainAccessLogCount::isMy($this->uid);
        if ($filter['kw']) {
            $builder = $builder->where('domain_name', 'like', '%' . $filter['kw'] . '%');
        }

        if ($filter['start_date'] && $filter['end_date']) {
            $builder = $builder->whereBetween('day', [$filter['start_date'], $filter['end_date']]);
        } elseif ($filter['start_date']) {
            $builder = $builder->where('day', '>=', $filter['start_date']);
        } elseif ($filter['end_date']) {
            $builder = $builder->where('day', '<=', $filter['end_date']);
        }

        //最终获取数据
        $data['count'] = $builder->count();
        $data['records'] = [];
        if ($data['count']) {
            $builder = $builder->offset(($page - 1) * self::$page_number)->limit(self::$page_number);
            if ($filter['order_by'] && is_array($filter['order_by'])) {
                foreach ($filter['order_by'] as $k => $v) {
                    if (in_array($k, $allowOrder)) {
                        $builder = $builder->orderBy($k, $v == 'asc' ? 'asc' : 'desc');
                    }
                }
            } else {
                $builder = $builder->orderBy('day', 'desc');
            }
            $data['records'] = $builder->get();
            $data['pagination'] = Functions::pagination($data['count'], self::$page_number);
        }

        //获取其他数据
        $data['currentName'] = 'statistic';
        return $this->view('statistic/day.twig', $data);
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
     * @pattern /statistic/logs
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function logs(Request $request, Response $response, $args)
    {
        $data = [];

        //获取校验参数
        $page = (int)$request->getQueryParam('page') ?: 1;
        $filter['domain_id'] = (int)$request->getQueryParam('domain_id', 0);
        $filter['is_bot'] = (int)$request->getQueryParam('is_bot');
        $filter['start_date'] = $request->getQueryParam('start_date');
        $filter['end_date'] = $request->getQueryParam('end_date');
        if ($filter['domain_id']) {
            if (($domainModel = \App\Models\Domain::find($filter['domain_id'])) && $domainModel->uid == $this->uid) {
                $data['currentDomain'] = $domainModel;
            } else {
                $filter['domain_id'] = 0;
            }
        }
        $data['filter'] = $filter;

        //构造条件
        $builder = new \App\Models\DomainAccessLog();
        if ($filter['domain_id']) {
            $builder = $builder->where('domain_id', $filter['domain_id'])->isMy($this->uid);
        } else {
            $builder = $builder->isMy($this->uid);
        }
        if ($filter['is_bot']) {
            $builder = $builder->where('is_bot', $filter['is_bot'] - 1);
        }
        $filter['start_date'] = $filter['start_date'] ? $filter['start_date'] . ' 00:00:00' : false;
        $filter['end_date'] = $filter['end_date'] ? $filter['end_date'] . ' 23:59:59' : false;
        if ($filter['start_date'] && $filter['end_date']) {
            $builder = $builder->whereBetween('created_at', [$filter['start_date'], $filter['end_date']]);
        } elseif ($filter['start_date']) {
            $builder = $builder->where('created_at', '>=', $filter['start_date']);
        } elseif ($filter['end_date']) {
            $builder = $builder->where('created_at', '<=', $filter['end_date']);
        }

        //最终获取数据
        $data['count'] = $builder->count();
        $data['records'] = [];
        if ($data['count']) {
            $data['records'] = $builder->offset(($page - 1) * self::$page_number)->limit(self::$page_number)->orderBy('id', 'desc')->get();
            $data['pagination'] = Functions::pagination($data['count'], self::$page_number);
        }

        //获取其他数据
        $data['currentName'] = 'statistic';
        $data['domains'] = \App\Models\Domain::select('id', 'name')->isMy($this->uid)->get()->toArray();
        return $this->view('statistic/logs.twig', $data);
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
            return $this->json(40001);
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
}