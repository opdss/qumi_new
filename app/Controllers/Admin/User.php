<?php
/**
 * User.php for qumi
 * @author SamWu
 * @date 2018/9/12 16:00
 * @copyright boyaa.com
 */
namespace App\Controllers\Admin;

use App\Models\UserNs;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class User
 * @middleware App\Middleware\Auth|App\Middleware\Rtime
 * @package App\Controllers\Admin
 */
class User extends \App\Controllers\Base
{
    /**
     * @pattern /admin/user
     * @auth admin|用户管理
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function index(Request $request, Response $response)
    {
        $data = array();
        //模板列表
        $data['currentName'] = $request->getAttribute('route')->getName();
        return $this->view('admin/user/index.twig', $data);
    }

    /**
     * @pattern /api/admin/users
     * @auth admin
     * @name api.admin.user.get
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function get(Request $request, Response $response)
    {
        $data = array();
        $page = (int)$request->getParam('page') ?: 1;
        $kw = trim($request->getQueryParam('kw', ''));
        $order_name = trim($request->getQueryParam('order_name'));
        $order_type = trim($request->getQueryParam('order_type'));
        $limit = (int)$request->getQueryParam('limit') ?: self::$page_number;
        $limit = min($limit, 100);

        $builder = new \App\Models\User();
        if ($kw !== '') {
            $builder = $builder->where('email', 'like', '%' . $kw . '%');
        }

        $data['count'] = $builder->count();
        $data['records'] = [];
        if ($data['count']) {
            if ($order_type && $order_type != 'null' && in_array($order_name, array('uid', 'email', 'login_time', 'login_num'))) {
                $builder = $builder->orderBy($order_name, $order_type);
            }
            $data['records'] = $builder->offset(($page - 1) * $limit)->limit($limit)->get()->toArray();
            $data['records'] = array_map(function ($arr){
                $arr['domainCount'] = \App\Models\Domain::where('uid', $arr['uid'])->count();
                return $arr;
            }, $data['records']);
        }
        return $this->json($data);
    }
    /**
     * @pattern /api/admin/vlogin
     * @method post
     * @auth admin
     * @name api.admin.vlogin
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function vloginApi(Request $request, Response $response, $args)
    {
        $uid = intval($request->getParsedBodyParam('uid'));
        if (!$userObj = \App\Models\User::find($uid)) {
            return $this->json(3);
        }
        $userInfo = $userObj->toArray();
        //获取处理用户的ns，放到session
        $userInfo['dns_server'] = UserNs::getDnsServer($userInfo['uid']);
        $userInfo['dns_server_str'] = implode('<br/>',$userInfo['dns_server']);
        //测试 end
        $this->session->set('userInfo', $userInfo);
        $redirectUrl =  $this->ci->router->pathFor('index');
        return $this->json(array('redirect_url'=>$redirectUrl));
    }

}