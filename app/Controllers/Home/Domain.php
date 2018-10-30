<?php
/**
 * Created by PhpStorm.
 * User: wuxin
 * Date: 2018/9/17
 * Time: 12:07
 */
namespace App\Controllers\Home;

use App\Controllers\Base;
use App\Functions;
use App\Libraries\Config;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class Domain
 * @package App\Controllers\Home
 */
class Domain extends Base
{
    /**
     * 域名管理首页
     * @pattern ///domain
     * @param Request $request
     * @param Response $response
     * @param $args
     */
    public function index(Request $request, Response $response, $args)
    {
        $data = [];
        return $this->view('home/domain/index.twig', $data);
    }

    /**
     * 我的域名列表
     * //@pattern /api/domain
     * @name home .api.domain.get
     * @auth user|域名管理
     * @method get
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function get(Request $request, Response $response, $args)
    {
        $this->uid = 100;
        $data = array();
        $filter = [];
        $filter['page'] = (int)$request->getParam('page') ?: 1;
        $filter['kw'] = trim($request->getQueryParam('kw', ''));
        $filter['template_id'] = (int)$request->getParam('template_id');
        $filter['dns_status'] = (int)$request->getParam('dns_status');
        $filter['order_name'] = $request->getQueryParam('order_name');
        $filter['order_type'] = $request->getQueryParam('order_type');
        $limit = (int)$request->getQueryParam('limit') ?: self::$page_number;
        $limit = min($limit, 100);

        $builder = \App\Models\Domain::isMy($this->uid);
        if ($filter['kw']) {
            $builder = $builder->where('name', 'like', '%' . $filter['kw'] . '%');
        }
        if ($filter['dns_status'] > 0) {
            $builder = $builder->where('dns_status', $filter['dns_status'] - 1);
        }
        if ($filter['template_id'] > 0) {
            $builder = $builder->where('template_id', $filter['template_id']);
        }

        $data['count'] = $builder->count();
        $data['records'] = [];
        if ($data['count']) {
            if ($filter['order_type'] && $filter['order_type'] != 'null' && in_array($filter['order_name'], array('id', 'name', 'price'))) {
                $builder = $builder->orderBy($filter['order_name'], $filter['order_type']);
            }
            $data['records'] = $builder->offset(($filter['page'] - 1) * $limit)->limit($limit)->orderBy('id', 'desc')->get();
        }
        return $this->json($data);
    }

    /**
     * 删除域名
     * 支持批量删除
     * @pattern /api/domain
     * @name home .api.domain.del
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

        $res = \App\Models\Domain::whereIn('id', $ids)->isMy($this->uid)->delete();
        if ($res) {
            return $this->json(0);
        }
        $this->log('error', 'domain.del error', [$ids]);
        return $this->json(1);

    }

    /**
     * 检查dns服务器来确认是否属于当前用户的域名
     * //@pattern /api/domain/dnsCheck
     * @name home .api.domain.dnscheck
     * @method get
     * @param Request $request
     * @param Response $response
     * @param $args
     */
    public function dnsCheck(Request $request, Response $response, $args)
    {
        $errMsg = '';
        $domain_id = $request->getParam('domain_id');
        $domain_ids = Functions::formatIds($domain_id, self::BATCH, $errMsg);
        if (!$domain_ids) {
            $this->log('error', $errMsg, $domain_id);
            return $this->json(3, $errMsg);
        }

        $domains = \App\Models\Domain::whereIn('id', $domain_ids)->isMy($this->uid)->get()->toArray();
        if (empty($domains)) {
            $this->ci->logger->error('[uid:' . $this->uid . '] 非法域名id', $domain_ids);
            return $this->json(3);
        }
        //用户的ns服务器地址
        $userNsArr = array_map(function ($item) {
            return $item['server'];
        }, $this->userInfo['dns_server']);

        $DNS = array_merge(Config::site('godaddyDNS'), $userNsArr);

        $result = [];
        $successIds = [];
        foreach ($domains as $item) {
            $check = false;
            if ($item['dns_status']) {
                $check = true;
            } else {
                //获取该域名的dns服务器
                $whois = Functions::whois($item['name']);
                if ($whois) {
                    //有的是大写 转换一下, 追后面跟一个.也行 - -！
                    if (isset($whois['Name Server'])) {
                        $whois['Name Server'] = array_map(function ($a) {
                            return trim(strtolower($a), '.');
                        }, $whois['Name Server']);
                    }
                    if (isset($whois['Name Server']) && count(array_intersect($whois['Name Server'], $DNS)) == 2) {
                        $check = true;
                        $successIds[] = $item['id'];
                    }
                }
            }
            $result[] = array(
                'domain_id' => $item['id'],
                'domain' => $item['name'],
                'dnsCheck' => $check
            );
        }
        if (!empty($successIds)) {
            //成功之后修改dns校验的状态字段
            if (!\App\Models\Domain::whereIn('id', $successIds)->update(array('dns_status' => 1))) {
                $this->log('error', 'update dns_status error', $successIds);
            }
        }
        return $this->json($result);
    }

