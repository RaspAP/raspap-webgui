#!/bin/bash
#
# Updates lighttpd config settings and restarts the service
# @author billz
# license: GNU General Public License v3.0

# Exit on error
set -o errexit
# Exit on error inside functions
set -o errtrace
# Turn on traces, disabled by default
#set -o xtrace

server_port=$1
server_bind=$2
lighttpd_conf=$3
host=$4
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
fi
if [ -n "$server_port" ]; then
    echo "Changing lighttpd server.port to $server_port ..."
    sed -i "s/^\(server\.port *= *\)[0-9]*/\1$server_port/g" "$lighttpd_conf"
    echo "RaspAP will now be available at port $server_port"
    conf_change=1
fi
if [ -n "$server_bind" ]; then
    echo "Changing lighttpd server.bind to $server_bind ..."
    grep -q 'server.bind' "$lighttpd_conf" && \
        sed -i "s/^\(server\.bind.*= \)\".*\"*/\1\"$server_bind\"/g" "$lighttpd_conf" || \
        printf "server.bind \t\t\t\t = \"$server_bind\"\n" >> "$lighttpd_conf"
    echo "RaspAP will now be available at address $server_bind"
    conf_change=1
fi
if [ "$conf_change" == 1 ]; then
    echo "Restart lighttpd for new settings to take effect"
fi

