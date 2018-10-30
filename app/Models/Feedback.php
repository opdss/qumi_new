<?php
/**
 * Feedback.php for qumi
 * @author SamWu
 * @date 2018/9/12 16:24
 * @copyright boyaa.com
 */
namespace App\Models;

class Feedback extends Base
{
	/**
	 * 表名
	 * @var string
	 */
	protected $table = "feedback";

	/**
	 * 主键
	 * @var string
	 */
	protected $primaryKey = 'id';
}