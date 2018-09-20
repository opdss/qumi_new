<?php
/**
 * Functions.php for wa_poker.
 * @author SamWu
 * @date 2017/6/2 15:29
 * @copyright istimer.com
 */

namespace App;

use App\Libraries\Pagination;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Device\DeviceParserAbstract;
use Monolog;
use Opdss\Http\Request;

class Functions
{
	private static $obj = [];

	public static function formatApiData($param = 0, $data = array(), $extra = array(), $json = false)
	{
		$errMap = array(
			-1 => 'process',
			0 => 'success',
			1 => '处理失败',
			40001 => '参数错误',
			40002 => '上传文件为空',
			40100 => 'token参数为空',
			40101 => 'token已经失效',
			//表单类错误
			40110 => '账号或者密码错误',
			40111 => '账号不存在',
			40112 => '密码错误',
			40113 => '账号已经存在',
			40114 => '验证码错误',
			40115 => '更新对象错误',

			40190 => '签名错误',
			40191 => '请求频繁',
			40192 => '请求超时',

			40300 => '需要登录',
			40301 => '没有权限修改',
			40400 => '访问资源不存在',
			40500 => '访问方法不允许',
			50000 => '内部服务器错误',
		);
		if (is_numeric($param) || is_string($param)) {
			$code = is_numeric($param) ? (isset($errMap[$param]) ? $param : 50000) : 1;
			$msg = $code == 1 && is_string($param) ? $param : $errMap[$code];
			if ($code != 0) {
				is_string($data) AND $msg = $data;
				is_array($data) AND $extra = array_merge($data, $extra);
				$data = array();
			}
		} else {
			$code = 0;
			$msg = $errMap[$code];
			$extra = empty($data) ? array() : $data;
			$data = $param;
		}
		$ret = array(
			'errCode' => $code,
			'errMsg' => $msg,
		);
		empty($data) || $ret['data'] = $data;
		empty($extra) || $ret['extra'] = $extra;
		return $json ? json_encode($ret) : $ret;
	}

	/**
	 * @return \Monolog\Logger
	 */
	public static function getLogger()
	{
		if (!isset(self::$obj[__FUNCTION__])) {
			$level = Monolog\Logger::NOTICE;
			$lineFormatter = new Monolog\Formatter\LineFormatter("[%datetime%] %channel%.%level_name% => %message% %context% %extra%\n", "Y-m-d H:i:s.u");
			$uidProcessor = new Monolog\Processor\UidProcessor();
			$memoryUsageProcessor = new Monolog\Processor\MemoryUsageProcessor();
			$processIdProcessor = new Monolog\Processor\ProcessIdProcessor();
			$introspectionProcessor = new Monolog\Processor\IntrospectionProcessor($level);
			$streamHandler = new Monolog\Handler\StreamHandler(LOG_DIR .date('Y-m-d').'.log', $level);
			$streamHandler->setFormatter($lineFormatter);
			$_logger = new Monolog\Logger('qumi');
			$_logger->pushProcessor($introspectionProcessor)
				->pushProcessor($uidProcessor)
				->pushProcessor($memoryUsageProcessor)
				->pushProcessor($processIdProcessor);
			$_logger->pushHandler($streamHandler);
			self::$obj[__FUNCTION__] = $_logger;
		}
		return self::$obj[__FUNCTION__];
	}

	public static function getCache()
	{
		return \Opdss\Cicache\Cache::factory(\App\Libraries\Config::get('cache'));
	}

