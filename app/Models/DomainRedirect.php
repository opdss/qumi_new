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