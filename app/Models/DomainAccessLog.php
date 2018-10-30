<?php
/**
 * Created by PhpStorm.
 * User: wuxin
 * Date: 2018/7/14
 * Time: 01:02
 */
namespace App\Models;

class DomainAccessLog extends Base
{
    /**
     * 表名
     * @var string
     */
    protected $table = "domain_access_log";

    /**
     * 主键
     * @var string
     */
    protected $primaryKey = 'id';

	/**
	 * @var bool
	 */
	public $timestamps = false;

    /**
     * 所属域名
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function domain()
    {
        return $this->belongsTo('\App\Models\Domain', 'domain_id', 'id');
    }

}