<?php
/**
 * carousel.php for qumi
 * @author SamWu
 * @date 2018/7/30 18:21
 * @copyright boyaa.com
 */

//首页的焦点大图，暂时放在这里配置吧

return array(
	'cfg' => [
		'width'=>'100%',
		'height'=>'420px',
		'default'=> 1,
		'target'=> '_blank'
	],
	'records' => array(
		//array('image'=>'/statics/images/t/1.jpg', 'url'=> '', 'description'=> '', 'title'=> ''),
		array('image'=>'/statics/images/t/2.jpg', 'url'=> 'http://qumi.app/article/1', 'description'=> '', 'title'=> '如何使用本站停靠域名'),
		//array('image'=>'/statics/images/t/3.png', 'url'=> '', 'description'=> '极品四声', 'title'=> ''),
		array('image'=>'/statics/images/t/4.png', 'url'=> 'https://croquis.app', 'description'=> '极品英文单词米', 'title'=> 'croquis.app'),
		array('image'=>'/statics/images/t/npc.app.jpg', 'url'=> 'https://npc.app', 'description'=> 'NPC', 'title'=> 'npc.app'),
		array('image'=>'/statics/images/t/sicbo.app.jpg', 'url'=> 'https://sicbo.app', 'description'=> 'sicbo', 'title'=> 'sicbo.app'),
	)
);