<?php
/**
 * Article.php for qumi
 * @author SamWu
 * @date 2018/8/3 11:12
 * @copyright boyaa.com
 */
namespace App\Models;

class Article extends Base
{
	/**
	 * 表名
	 * @var string
	 */
	protected $table = "article";

	/**
	 * 主键
	 * @var string
	 */
	protected $primaryKey = 'id';
}