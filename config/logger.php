<?php
/**
 * logger.php for wa_poker.
 * @author SamWu
 * @date 2017/5/22 10:15
 * @copyright boyaa.com
 */
return array(
        'name' => 'isnoter',
        'path' => LOG_DIR . 'isnoter-'.date('Ymd').'.log',
        'fm_output' => "[%datetime%] %channel%.%level_name% => %message% %context% %extra%\n",
        'fm_datetime' => "Y-m-d H:i:s.u",
        'level' => 100
);