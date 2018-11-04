<?php
/**
 * Created by PhpStorm.
 * User: wuxin
 * Date: 2018/11/4
 * Time: 22:22
 */
namespace App\Models;

class DomainOffer extends Base
{
    /**
     * 表名
     * @var string
     */
    protected $table = "domain_offer";

    /**
     * 主键
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * 所属域名
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function domain()
    {
        return $this->belongsTo('\App\Models\Domain', 'domain_id', 'id');
    }

}