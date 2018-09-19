<?php
/**
 * Created by PhpStorm.
 * User: wuxin
 * Date: 2017/6/23
 * Time: 23:50
 */

$site = array(
	'homepage' => HOMEPAGE,
	'title' => '趣米停靠站|趣米APP(qumi.app)',
	'author' => '趣米',
    'slogan' => '专业的域名停靠站',
	'keyword' => '趣米,Qumi,趣米APP,Qumi.app,米表,域名停靠',
	'description' => '趣米APP停靠站，专业的域名停靠站，app域名自动申请证书和完全的自定义主题展示模版。',
	//'copyright' => 'Copyright © 2017  istimer.com </br>技术支持：<a href="mailto:wux@tsingning.com" style="color: #2a76fe	;">@阿新</a>',
	'page_number' => 20, //每页分页数量
	'mibiao_max_number' => 5, //每个用户米表总数
	'copyright' => '©2018 <a href="https://Qumi.app">Qumi. </a>All rights reserved.',
	'icp' => '<a href="http://www.miibeian.gov.cn/"></a>',
	'contact' => '联系人：<a href="mailto:opdss@qq.com">阿新</a>',
	'version' => '0.7.0',
    'isRedirct' => true,//是否开启重定向
	'tongji' => '',
    'menu' => array(
        ['key' => 'ucenter', 'name' => '个人中心', 'role'=>array('member','admin')],
        ['key' => 'mibiao', 'name' => '我的米表', 'role'=>array('member','admin')],
        ['key' => 'domain', 'name' => '域名管理', 'role'=>array('member','admin')],
        ['key' => 'template', 'name' => '模版管理', 'role'=>array('member','admin')],
        ['key' => 'statistic', 'name' => '数据统计', 'role'=>array('member','admin')],
		['key' => 'redirect', 'name' => '域名跳转', 'role'=>array('member','admin')],
        ['key' => 'admin.user', 'name' => '用户列表', 'role'=>array('admin')],
        ['key' => 'admin.domain', 'name' => '域名列表', 'role'=>array('admin')],
    ),
    'email' => ['username'=>'sys@qumi.app', 'password'=>'Qwerasdf12345'],
	'seo' => [
		//米表
		'm' => [
			'keyword' => '',
			'description' => '',
		],
		//详情
		'd' => [
			'keyword' => '',
			'description' => '',
		]
	],
	'godaddyDNS' => ['ns1.wuxin.info', 'ns2.wuxin.info'], //狗爹的专属dns服务器
);

return $site;