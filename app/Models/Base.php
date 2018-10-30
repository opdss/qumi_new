<?php
/**
 * Base.php for deploy.
 * @author SamWu
 * @date 2017/8/2 10:52
 * @copyright istimer.com
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Base extends Model
{

	public function __construct(array $attributes = array())
	{
		parent::__construct($attributes);
		$this->getConnection()->enableQueryLog();
	}

	/**
	 * 多参数查询
	 * @param $query
	 * @param $arr
	 * @return mixed
	 */
	public function scopeMultiWhere($query, $arr)
	{
		if (!is_array($arr)) {
			return $query;
		}

		foreach ($arr as $key => $value) {
			$query = $query->where($key, $value);
		}
		return $query;
	}

	/**
	 * 过滤我的
	 * @param $query
	 * @param $uid
	 * @return mixed
	 */
	public function scopeIsMy($query, $uid)
	{
		return $query->where('uid', $uid);
	}

	/**
	 * 批量更新
	 * @param $query
	 * @param array $multipleData
	 * @param string $id
	 * @return bool|int
	 */
	public function scopeUpdateBatch($query, $multipleData = array(), $id='')
	{
		if(!empty($multipleData) ) {
			$n = 0;
			// column or fields to update
			$updateColumn = array_keys($multipleData[0]);
			$referenceColumn = $id ?: $updateColumn[0]; //e.g id
			foreach ($multipleData as $item) {
				if (self::where($referenceColumn, $item[$referenceColumn])->update($item)) {
					$n++;
				}
			}
			return $n;
		}
		return false;
	}

	/*public function scopeUpdateBatch($query, $multipleData = array(), $id='')
	{

		if(!empty($multipleData) ) {
			// column or fields to update
			$updateColumn = array_keys($multipleData[0]);
			$referenceColumn = $id ?: $updateColumn[0]; //e.g id
			$whereIn = "";

			$q = "UPDATE ".self::getTable()." SET ";
			foreach ( $updateColumn as $uColumn ) {
				if ($referenceColumn == $uColumn) {
					continue;
				}
				$q .=  $uColumn." = CASE ";
				foreach( $multipleData as $data ) {
					$q .= "WHEN ".$referenceColumn." = ".$data[$referenceColumn]." THEN '".$data[$uColumn]."' ";
				}
				$q .= "ELSE ".$uColumn." END, ";
			}
			foreach( $multipleData as $data ) {
				$whereIn .= "'".$data[$referenceColumn]."', ";
			}
			$q = rtrim($q, ", ")." WHERE ".$referenceColumn." IN (".  rtrim($whereIn, ', ').")";
			// Update
			return DB::update(DB::raw($q));

		}
		return false;
	}*/

	public static function getSql()
	{
		return (new self())->getConnection()->getQueryLog();
	}

}