    /**
     * curl请求
     * @param $url
     * @param null $data 发送数据
     * @param string $method 请求方法
     * @param array $headers 请求头
     * @param null $cookies 携带cookie
     * @param array $options 其他标准curl选项
     * @param null $info 请求信息
     * @return mixed|null
     */
	public static function iCurl($url, $data = null, $method = 'get', array $headers = array(), $cookies = null, array $options = array(), &$info = null)
    {
        $method = strtoupper($method);
        if ($data) {
            if ($method == 'GET') {
                $data = is_array($data) ? http_build_query($data) : $data;
                $url = strpos($url, '?') !== false ? $url . '&' . $data : $url . '?' . $data;
                $curl = curl_init($url);
            } else {
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
        } else {
            $curl = curl_init($url);
        }
        //设置选项
        curl_setopt_array($curl, array(
            CURLOPT_TIMEOUT => 30, //超市时间
            CURLOPT_CUSTOMREQUEST => $method,// 请求方法
            CURLOPT_RETURNTRANSFER => true,// 返回内容
            CURLOPT_HEADER => false,// 返回header
            CURLOPT_FOLLOWLOCATION => true,// 自动重定向
            CURLOPT_SSL_VERIFYPEER => false,// 不校验证书
        ));

        //设置头信息
        if (!empty($headers)) {
            $_headers = [];
            foreach ($headers as $name => $value) { //处理成CURL可以识别的headers格式
                $_headers[] = $name . ':' . $value;
            }
            curl_setopt($curl, CURLOPT_HTTPHEADER, $_headers);
        }
        //设置cookie
        if (!empty($cookies)) {
            $_cookies = '';
            if (is_array($cookies)) {
                foreach ($cookies as $name => $value) {
                    $_cookies .= "{$name}={$value}; ";
                }
            } else {
                $_cookies = $cookies;
            }
            curl_setopt($curl, CURLOPT_COOKIE, $_cookies);
        }
        //其他特殊选项
        if (!empty($options)) {
            curl_setopt_array($curl, $options);
        }
        //执行请求
        $output = curl_exec($curl);
        $info = curl_getinfo($curl);
		$error = curl_error($curl);
        curl_close($curl);
        if ($info['http_code'] == 200) {
            return $output;
        }
        $info['error'] = $error;
        $info['output'] = $output;
        return null;
    }

	public static function getIP()
	{
		$ip = $_SERVER['REMOTE_ADDR'];
		if (isset($_SERVER['HTTP_CDN_SRC_IP'])) {
			$ip = $_SERVER['HTTP_CDN_SRC_IP'];
		} elseif (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
			foreach ($matches[0] AS $xip) {
				if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
					$ip = $xip;
					break;
				}
			}
		}
		return $ip;
	}

	/**
	 * @return Redis
	 */
	public static function genRandStr($num = 16, $has=true)
	{
		$num = intval($num) ?: 16;
		$str = '1234567890';
		#$str1 = '~!@#$%^&*()_+={}|\][<>?/';
		$str1 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
		if ($has) {
			$str .= $str1;
		}
		$str = str_shuffle($str);
		$len = strlen($str);
		$res = '';
		for ($i=0; $i<$num; $i++) {
			$res .= $str[rand(0, $len-1)];
		}
		return $res;
	}


