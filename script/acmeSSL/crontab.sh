#!/usr/bin/env bash

ROOT_PATH=$(cd "$(dirname "$0")"; pwd)
#日志文件
LOG=${ROOT_PATH}/`basename $0`.log

COMMAND="php ${ROOT_PATH}/start.php >> ${LOG} 2>&1"

num=`ps -ef | grep "${COMMAND}" | grep -v grep | wc -l`
if [ $num -eq 0 ];
then
    ${COMMAND} &
fi