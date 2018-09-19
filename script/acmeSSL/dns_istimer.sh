#!/usr/bin/env sh

#给我自己的dns服务器istimer.com 绑定acme的校验
#已经建立软连接到 /root/.acme.sh/dnsapi/
#acme.sh 脚本进行dns检测的时候会调用


#dns服务器上的脚本，用来向named添加记录和重启
SCRIPT='/root/qumi/acmeDNS.sh'

#Usage: dns_ali_add   _acme-challenge.www.domain.com   "XKrxpRBosdIKFzxW_CT3KLZNf6q0HG9i01zxXp5CPBs"
dns_istimer_add() {
  fulldomain=$1
  txtvalue=$2

  #_debug "Add istimer record => ${fulldomain}:${txtvalue}"
  res=`__execRemote ${SCRIPT} addAcmeTXT $txtvalue`
  if [ $? -ne 0 ]; then
    _err $res
    return 1
  fi
}

dns_istimer_rm() {
  fulldomain=$1
  txtvalue=$2

  #_debug "remove istimer record=> ${fulldomain}:${txtvalue}"
  res=`__execRemote ${SCRIPT} rmAcmeTXT`
  if [ $? -ne 0 ]; then
    _err $res
    return 1
  fi
}

__execRemote() {
    HOST='119.29.156.18'
    PORT=22
    ssh -T -p ${PORT} -q -o ConnectTimeout=30 -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no ${HOST} "${*}"


    HOST='140.143.225.238'
    PORT=22
    ssh -T -p ${PORT} -q -o ConnectTimeout=30 -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no ${HOST} "${*}"

    $*
}