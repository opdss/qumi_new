<?php
/**
 * Created by PhpStorm.
 * User: wuxin
 * Date: 2018/8/26
 * Time: 22:11
 */
namespace App\Models;

class DomainRedirect extends Base
{
	const REDIRECT_STATUS_A = 301;
	const REDIRECT_STATUS_B = 302;

	public static $redirect_status = [
		self::REDIRECT_STATUS_A,
		self::REDIRECT_STATUS_B
	];

    /**
     * 表名
     * @var string
     */
    protected $table = "domain_redirect";

    /**
     * 主键
     * @var string
     */
    protected $primaryKey = 'id';
}