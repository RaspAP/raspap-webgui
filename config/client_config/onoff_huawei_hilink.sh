#!/bin/bash
# connect/disconnect Huawei mobile data stick in Hilink mode (e.g. E3372h)
# ========================================================================
#
# options: -u, --user       - user name (default "admin")
#          -P, --password   - password
#          -h, --host       - host ip address (default 192.168.8.1)
#          -d, --devname    - device name (IP is extracted using default route)
#          -p, --pin        - PIN of SIM card
#          -c, --connect    - connect 0/1 to set datamode off/on
#
# required software: curl, base64, sha256sum
#
# zbchristian 2021

# include the hilink API (defaults: hilink_user=admin, hilink_host=192.168.8.1)
source /usr/local/sbin/huawei_hilink_api.sh

# include the raspap helper functions
source /usr/local/sbin/raspap_helpers.sh

datamode=""
devname=""
err=""
while [ -n "$1" ]; do
    case "$1" in
        -u|--user)      hilink_user="$2"; shift ;;
        -P|--password)  hilink_password="$2"; shift ;;
        -p|--pin)       if [[ $2 =~ ^[0-9]{4,8} ]]; then hilink_pin="$2"; fi; shift ;;
        -h|--host)      hilink_host="$2"; shift ;;
        -d|--devname)   devname="$2"; shift ;;
        -c|--connect)   if [ "$2" = "1" ]; then datamode=1; else datamode=0; fi; shift ;;
    esac
    shift
done

if [ ! -z "$devname" ]; then # get host IP for given device name
    gw=$(ip route list |  sed -rn "s/default via (([0-9]{1,3}\.){3}[0-9]{1,3}).*dev $devname.*/\1/p")
    if [ -z "$gw" ]; then exit; fi  # device name not found in routing list -> abort 
    hilink_host="$gw"
elif [ ! -z "$hilink_host" ]; then
    ip=${hilink_host%.*}
    devname=$(ip route list |  sed -rn "s/${ip}.0\/[0-9]{1,2} dev ([a-zA-Z0-9]*) proto dhcp.*/\1/p")
fi

if [ -z "$hilink_password" ] || [ -z "$hilink_pin" ]; then 
    _getAuthRouter
    if [ ! -z "$raspap_user" ]; then hilink_user="$raspap_user"; fi
    if [ ! -z "$raspap_password" ]; then hilink_password="$raspap_password"; fi
    if [ ! -z "$raspap_pin" ]; then hilink_pin="$raspap_pin"; fi
fi

echo  "Hilink: switch device at $hilink_host to mode $datamode" | systemd-cat

status="usage: -c 0/1 to disconnect/connect"
if [ -z "$datamode" ] || [ ! _initHilinkAPI ]; then echo "Hilink: failed - return status: $status"; exit; fi

if ! _switchMobileData "$datamode"; then echo -n "Hilink: could not switch the data mode on/off . Error: ";_getErrorText; err=1; fi

if ! _closeHilinkAPI; then echo -n "Hilink: failed - return status: $status . Error: ";_getErrorText; err=1; fi

if [ -z "$err" ] && [ ! -z "$hilink_host" ] && [ ! -z "$devname" ]; then
    dhcpIP=$(ip addr show "$devname" | sed -rn "s/inet (([0-9]{1,3}\.){3}[0-9]{1,3})\/.*/\1/p")
    ip=${hilink_host%.*}
    metric=$(ip route list | sed -rn "s/${ip}.0\/[0-9]{1,2} dev $devname .*metric ([0-9]*).*/\1/p")
    if [ -z $metric ]; then metric="207"; fi
    if [ ! -z "$dhcpIP" ]; then
       if [ "$datamode" -eq 1 ]; then
           sudo ip route add default via "$hilink_host" dev "$devname" proto dhcp src "$dhcpIP" metric "$metric"
       else
           sudo ip route delete default via "$hilink_host" dev "$devname"
       fi
    fi
fi
 
