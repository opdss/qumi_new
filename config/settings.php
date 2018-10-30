<?php
/**
 * settings.php for wa_poker.
 * @author SamWu
 * @date 2017/5/22 10:12
 * @copyright boyaa.com
 */

return array(
    'determineRouteBeforeAppMiddleware' => false,
    'displayErrorDetails' => true, // set to false in production
    'addContentLengthHeader' => false, // Allow the web server to send the content-length header
    'runTimeNotice' => 3, //程序运行超时通知（s）
    #'httpRequestTimeOut' => 60, //接口请求超时时间
    #'httpRequestSignKey' => '23e112310af3b657b0c88c5f35e94189', //接口请求签名字符串
    #'AccessTokenTimeOut' => 600, //接口请求签名字符串
);