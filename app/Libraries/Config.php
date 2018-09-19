<?php
/**
 * Config.php for deploy.
 * 动态载入配置文件的数据
 * @author SamWu
 * @date 2017/7/19 19:49
 * @copyright istimer.com
 */
namespace App\Libraries;

/**
 * Class Config
 * @package App\Libraries
 */
class Config
{
    /**
     * 当前已载入配置
     * @var array
     */
	private static $config = array();

    /**
     * 配置目录
     * @var null
     */
	private static $configDir = null;

    /**
     * 是否已载入所有配置，防止重复读取
     * @var bool
     */
	private static $readAll = false;

    /**
     * 设置config目录
     * @param $configDir
     * @return bool
     * @throws \Exception
     */
	public static function setConfigPath($configDir)
	{
		if (!$configDir || !is_dir($configDir)) {
			throw new \Exception('配置目录设置错误：'.$configDir);
		}
        self::$configDir = rtrim($configDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		return true;
	}

	/**
	 * 读取config配置
	 * get('db')
	 * or get('db,menu')
	 * or get(array('db', 'menu'))
	 * or get('db', 'menu')
	 * @param array ...$params
	 * @return array|mixed
	 */
	public static function get(...$params)
	{
		$args = count($params);
		if ($args == 0) {
			return self::all();
		}
        if ($args == 1) {
            $key = $params[0];
            $key = is_string($key) && strpos($key, ',') !== false ? explode(',', $key) : $key;
            if (is_string($key)) {
                return self::_readConfig($key);
            }
            if (is_array($key)) {
                return self::_getConfigByArr($key);
            }
        }
        if ($args > 1) {
            return self::_getConfigByArr($params);
        }
	}

    /**
     * 所有配置
     * @return array|mixed
     */
	public static function all()
	{
		return self::_readConfig();
	}

    /**
     * 获取当前已载入配置
     * @return array
     */
	public static function getCurrentConfig()
    {
        return self::$config;
    }

	public static function __callStatic($name, $arguments)
	{
		$conf = self::_readConfig($name);
		$data = array();
		if (!empty($conf)) {
			if (is_array($conf) && count($arguments) > 0 && ($key = $arguments[0]) && !is_array($key)) {
				$data = isset($conf[$key]) ? $conf[$key] : $data;
			} else {
				$data = $conf;
			}
		}
		return $data;
	}

	private static function _getConfigByArr(array $arr)
	{
		$res = array();
		foreach ($arr as $item) {
			$res[$item] = self::_readConfig($item);
		}
		return $res;
	}

    /**
     * 读取配置文件的配置
     * @param string $name 对应的文件名，为空则读取所有
     * @return array|mixed
     * @throws \Exception
     */
	private static function _readConfig($name = '')
	{
		if (!self::$configDir) {
            throw new \Exception('没有设置config目录');
		}
		if ($name) {
		    if (!isset(self::$config[$name])) {
		        $file = self::$configDir.$name.'.php';
		        if (file_exists($file)) {
                    self::$config[$name] = include($file);
                } else {
                    self::$config[$name] = array();
                }
            }
            return self::$config[$name];
        } elseif (!self::$readAll) {
			$handler = opendir(self::$configDir);
			while (($filename = readdir($handler)) !== false) {//务必使用!==，防止目录下出现类似文件名“0”等情况
				if ($filename != "." && $filename != "..") {
					if (substr($filename, -4) == '.php') { //只读取.php后缀的配置文件
                        $name = substr($filename, 0, -4);
                        if (!isset(self::$config[$name])) {
                            self::$config[$name] = include(self::$configDir . $filename);
                        }
					}
				}
			}
			closedir($handler);
            self::$readAll = true;
		}
		return self::$config;
	}
}