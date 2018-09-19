#!/bin/bash

systemctl start php-fpm
/usr/sbin/nginx
systemctl start named