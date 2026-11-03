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

# Usage: configport.sh <port|""> <bind address|""> /etc/lighttpd/lighttpd.conf [host]
#        configport.sh --restart
# www-data runs this script with sudo, so it checks each argument. The host
# argument is not used.
readonly lighttpd_config="/etc/lighttpd/lighttpd.conf"

server_port=${1-}
server_bind=${2-}
lighttpd_conf=${3-}
restart_service=0
conf_change=0

if [ "$server_port" = "-r" ] || [ "$server_port" = "--restart" ]; then
    if [ "$#" -ne 1 ]; then
        echo "Usage: $0 --restart" >&2
        exit 2
    fi
    restart_service=1
    server_port=""
    server_bind=""
    lighttpd_conf="$lighttpd_config"
fi

if [ "$lighttpd_conf" != "$lighttpd_config" ]; then
    echo "Invalid lighttpd configuration file" >&2
    exit 2
fi
if [ -n "$server_port" ]; then
    if ! [[ "$server_port" =~ ^[0-9]{1,5}$ ]] || [ "$server_port" -lt 1 ] || [ "$server_port" -gt 65535 ]; then
        echo "Invalid port number" >&2
        exit 2
    fi
fi
if [ -n "$server_bind" ]; then
    if ! [[ "$server_bind" =~ ^([0-9]{1,3}\.){3}[0-9]{1,3}$ ]] && ! [[ "$server_bind" =~ ^[0-9a-fA-F:.]{2,45}$ && "$server_bind" == *:* ]]; then
        echo "Invalid bind address" >&2
        exit 2
    fi
fi

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
        printf 'server.bind \t\t\t\t = "%s"\n' "$server_bind" >> "$lighttpd_conf"
    echo "RaspAP will now be available at address $server_bind"
    conf_change=1
fi

if [ "$conf_change" == 1 ]; then
    echo "Restart lighttpd for new settings to take effect"
fi