	public static function getMicroTime()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}


	public static function runTime($name, $isGet = false)
	{
		static $_times = array();
		if ($isGet) {
			return isset($_times[$name]) ? (self::getMicroTime() - $_times[$name]) : 0;
		} else {
			$_times[$name] = self::getMicroTime();
			return $_times[$name];
		}
	}


    /**
     * @desc im:十进制数转换成三十六机制数
     * @param (int)$num 十进制数
     * return 返回：三十六进制数
     */
    public static function decTo36($num) {
        $num = intval($num);
        if ($num <= 0)
            return false;
        $charArr = array("0","1","2","3","4","5","6","7","8","9",'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');
        $char = '';
        do {
            $key = ($num - 1) % 36;
            $char= $charArr[$key] . $char;
            $num = floor(($num - $key) / 36);
        } while ($num > 0);
        return $char;
    }

	/**
	 * 获取一个域名的dns服务器
	 * @param $domain
	 * @return array|bool
	 */
    public static function getDomainDnsServer($domain)
	{
		$exitCode = 0;
		$output = null;
		exec('whois '.$domain. ' | grep Name\ Server',$output, $exitCode);
		if ($exitCode > 0) {
			self::getLogger()->error('whois exec error: '.$exitCode.'!', array('domain'=>$domain, 'res'=>$output));
			return false;
		}
		if (empty($output) || count($output) != 2) {
			self::getLogger()->notice('whois exec notice!', array('domain'=>$domain, 'res'=>$output));
			return false;
		}
		$dns = [];
		foreach ($output as $one) {
			$_arr = preg_split('/[:|：]/', $one);
			if (isset($_arr[1]) && $_arr[1]) {
				$dns[] = trim($_arr[1]);
			}
		}
		return $dns;
	}

	public static function whois($domain, $field='')
	{
		if (!$domain) {
			return null;
		}
		$exitCode = 0;
		$output = null;
		//脚本执行限制3秒
		exec('timeout 3 whois '.$domain,$output, $exitCode);
		$data = [];
		if ($exitCode == 0 && $output) {
			foreach ($output as $item) {
				if (strpos($item, 'DNSSEC') !== false) {
					break;
				}
				$res = preg_split('/[:|：]/', $item, 2);
				if (count($res) == 2) {
					$k = trim($res[0]);
					if (isset($data[$k])) {
						if (is_array($data[$k])) {
							$data[$k][] = trim($res[1]);
						} else {
							$data[$k] = [$data[$k], trim($res[1])];
						}
					} else {
						$data[$k] = trim($res[1]);
					}
				}
			}
		}
		if (!$data) {
			self::getLogger()->error('whois error:'.$domain);
			return $data;
		}
		if ($field) {
			return isset($data[$field]) ? $data[$field] : null;
		}
		return $data;
	}

	/**
	 * 用来处理批量传过来的int型id
	 * @param $id
	 * @param int $number
	 * @param string $errMsg
	 * @return array|bool
	 */
	public static function formatIds($id, $number=100, &$errMsg = '')
	{
		if (empty($id)) {
			$errMsg = 'ID参数错误！';
			return false;
		}
		if(is_string($id) && strpos($id, ',') !== false) {
			$id = explode(',', $id);
		}

		$ids = [];
		if (is_numeric($id)) {
			$id>0 AND $ids[] = $id;
		} elseif(is_array($id)) {
			$ids = array_filter($id, function($item){
				if (is_numeric($item) && $item > 0) {
					return true;
				}
				return false;
			});
		}
		if (empty($ids)) {
			$errMsg = 'ID参数解析错误！';
			return false;
		}
		if (count($ids) > $number) {
			$errMsg = '批量操作最多'.$number.'个！';
			return false;
		}
		return $ids;
	}

    /**
     * 处理清洗批量过来的字符串域名
     * @param $domainStr
     * @return array
     */
	public static function formatDomains($domainStr)
    {
        if (!$domainStr) {
            return [];
        }
        //$domainStr = " aas.cpp,aa.cpp\ngg.com www.aa.com     -gg.app,,,,,,2a.co，gwa.gawg.cc gaga-ga.com";
        $domainStr = preg_replace("/[\r|\n|\t| |，|,]+/", ',', $domainStr);
        $domains = [];
        $preg = '/^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,8}/';
        foreach (explode(',', $domainStr) as $domain) {
			$domain = strtolower(trim($domain));
            //去掉前缀
            if (substr_count($domain, '.') > 1) {
                $_arr = explode('.', $domain);
                $c = count($_arr);
                $domain = $_arr[$c-2].'.'.$_arr[$c-1];
            }
            //校验和过滤重复
            if (preg_match($preg, $domain) && !in_array($domain, $domains)) {
                $domains[] = $domain;
            }
        }
        return $domains;
    }


	/**
	 *	调用淘宝接口查询ip地理地址
	 * 1. 请求接口（GET）：http://ip.taobao.com/service/getIpInfo.php?ip=[ip地址字串]
	 * 2. 响应信息：（json格式的）国家 、省（自治区或直辖市）、市（县）、运营商
	 * 3. 返回数据格式Json：
	 * 其中code的值的含义为，0：成功，1：失败。
	 * @return bool|string
	 */
	public static function getIpAddressByTb($ip){
		$res = Request::get('http://ip.taobao.com/service/getIpInfo.php?ip='.$ip);
		if ($res->getBody()) {
			$json = json_decode($res->getBody(), true);
			if ($json['code'] == 0) {
				return $json['data']['country'].$json['data']['region'];
			}
		} else {
		    $res = Request::get('http://opendata.baidu.com/api.php?query='.$ip.'&resource_id=6006&format=json');
            if ($res->getBody()) {
                $json = json_decode($res->getBody(), true);
                if ($json['status'] == 0) {
                    return explode(' ', $json['data'][0]['location'])[0];
                }
            }
        }
		return '';
	}

	public static function getIpAddress($ip)
	{
		$cache = self::getCache();
		$cacheKey = 'ip_'.$ip;
		if (!($location = $cache->get($cacheKey))) {
			$req = Request::factory();
			$req->timeout(2000);
			$req->retry(2);
			//淘宝
			$res = $req->get('http://ip.taobao.com/service/getIpInfo.php?ip='.$ip);
			if ($res->getBody()) {
				$json = json_decode($res->getBody(), true);
				if ($json['code'] == 0) {
					$location = $json['data']['country'].$json['data']['region'];
				}
			} else {
				self::getLogger()->error('getIpAddress taobao error', [$res->getCurlInfo()]);
			}
			//百度
			if (!$location) {
				$res = $req->get('http://opendata.baidu.com/api.php?query='.$ip.'&resource_id=6006&ie=utf8&oe=utf-8&format=json');
				if ($res->getBody()) {
					$json = json_decode($res->getBody(), true);
					if ($json['status'] == 0) {
						$location = explode(" ", $json['data'][0]['location']);
						$location = $location[0];
					}
				} else {
					self::getLogger()->error('getIpAddress baidu error', [$res->getCurlInfo()]);
				}
			}

			if ($location) {
				$cache->save($cacheKey, $location, 86400*3);
			}
		}
		return $location ?: '';
	}

	public static function verifyUrl($url)
	{
		$preg = "/\b(?:(?:https?|http):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i";
		if (preg_match($preg,$url)) {
			return true;
		}
		return false;
	}

	public static function verifyDomainPrefix($prefix)
	{
		$preg = "/[0-9]{1,}/i";
		if (preg_match($preg, $prefix)) {
			return true;
		}
		return false;
	}

	public static function runLocalCommand($command, &$log=[])
	{
		$exitCode = 0;
		$output = null;
		exec($command,$output, $exitCode);
		$log = array('command'=>$command, 'output'=>$output, 'exitCode'=>$exitCode);
		if ($exitCode == 0) {
			self::getLogger()->alert('exec command success ', $log);
			return true;
		}
		self::getLogger()->error('exec command error', $log);
		return false;
	}

	/**
	 * 提取域名
	 * @param $domain
	 * @return string
	 */
	public static function trimDomain($domain)
	{
        if (filter_var($domain, FILTER_VALIDATE_IP)) {
            return $domain;
        }
		if (substr_count($domain, '.') > 1) {
			$_arr = explode('.', $domain);
			$c = count($_arr);
			$domain = $_arr[$c-2].'.'.$_arr[$c-1];
		}
		return $domain;
	}

	public static function getFullUrl()
    {
        $url = '';
        if (PHP_SAPI != 'cli' && $_SERVER) {
            $url = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].($_SERVER['REQUEST_URI'] == '/' ? '' : $_SERVER['REQUEST_URI']);
        }
        return $url;
    }

	public static function parseUrl($url)
    {
        $info = null;
        if ($url) {
            $info = parse_url($url);
            if ($info && $info['host']) {
                $info['suffix'] = '';
                $info['domain'] = '';
                $info['prefix'] = '';
                $host = $info['host'];
                if (!filter_var($host, FILTER_VALIDATE_IP)){
                    $_arr = explode('.', $host);
                    $_c = count($_arr);
                    if ($_c > 1) {
                        $info['suffix'] = $_arr[$_c - 1];
                        $info['domain'] = $_arr[$_c - 2] . '.' . $_arr[$_c - 1];
                        if ($_c > 2) {
                            $info['prefix'] = str_replace('.' . $info['domain'], '', $host);
                        } else {
                            $info['prefix'] = '';
                        }
                    }
                }
            }
        }
        return $info;
    }

	/**
	 * @return \Twig_Environment
	 */
	public static function getTwig()
	{
		if (!isset(self::$obj[__FUNCTION__])) {
			$settings = \App\Libraries\Config::get('twig');
			$loader  = new \Twig_Loader_Filesystem($settings['template_path']);
			self::$obj[__FUNCTION__] = new \Twig_Environment($loader, $settings['options']);
			//$res = new \Twig_Environment($loader, $settings['options']);
			self::$obj[__FUNCTION__]->addExtension(new \App\Libraries\MyTwigExtension());
		}
		return self::$obj[__FUNCTION__];
	}

	public static function pagination($total, $pageSize = 10)
    {
        $temp = array(
			'total' => '<li><span>共<b>{#total}</b>条</span></li>',
			'info' => '<li><span>每页显示<b>{#pageSize}</b>条，本页<b>{#start}-{#end}</b>条</span></li>',
			'limit' => '<li><span><b>{#page}/{#pageTotal}</b>页</span></li>',
            'go_page' => '<li><input type="text" onkeydown="javascript:if(event.keyCode==13){var page=(this.value>{#pageTotal})?{#pageTotal}:this.value;location=\'{#baseUrl}&{#pageName}=\'+page;}" value="{#page}" style="width:25px"><input type="button" value="GO" onclick="javascript:var page=(this.previousSibling.value>{#pageTotal})?{#pageTotal}:this.previousSibling.value;location=\'{#baseUrl}&{#pageName}=\'+page;"></li>',

            'fl_active' => '<li><a href="{#url}"><span aria-hidden="true">{#title}</span></a></li>', //首页尾页有链接模版
            'fl_not_active' => '<li><a href="{#url}"><span aria-hidden="true">{#title}</span></a></li>', //首页尾页没有链接模版
            'pn_active' => '<li><a href="{#url}"><span aria-hidden="true">{#title}</span></a></li>',
            'pn_not_active' => '<li><a href="{#url}"><span aria-hidden="true">{#title}</span></a></li>',
            'list_active' => '<li><a href="{#url}">{#page}</a></li>', //分页列表没有链接模版
            'list_not_active' => '<li><a href="{#url}">{#page}</a></li>',//分页列表有链接模版
        );
        $config = array(
            "prev" => "<<",
            "next" => ">>",
            "first" => "首 页",
            "last" => "尾 页",
            'div_prev' => '<nav aria-label="Page navigation small"><ul class="pagination">',
            'div_next' => '</ul></nav>'
        );
        $pagination = new Pagination($total, $pageSize);
        return $pagination->setTemp($temp)->setConfig($config)->fpage(array(0, 3, 4, 5, 6, 7));
    }

	/**
	 * 根据user-agent解析客户端信息
	 * @param $ua
	 * @return array
	 */
	public static function getUaInfo($ua)
	{
		DeviceParserAbstract::setVersionTruncation(DeviceParserAbstract::VERSION_TRUNCATION_NONE);
		$dd = new DeviceDetector($ua);
		$dd->parse();
		if ($dd->isBot()) {
			$_bot = $dd->getBot();
			$res = [
				'is_bot' => '1',
				'cli_type' => $_bot && isset($_bot['category']) ? $_bot['category'] : '',
				'cli_name' => $_bot && isset($_bot['name']) ? $_bot['name'] : '',
				'cli_version' => '',
				'os_name' => $_bot && isset($_bot['producer']['name']) ? $_bot['producer']['name'] : '',
				'os_version' => '',
				'device_name' => 'bot'
			];
		} else {
			$res = [
				'is_bot' => '0',
				'cli_type' => $dd->getClient('type'),
				'cli_name' => $dd->getClient('name'),
				'cli_version' => (string)$dd->getClient('version'),
				'os_name' => $dd->getOs('name'),
				'os_version' => (string)$dd->getOs('version'),
				'device_name' => $dd->getDeviceName()
			];
		}
		return $res;
	}

	public static function parseDate($date, $fmt='Y-m-d') {
		if (!is_array($date)) {
			if ($ts = strtotime($date)) {
				return date($fmt, $ts);
			}
			return false;
		} else {
			$t0 = strtotime($date[0]);
			$t1 = strtotime($date[1]);
			if ($t0 && $t1) {
				$t0 = min($t0, $t1);
				$t1 = max($t0, $t1);
				return [date($fmt, $t0), $t1 > time() ? date($fmt) : date($fmt, $t1)];
			}
		}
	}

	/**
	 * 处理记录域名的访问日志
	 * @param $domainModel
	 * @return Models\DomainAccessLog
	 */
	public static function saveAccessLog($domainModel, $from='')
	{
		$ip = \App\Functions::getIP();

		$regin = \App\Functions::getIpAddress($ip);
		if (!$regin) {
		    self::getLogger()->notice('get Ip Address error : '.$ip);
        }
        $from = $from ?: ($_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].($_SERVER['REQUEST_URI'] == '/' ? '' : $_SERVER['REQUEST_URI']));
		$ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$accessLogModel = new \App\Models\DomainAccessLog();
		$accessLogModel->domain_id = $domainModel->id;
		$accessLogModel->uid = $domainModel->uid;
		$accessLogModel->url = $from;
		$accessLogModel->ip = $ip;
		$accessLogModel->user_agent = $ua;
		$accessLogModel->region = $regin;
		$accessLogModel->referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		$accessLogModel->created_at = date('Y-m-d H:i:s');
		//解析user-agent信息
		$uaInfo = \App\Functions::getUaInfo($ua);
		foreach ($uaInfo as $k=>$v) {
			$accessLogModel->{$k} = $v;
		}
		$accessLogModel->save() AND \App\Models\DomainAccessLogCount::parseLogSave($accessLogModel, $domainModel->name);//统计表处理
		return $accessLogModel;
	}

	/**
	 * 手工301跳转
	 * @param $url
	 */
	public static function redirect($url, $status=301)
	{
	    if ($status == 301) {
            header('HTTP/1.1 301 Moved Permanently');
        }
		header('Location: '.$url);
		exit();
	}

	public static function sendEmail($email, $content)
    {
        return true;
    }

}

