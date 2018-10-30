<?php
/**
 * crontab-genssl.php for qumi
 * @author SamWu
 * @date 2018/7/17 17:54
 * @copyright boyaa.com
 */
//公共定义文件
require_once dirname(__DIR__).'/common.php';

\App\Libraries\Config::setConfigPath(CONFIG_DIR);

//设置数据库
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection(\App\Libraries\Config::get('mysql'));
$capsule->setAsGlobal();
$capsule->bootEloquent();

$act = 'cc';

/********* 获取更新ip地理位置 start***************/
if ($act === 'ip') {
	$flag = true;
	while ($flag) {
		$res = \App\Models\DomainAccessLog::where('region', '')->limit(100)->get()->toArray();
		if ($res) {
			foreach ($res as $item) {
				$region = \App\Functions::getIpAddress($item['ip']);
				\App\Models\DomainAccessLog::where('id', $item['id'])->update(array('region' => $region));
				echo "id:{$item['id']}, IP:{$item['ip']}, $region" . PHP_EOL;
			}
		} else {
			$flag = false;
			break;
		}
	}
}
/********* 获取更新ip地理位置 end***************/



/********* 获取更新ip地理位置 start***************/
if ($act === 'cc') {
		$res = \App\Models\DomainAccessLogCount::all()->toArray();
		if ($res) {
			foreach ($res as $item) {
				$acInfo = \App\Models\DomainAccessLog::where('domain_id', $item['domain_id'])->where('created_at', 'like', $item['day'].'%')->get()->toArray();
				$zh = $en = 0;
				foreach ($acInfo as $_info) {
					if (strpos($_info['region'], '中国') !== false || strpos($_info['region'], '台湾') !== false) {
						$zh++;
					} else {
						$en++;
					}
				}
				\App\Models\DomainAccessLogCount::where('id', $item['id'])->update(array('domestic'=>$zh, 'overseas'=>$en));
				echo "id:{$item['id']},domain_id:{$item['domain_id']},".json_encode(array('domestic'=>$zh, 'overseas'=>$en)).PHP_EOL;
			}
		}

}

/********* 获取更新ip地理位置 end***************/


/*--prefix=/usr/share/nginx --sbin-path=/usr/sbin/nginx --modules-path=/usr/lib64/nginx/modules --conf-path=/etc/nginx/nginx.conf --error-log-path=/var/log/nginx/error.log --http-log-path=/var/log/nginx/access.log --http-client-body-temp-path=/var/lib/nginx/tmp/client_body --http-proxy-temp-path=/var/lib/nginx/tmp/proxy --http-fastcgi-temp-path=/var/lib/nginx/tmp/fastcgi --http-uwsgi-temp-path=/var/lib/nginx/tmp/uwsgi --http-scgi-temp-path=/var/lib/nginx/tmp/scgi --pid-path=/run/nginx.pid --lock-path=/run/lock/subsys/nginx --user=nginx --group=nginx --with-file-aio --with-ipv6 --with-http_auth_request_module --with-http_ssl_module --with-http_v2_module --with-http_realip_module --with-http_addition_module --with-http_xslt_module=dynamic --with-http_image_filter_module=dynamic --with-http_geoip_module=dynamic --with-http_sub_module --with-http_dav_module --with-http_flv_module --with-http_mp4_module --with-http_gunzip_module --with-http_gzip_static_module --with-http_random_index_module --with-http_secure_link_module --with-http_degradation_module --with-http_slice_module --with-http_stub_status_module --with-http_perl_module=dynamic --with-mail=dynamic --with-mail_ssl_module --with-pcre --with-pcre-jit --with-stream=dynamic --with-stream_ssl_module --with-google_perftools_module --with-debug --with-cc-opt='-O2 -g -pipe -Wall -Wp,-D_FORTIFY_SOURCE=2 -fexceptions -fstack-protector-strong --param=ssp-buffer-size=4 -grecord-gcc-switches -specs=/usr/lib/rpm/redhat/redhat-hardened-cc1 -m64 -mtune=generic' --with-ld-opt='-Wl,-z,relro -specs=/usr/lib/rpm/redhat/redhat-hardened-ld -Wl,-E' --add-module=/home/wuxin/ngx_http_substitutions_filter_module --add-module=/home/wuxin/ngx_http_google_filter_module

--prefix=/usr/share/nginx --sbin-path=/usr/sbin/nginx --modules-path=/usr/lib64/nginx/modules --conf-path=/etc/nginx/nginx.conf --error-log-path=/var/log/nginx/error.log --http-log-path=/var/log/nginx/access.log --http-client-body-temp-path=/var/lib/nginx/tmp/client_body --http-proxy-temp-path=/var/lib/nginx/tmp/proxy --http-fastcgi-temp-path=/var/lib/nginx/tmp/fastcgi --http-uwsgi-temp-path=/var/lib/nginx/tmp/uwsgi --http-scgi-temp-path=/var/lib/nginx/tmp/scgi --pid-path=/run/nginx.pid --lock-path=/run/lock/subsys/nginx --user=nginx --group=nginx --with-file-aio --with-ipv6 --with-http_auth_request_module --with-http_ssl_module --with-http_v2_module --with-http_realip_module --with-http_addition_module --with-http_xslt_module=dynamic --with-http_image_filter_module=dynamic --with-http_geoip_module=dynamic --with-http_sub_module --with-http_dav_module --with-http_flv_module --with-http_mp4_module --with-http_gunzip_module --with-http_gzip_static_module --with-http_random_index_module --with-http_secure_link_module --with-http_degradation_module --with-http_slice_module --with-http_stub_status_module --with-http_perl_module=dynamic --with-mail=dynamic --with-mail_ssl_module --with-pcre --with-pcre-jit --with-stream=dynamic --with-stream_ssl_module --with-google_perftools_module --with-debug --with-cc-opt='-O2 -g -pipe -Wall -Wp,-D_FORTIFY_SOURCE=2 -fexceptions -fstack-protector-strong --param=ssp-buffer-size=4 -grecord-gcc-switches -specs=/usr/lib/rpm/redhat/redhat-hardened-cc1 -m64 -mtune=generic' --with-ld-opt='-Wl,-z,relro -specs=/usr/lib/rpm/redhat/redhat-hardened-ld -Wl,-E' --add-module=/root/ngx_http_substitutions_filter_module --add-module=/root/ngx_http_google_filter_module*/