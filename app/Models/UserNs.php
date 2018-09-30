<?php
/**
 * UserNs.php for qumi
 * @author SamWu
 * @date 2018/7/12 18:26
 * @copyright boyaa.com
 */
namespace App\Models;

use App\Functions;

class UserNs extends Base
{
	/**
	 * 表名
	 * @var string
	 */
	protected $table = "user_ns";

	/**
	 * 主键
	 * @var string
	 */
	protected $primaryKey = 'id';

    /**
     * 为一个用户生成dns专属服务地址
     * @param $uid
     * @return array|bool
     */
	public static function setDnsServer($uid)
    {
        $pre = Functions::decTo36($uid);
        //$pre = sprintf('%04s', Functions::decTo36($uid));
        $server = [$pre.'.ns1.istimer.com', $pre.'.ns2.istimer.com'];
        $servers = [];
        foreach ($server as $item) {
            $servers[] = [
                'uid'=>$uid,
                'server' => $item,
            ];
        }
        return self::insert($servers) ? $server : false;
    }

	/**
	 * 获取一个用户的dns
	 * @param $uid
	 * @return array
	 */
    public static function getDnsServer($uid)
	{
		$data = [];
		if ($uid && $res = self::where('uid', $uid)->get()) {
			$data = array_reduce($res->toArray(), function ($a, $b){$a[] = $b['server']; return $a;});
		}
		return $data;
	}
}