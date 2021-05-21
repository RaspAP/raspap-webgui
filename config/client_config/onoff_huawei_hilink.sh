#!/bin/bash
# connect/disconnect Huawei mobile data stick in Hilink mode (e.g. E3372h)
# ========================================================================
#
# options: -u, --user       - user name (default "admin")
#          -P, --password   - password
#          -h, --host       - host ip address (default 192.168.8.1)
#          -p, --pin        - PIN of SIM card
#          -c, --connect    - connect 0/1 to set datamode off/on
#
# required software: curl, base64, sha256sum
#
# zbchristian 2021

# include the hilink API (defaults: user=admin, host=192.168.8.1)
source /usr/local/sbin/huawei_hilink_api.sh

datamode=""
while [ -n "$1" ]; do
    case "$1" in
        -u|--user)      user="$2"; shift ;;
        -P|--password)  pw="$2"; shift ;;
        -p|--pin)       if [[ $2 =~ ^[0-9]{4,8} ]]; then pin="$2"; fi; shift ;;
        -h|--host)      host="$2"; shift ;;
        -c|--connect)   if [ "$2" = "1" ]; then datamode=1; else datamode=0; fi; shift ;;
    esac
    shift
done

echo  "Hilink: switch device at $host to mode $datamode" | systemd-cat

status="usage: -c 1/0 to disconnect/disconnect"
if [ -z "$datamode" ] || [ ! _initHilinkAPI ]; then echo "Hilink: failed - return status: $status"; exit; fi

if ! _switchMobileData "$datamode"; then echo -n "Hilink: could not switch the data mode on/off . Error: ";_getErrorText; fi

if ! _closeHilinkAPI; then echo -n "Hilink: failed - return status: $status . Error: ";_getErrorText; fi


