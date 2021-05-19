#!/bin/bash
# connect/disconnect Huawei mobile data stick in Hilink mode (e.g. E3372h)
# ========================================================================
# - send xml formatted string via HTTP API to stick
# - Requires session and verification token, which is obtained by an API call
#
# options: -l "user":"password" - login data - DOES NOT WORK YET 
#          -h 192.168.8.1       - host ip address
#          -p 1234              - PIN of SIM card
#          -c 0/1               - connect - set datamode off/on
# required software: curl, base64, sha256sum
#
# zbchristian 2021

# include the hilink API
source /usr/local/sbin/huawei_hilink_api.sh

# handle options

host="192.168.8.1"
pin=""
user=""
pw=""
datamode=""

while getopts ":c:h:l:m:p:" opt; do
  case $opt in
    h) if [[ $OPTARG =~ ^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$ ]]; then host="$OPTARG"; fi
    ;;
    p) if [[ $OPTARG =~ ^[0-9]{4,8} ]]; then pin="$OPTARG"; fi
    ;;
    l) if [[ $OPTARG =~ ^[0-9a-zA-Z]*:.*$ ]]; then
         user=$(echo "$OPTARG" | cut -d':' -f1);
         pw=$(echo "$OPTARG" | cut -d':' -f2);
       fi
    ;;
    c) if [[ $OPTARG == "1" ]]; then datamode=1; else datamode=0; fi
    ;;
  esac
done

echo  "Hilink: switch device at $host to mode $datamode" | systemd-cat

status="usage: -c 1/0 to disconnect/disconnect"
if [ -z "$datamode" ] || [ ! _initHilinkAPI ]; then echo "Hilink: failed - return status: $status"; exit; fi

if ! _switchMobileData "$datamode"; then echo "Hilink: could not switch the data mode on/off . Error: ";_getErrorText; fi

if ! _closeHilinkAPI; then echo -n "Hilink: failed - return status: $status . Error: ";_getErrorText; fi


