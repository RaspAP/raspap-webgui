#!/bin/bash
# Updates lighttpd server.port and restarts the service in a predictable manner

PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

server_port=$1
lighttpd_conf=$2
host=$3

echo "Changing lighttpd server.port to $server_port..."
sed -i "s/^\(server\.port *= *\)[0-9]*/\1$server_port/g" "$lighttpd_conf"

echo "RaspAP will now be available at $host:$server_port"
echo "Restarting lighttpd in 5 seconds..."
sleep 5
systemctl restart lighttpd.service