    /**
     * 添加域名
     * @pattern /api/domain
     * @name home .api.domain.create
     * @method post
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return static
     */
    public function create(Request $request, Response $response, $args)
    {
        $domains = Functions::formatDomains($request->getParsedBodyParam('domains'));
        if (empty($domains)) {
            return $this->json(3);
        }
        $description = trim($request->getParsedBodyParam('description', ''));
        $price = (int)$request->getParsedBodyParam('price', '');
        $sale_type = (int)$request->getParsedBodyParam('sale_type', 0);
        $template_id = $request->getParsedBodyParam('template_id', 0);
        if ($sale_type) {
            $price = 0;
        }

        //获取用户已经添加的域名
        $existsArray = [];
        $exists = \App\Models\Domain::select('name')->whereIn('name', $domains)->isMy($this->uid)->get()->toArray();
        if ($exists) {
            $existsArray = array_map(function ($item) {
                return $item['name'];
            }, $exists);
        }
        $insertData = array();
        foreach ($domains as $domain) {
            //过滤掉已经存在的域名
            if ($existsArray && in_array($domain, $existsArray)) {
                continue;
            }
            $insertData[] = array(
                'uid' => $this->uid,
                'name' => $domain,
                'description' => $description,
                'price' => $price,
                'sale_type' => $sale_type,
                'suffix' => substr($domain, strpos($domain, '.') + 1),
                'template_id' => $template_id,
                'dns_status' => 0,
                'dtype' => $this->getDtype($domain),
                'len' => strpos($domain, '.')
            );
        }
        if (!$insertData || \App\Models\Domain::insert($insertData)) {
            return $this->json(0);
        }
        return $this->json(1);
    }

    /**
     * 更新域名信息
     * 支持批量
     * @pattern /api/domain
     * @name home .api.domain.update
     * @method put
     * @param Request $request
     * @param Response $response
     * @param $args
     */
    public function update(Request $request, Response $response, $args)
    {
        $domain_id = $request->getParsedBodyParam('domain_id', null);
        $description = $request->getParsedBodyParam('description', '');
        $price = $request->getParsedBodyParam('price', 0);
        $sale_type = $request->getParsedBodyParam('sale_type', 0);
        $template_id = $request->getParsedBodyParam('template_id', 0);

        if (!$domain_id) {
            return $this->json(3);
        }
        //如果是修改一个的话，包装成数组交给下面去处理
        if (is_numeric($domain_id)) {
            if (!$domain_id = intval($domain_id)) {
                return $this->json(3);
            }
            $domain_id = [$domain_id];
            $description = [$description];
            $price = [$price];
            $sale_type = [$sale_type];
            $template_id = [$template_id];
        }
        //数据清洗处理
        if (is_array($domain_id)) {
            //获取属于用户的域名
            $existsIds = [];
            $exists = \App\Models\Domain::select('id')->whereIn('id', $domain_id)->isMy($this->uid)->get()->toArray();
            if ($exists) {
                $existsIds = array_map(function ($item) {
                    return $item['id'];
                }, $exists);
            }
            if (empty($existsIds)) {
                //应该是恶意修改
                $this->log('error', 'update domain error', $request->getParams());
                return $this->json(3, '你要修改的域名有问题！');
            }
            $data = [];
            foreach ($domain_id as $k => $_domain_id) {
                //只能修改属于的我的域名
                if (in_array($_domain_id, $existsIds)) {
                    $_sale_type = (int)$sale_type[$k];
                    $data[] = array(
                        'id' => $_domain_id,
                        'description' => trim($description[$k]),
                        'sale_type' => $_sale_type,
                        'price' => $_sale_type ? 0 : intval($price[$k]),
                        'template_id' => intval($template_id[$k])
                    );
                }
            }
            if (\App\Models\Domain::updateBatch($data, 'id')) {
                return $this->json(0);
            }
        }
        return $this->json(1);
    }

    private function getDtype($domain)
    {
        if (preg_match('/^[a-zA-Z]+\.[a-zA-Z]+$/', $domain)) {
            $dtype = 1;
        } elseif (preg_match('/^[0-9]+\.[a-zA-Z]+$/', $domain)) {
            $dtype = 2;
        } else {
            $dtype = 3;
        }
        return $dtype;
    }
}