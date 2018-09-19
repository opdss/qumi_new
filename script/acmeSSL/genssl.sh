#!/usr/bin/env bash

#ssl证书自动申请脚本
DOMAIN=$1

if [ ! $DOMAIN ]
then
    echo '请输入域名'
    exit 1
fi

#脚本执行根目录
ROOT_PATH=$(cd "$(dirname "$0")"; pwd)

#acme.脚本
ACME_SH='/root/.acme.sh/acme.sh'

#nginx配置模版
NGINX_CONF_TEMP=${ROOT_PATH}/app.conf.tmp
#nginx配置目录
NGINX_CONF="/etc/nginx/conf.d/qumi/${DOMAIN}.conf"

#证书目录
SSL_ROOT_PATH='/data/www/acmeSSL/'${DOMAIN:0:1}
#创建目录
if [ ! -d ${SSL_ROOT_PATH} ]; then
    mkdir -p ${SSL_ROOT_PATH}
fi

#域名证书相关目录
SSL_DOMAIN_PATH="${SSL_ROOT_PATH}/${DOMAIN}"
#日志文件
LOG=${ROOT_PATH}/`basename $0`.log
#acme.sh 日志
ACME_LOG=${SSL_ROOT_PATH}/${DOMAIN}.log

# 写好nginx配置
__writeNginx() {
    domainCrt=$1
    domainKey=$2
    #读取nginx模版
    nginxConf=`cat ${NGINX_CONF_TEMP}`
    #替换域名
    nginxConf=${nginxConf/\$\{domain\}/$DOMAIN}
    nginxConf=${nginxConf/\$\{domain\}/$DOMAIN}
    #替换证书
    nginxConf=${nginxConf/\$\{domainCrt\}/$domainCrt}
    nginxConf=${nginxConf/\$\{domainKey\}/$domainKey}
    #根据nginx模板生成的写入conf文件
    echo $nginxConf > $NGINX_CONF
}

echo "-------- "`date +"%Y-%m-%d %k:%M:%S"`" [${DOMAIN}] start --------" >> ${LOG}

#如果已经存在
if [ -d ${SSL_DOMAIN_PATH} ];then
    echo 'ssl dir exists' >> ${LOG}
    echo "-------- "`date +"%Y-%m-%d %k:%M:%S"`" [${DOMAIN}] end --------" >> ${LOG}
    echo 'exists'
    exit 0
fi

#dns_istimer 会调用dns服务器的脚本进行修改dns解析
${ACME_SH} --issue --dns dns_istimer -d ${DOMAIN} -d "*.${DOMAIN}" --dnssleep 3 --certhome ${SSL_ROOT_PATH} --log "${ACME_LOG}" >/dev/null 2>&1
acmeExitCode=$?
if [ $acmeExitCode -ne 0 ]
then
    #申请失败的删除域名目录
    if [ -d ${SSL_DOMAIN_PATH} ];then
        rm -rf ${SSL_DOMAIN_PATH}
    fi

    #检查是不是因为数量到了上限而停止
    manyErr=`grep 'Error creating new order :: too many new orders recently' ${ACME_LOG} | wc -l`
    if [ $manyErr -eq 0 ]
    then
        echo 'acme.sh exec error:'${acmeExitCode} >> ${LOG}
    else
        echo 'acme.sh exec error: Error creating new order :: too many new orders recently' >> ${LOG}
        echo '529'
    fi

    echo "-------- "`date +"%Y-%m-%d %k:%M:%S"`" [${DOMAIN}] end --------" >> ${LOG}
    exit 2
fi

#写入nginx配置
__writeNginx "${SSL_DOMAIN_PATH}/fullchain.cer" "${SSL_DOMAIN_PATH}/${DOMAIN}.key"
if [ $? -ne 0 ]
then
    echo 'write nginx conf error' >> ${LOG}
    echo "-------- "`date +"%Y-%m-%d %k:%M:%S"`" [${DOMAIN}] end --------" >> ${LOG}
    exit 3
fi

#nginx重启
/usr/sbin/nginx -s reload  >> ${LOG} 2>&1
reCode=$?
if [ $reCode -ne 0 ]
then
    echo "nginx reload error: ${reCode}" >> ${LOG}
fi

echo 'success' >> ${LOG}
echo "-------- "`date +"%Y-%m-%d %k:%M:%S"`" [${DOMAIN}] end --------" >> ${LOG}
echo 'success'
exit 0
