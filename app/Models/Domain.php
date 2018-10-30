<?php
/**
 * Created by PhpStorm.
 * User: wuxin
 * Date: 2018/7/11
 * Time: 00:02
 */
namespace App\Models;

use App\Functions;

use Illuminate\Database\Eloquent\SoftDeletingTrait;

class Domain extends Base
{
	const COIN_UNIT_CNY = 'CNY'; //人民币
	const COIN_UNIT_USD = 'USD'; //美元
	const COIN_UNIT_GBP = 'GBP'; //英镑
	const COIN_UNIT_EUR = 'EUR'; //欧元

	static public $coin_unit = [
		self::COIN_UNIT_CNY,
		self::COIN_UNIT_USD,
		self::COIN_UNIT_GBP,
		self::COIN_UNIT_EUR
	];

    use SoftDeletingTrait;

    /**
     * 表名
     * @var string
     */
    protected $table = "domain";

    /**
     * 主键
     * @var string
     */
    protected $primaryKey = 'id';

    public static $dtype = [
    	1 => '字母',
		2 => '数字',
		3 => '杂'
	];

	/**
	 * 所属域名
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function user()
	{
		return $this->belongsTo('\App\Models\User', 'uid', 'uid');
	}

    /**
     * 判断可不可以使用某个域名
     * @param $uid
     * @param $id
     */
    public static function iCanUse($uid, $id)
    {
        $id = (int)$id;
        if ($id) {
            $model = self::find($id);
            if ($model) {
                if ($model->uid = $uid && $model->dns_status == 1) {
                	return $model;
				}
            }
        }
        return false;
    }

	/**
	 * @param $query
	 * @return mixed
	 */
	public function scopeIsDns($query)
	{
		return $query->where('dns_status', 1);
	}

	/**
	 * 头字母大写的域名
	 * @return string
	 */
	public function getUcNameAttribute()
	{
		return ucwords($this->name);
	}

	/**
	 * 去掉后缀的域名
	 * @return bool|string
	 */
	public function getDoNameAttribute()
	{
		return substr($this->name, 0, strpos($this->name, '.'));
	}

	/**
	 * 获取所有后缀
	 * @param bool $andCount 是否要统计数量
	 * @return array
	 */
	public static function getAllSuffix($andCount = false, $dns_status = 1)
	{
		$select = $andCount ? 'suffix, count(*) as count' : 'suffix';
		$cacheKey = 'domain:suffix:' . ($andCount? 'allCount' : 'all').$dns_status; //缓存键
		$cache = Functions::getCache();
		$data = [];
		//获取缓存
		if ($cacheVal = $cache->get($cacheKey)) {
			$data = $cacheVal;
		} else {
			$res = self::selectRaw($select)->where('dns_status', $dns_status)->groupBy('suffix')->get();
			if ($andCount) {
				$data = $res->toArray();
			} else {
				foreach ($res as $item) {
					$data[] = $item->suffix;
				}
			}
			//缓存十分钟
			$cache->save($cacheKey, $data, 600);
		}
		return $data;
	}

	public static function getAllLen($andCount = false, $dns_status = 1)
	{
		$select = $andCount ? 'len, count(*) as count' : 'len';
		$cacheKey = 'domain:len:' . ($andCount? 'allCount' : 'all').$dns_status; //缓存键
		$cache = Functions::getCache();
		$data = [];
		//获取缓存
		if ($cacheVal = $cache->get($cacheKey)) {
			$data = $cacheVal;
		} else {
			$res = self::selectRaw($select)->where('dns_status', $dns_status)->groupBy('len')->get();
			if ($andCount) {
				$data = $res->toArray();
			} else {
				foreach ($res as $item) {
					$data[] = $item->len;
				}
			}
			//缓存十分钟
			$cache->save($cacheKey, $data, 600);
		}
		return $data;
	}
}