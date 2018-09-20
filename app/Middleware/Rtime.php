<?php
/**
 * Created by PhpStorm.
 * 统计个控制器运行时间
 * User: wuxin
 * Date: 2018/8/21
 * Time: 21:49
 */
namespace App\Middleware;

use App\Libraries\Email;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

use \App\Functions;

class Rtime
{
    /**
     * @var Container
     */
    protected $ci;

    /**
     * Auth constructor.
     * @param Container $ci
     */
    public function __construct(Container $ci)
    {
        $this->ci = $ci;
    }


    /**
     * @param Request $request
     * @param Response $response
     * @param $next
     * @return Response
     */
    public function __invoke(Request $request, Response $response, $next)
    {
        $key = 'RTIME';
        Functions::runTime($key); //进入时间
        $response = $next($request, $response);
        $runTimeNotice = isset($this->ci->settings['runTimeNotice']) ? $this->ci->settings['runTimeNotice'] : 5;
        $runTime = Functions::runTime($key, true);
        if ($runTime >= $runTimeNotice) {
            //Functions::sendEmail('opdss@qq.com', '超时拉！');
            $log = [
                'router' => $request->getAttribute('route')->getName(),
                'params' => $request->getParams(),
                'url' => $request->getServerParam('REQUEST_SCHEME').'://'.$request->getServerParam('HTTP_HOST').($request->getServerParam('REQUEST_URI') == '/' ? '' : $request->getServerParam('REQUEST_URI'))
            ];
            //Email::factory()->send('opdss@qq.com', json_encode($log), '超时拉！');
            $this->ci->logger->notice('程序运行超时:'.$runTime, $log);
        }
        return $response;
    }
}