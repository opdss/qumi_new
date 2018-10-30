<?php
/**
 * Template.php for qumi
 * @author SamWu
 * @date 2018/7/11 11:13
 * @copyright boyaa.com
 */
namespace App\Models;

class Template extends Base
{
	/**
	 * 表名
	 * @var string
	 */
	protected $table = "template";

	/**
	 * 主键
	 * @var string
	 */
	protected $primaryKey = 'id';

    /**
     * 判断能不能使用的模版
     * @param $uid
     * @param $id
     * @return bool
     */
	public static function iCanUse($uid, $id)
	{
		$id = (int)$id;
		if ($id) {
			$model = self::find($id);
			if ($model) {
				return $model->uid == $uid;
			}
		}
		return false;
	}
}