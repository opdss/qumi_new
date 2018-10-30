<?php
/**
 * Created by PhpStorm.
 * User: wuxin
 * Date: 2018/7/28
 * Time: 11:54
 */
namespace App\Models;

class DomainAccessLogCount extends Base
{
    /**
     * 表名
     * @var string
     */
    protected $table = "domain_access_log_count";

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

	/**
	 * 每个域名访问的时候，都要调用这个访问，添加到统计表
	 * @param array $log
	 * @return bool
	 */
    public static function parseLogSave(DomainAccessLog $accessLog, $domain_name='')
    {
        $domain_id = $accessLog->domain_id;
        $uid = $accessLog->uid;
        $day = substr($accessLog->created_at, 0, 10);
        $isUser = $accessLog->is_bot ? 0 : (!$accessLog->cli_type || $accessLog->cli_type == 'UNK' || $accessLog->cli_type == 'library' ? 0 : 1);
        $isChina = strpos($accessLog->region, '中国') !== false || strpos($accessLog->region, '台湾') !== false ? 1 : 0;

        $model = self::where('domain_id', $domain_id)->where('day', $day)->first();

        if ($model) {
            $ipc = DomainAccessLog::where('domain_id', $domain_id)->where('ip', $accessLog->ip)->where('created_at', 'like', $day.'%')->count();
            $model->pv += 1;
            $model->bot += $accessLog->is_bot ? 1 : 0;
            $model->uv += $ipc>1 ? 0 : 1;
            $model->ip += $ipc>1 ? 0 : 1;
            $model->user += $isUser ? 1 : 0;
            if ($isChina) {
                $model->domestic += 1;
            } else {
                $model->overseas += 1;
            }
        } else {
            $model = new self();
            $model->uid = $uid;
            $model->domain_id = $domain_id;
            $model->domain_name = $domain_name;
            $model->pv = 1;
            //$model->uv = $r[0]['total'];
            //$model->ip = $r[0]['total'];
            $model->uv = 1;
            $model->ip = 1;
            $model->bot = $accessLog->is_bot ? 1 : 0;
            $model->user = $isUser ? 1 : 0;
            $model->domestic = $isChina ? 1 : 0;
            $model->overseas = $isChina ? 0 : 1;
            $model->day = $day;
        }
        return $model->save();
    }

}