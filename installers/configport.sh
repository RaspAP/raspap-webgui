#!/bin/bash
#
# Updates lighttpd server.port and restarts the service
# @author billz
# license: GNU General Public License v3.0

server_port=$1
lighttpd_conf=$2
host=$3
restart_service=0

while :; do
    case $1 in
        -r|--restart)
        restart_service=1
        shift
        ;;
        *)
        break
        ;;
    esac
done

if [ "$restart_service" = 1 ]; then
    echo "Restarting lighttpd in 3 seconds..."
    sleep 3
    systemctl restart lighttpd.service
else
    echo "Changing lighttpd server.port to $server_port..."
    sed -i "s/^\(server\.port *= *\)[0-9]*/\1$server_port/g" "$lighttpd_conf"

    echo "RaspAP will now be available at $host:$server_port"
    echo "Restart lighttpd for new setting to take effect"
fi

