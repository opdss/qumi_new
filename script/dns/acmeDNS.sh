#!/usr/bin/env bash

#
# 本脚本放在每一台dns服务器上执行,用于操作dns记录
# 取代原来的bindDNS.sh 脚本
#

#ROOT_PATH=$(cd "$(dirname "$0")"; pwd)
ZONE_PATH='/var/named/'
ZONES='/var/named/qumi/qumi.zones'
FILE_ZONE='qumi/file.zone'

CMD='__'$1
PARAMS=${@:2}

#重启named服务
__restartDNS() {
    systemctl restart named
    if [ $? -gt 0 ];
    then
        echo 'named 服务重启出错了！';
        exit 127
    fi
}

#生成一个acme txt记录的校验文件
__addAcmeTXT() {
    txt="_acme-challenge    IN TXT \"${1}\""
    echo $txt >> ${ZONE_PATH}${FILE_ZONE}
    __restartDNS
}

#删除acme txt记录的校验文件
__rmAcmeTXT() {
    sed -i '/_acme-challenge*/d' ${ZONE_PATH}${FILE_ZONE}
    __restartDNS
}

#添加named解析域名
__addDNS() {
    COUNT=0
    for x in $*
    do
        RECORD="zone \"${x}\" IN { type master; file \"${FILE_ZONE}\"; allow-update {none;}; };"
        num=`grep "$RECORD" $ZONES | wc -l`
        #过滤重复记录
        if [ $num -eq 0 ];
        then
            echo $RECORD >> $ZONES
            COUNT=$[$COUNT+1]
        fi
    done

    #有新的添加，则重启named服务
    if [ $COUNT -gt 0 ];
    then
        __restartDNS
    fi
    return 0
}

#执行命令
$CMD $PARAMS