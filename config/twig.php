<?php
/**
 * Created by PhpStorm.
 * User: wuxin
 * Date: 2017/6/20
 * Time: 23:24
 */

return array(
        'template_path' => TPL_DIR,
        'options' => array(
            'cache' => CACHE_DIR,
            //'debug' => false,
            'debug' => !ONLINE ? true : false,
            //'debug' => true,
            'auto_reload' => true
        )
);