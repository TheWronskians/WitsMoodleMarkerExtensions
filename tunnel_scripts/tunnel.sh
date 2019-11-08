#!/bin/bash

#SSH Tunnel into "target_host" via the proxy "proxy_host"

#Usage: ./tunnel.sh [dev number: 1, 2] s[student number]

#addr: ssh://sdp@localhost:port/var/www/html/etc...
#where port = 3333/3334 for dev1/dev2

target_host_first="moodle-dev"
target_host_second=".ms.wits.ac.za"
proxy_host="lamp.ms.wits.ac.za"
target_dev=$1
username_proxy=$2
local_port=3333
target_port=22

if [ $target_dev -eq 2 ]
then
	echo $target_dev
	local_port=3334
fi

echo $local_port
echo "Connecting to ${proxy_host} on local port: ${local_port}"
echo "..."
ssh -L ${local_port}:${target_host_first}${target_dev}${target_host_second}:${target_port} ${username_proxy}@${proxy_host}
echo "Connection with ${proxy_host} closed."
