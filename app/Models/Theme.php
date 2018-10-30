<?php
/**
 * Theme.php for qumi
 * @author SamWu
 * @date 2018/7/19 12:29
 * @copyright boyaa.com
 */
namespace App\Models;

class Theme extends Base
{
    const THEME_TYPE_DOMAIN = 0;
    const THEME_TYPE_MIBIAO = 1;
	/**
	 * 表名
	 * @var string
	 */
	protected $table = "theme";

	/**
	 * 主键
	 * @var string
	 */
	protected $primaryKey = 'id';

	/**
	 * 获取自己能使用的主题，包括默认
	 * @param $uid
	 * @param string $select
	 * @return mixed
	 */
	public static function getICanUse($uid, $select='*', $type = self::THEME_TYPE_DOMAIN)
	{
		return self::select($select)->where(function ($query) use ($uid) {
			$query->where('uid', '=', $uid)->orWhere('is_open', '=', 1);
		})->where('type', $type)->get();
	}

	/**
	 * 判断可不可以使用某个主题
	 * @param $uid
	 * @param $id
	 */
	public static function iCanUse($uid, $id, $type=Self::THEME_TYPE_DOMAIN)
	{
		$id = (int)$id;
		if ($id) {
			$model = self::find($id);
			if ($model) {
			    if ($model->type == $type) {
			        if ($model->uid) {
			            return $model->uid == $uid;
                    } else {
			            return $model->is_open == 1;
                    }
                }
			}
		}
		return false;
	}
}