<?php
/**
 * MyTwigExtension.php for qumi
 * @author SamWu
 * @date 2018/8/7 14:49
 * @copyright boyaa.com
 */
namespace App\Libraries;

class MyTwigExtension extends \Twig_Extension
{

	private static $langfix = [
		'zh',
		'en',
		'jp',
		'ru',
	];

	private $lang = [];

	public function __construct()
	{
		$this->lang = Config::get('lang');
	}

	public function getName()
	{
		return 'slim';
	}

	public function getFunctions()
	{
		return [
			new \Twig_SimpleFunction('lang', array($this, 'lang')),
		];
	}

    /**
     * 切换语言
     * @param array ...$params
     * @return mixed
     */
	public function lang(...$params)
	{
		$langfix = isset($_GET['l']) && in_array($_GET['l'], self::$langfix) ? $_GET['l'] : 'zh';
		if (count($params) == 1) {
			$param = $params[0];
			if (is_array($param)) {
				return isset($param[$langfix]) ? $param[$langfix] : array_values($param)[0];
			} else {
				if (isset($this->lang[$param])) {
					return $this->lang[$param][$langfix] ? $this->lang[$param][$langfix] : $this->lang[$param]['zh'];
				}
				return $param;
			}
		} else {
			$idx = array_search($langfix, self::$langfix);
			return isset($params[$idx]) ? $params[$idx] : $params[0];
		}
	}
}
