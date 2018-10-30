<?php
/**
 * Created by PhpStorm.
 * User: wuxin
 * Date: 2018/9/12
 * Time: 21:46
 */
namespace App\Models;

class EmailQueue extends Base
{
    /**
     * 表名
     * @var string
     */
    protected $table = "email_queue";

    /**
     * 主键
     * @var string
     */
    protected $primaryKey = 'id';
}