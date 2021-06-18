#!/bin/bash
# Information about HUAWEI hilink
# -------------------------------
# get info about the device and signal
# parameter: $1 - "connected", "device", "ipaddress", "mode", "signal"  (see case statement below)
#            -u,--user - username
#            -P,--password - password
#            -p,--pin - SIM pin
#            -h,--host - host ip address for API calls (optional)
# returns the value of the parameter, or "none" if not found or empty
#
# All device informations are buffered for 5 secs to speed up subsequent calls
#
# zbchristian 2021

function _setAPIParams() {
    if [ ! -z "$hostip" ]; then hilink_host="$hostip"; fi
    if [ ! -z "$username" ]; then hilink_user="$username"; fi
    if [ ! -z "$password" ]; then hilink_password="$password"; fi
    if [ ! -z "$simpin" ]; then hilink_pin="$simpin"; fi
}

if [ -z "$1" ]; then echo "none"; exit; fi
property="${1,,}"
shift
hostip="192.168.8.1"
while [ -n "$1" ]; do
    case "$1" in
        -u|--user)      username="$2"; shift ;;
        -P|--password)  password="$2"; shift ;;
        -p|--pin)       simpin="$2"; shift ;;
        -h|--host)      hostip="$2"; shift ;;
    esac
    shift
done

status="no valid option given"
result="none"
hostip="192.168.8.1"
if [ "$opt" = "connected" ]; then
    source /usr/local/sbin/huawei_hilink_api.sh
    _setAPIParams
    if ! _initHilinkAPI; then echo "none"; exit; fi
    result=$(_getMobileDataStatus)
    _closeHilinkAPI
else
    info_file="/tmp/huawei_infos_${hostip}_$(id -u).dat"
    if [ -f "$info_file" ]; then
        age=$(( $(date +%s) - $(stat $info_file -c %Y) )) 
        if [[ $age -gt 10 ]]; then rm -f $info_file; fi
    fi

    if [ -f "$info_file" ]; then
        infos=$(cat $info_file)
    else
        source /usr/local/sbin/huawei_hilink_api.sh
        _setAPIParams
        if ! _initHilinkAPI; then echo "none"; exit; fi
        infos=$(_getAllInformations)
        _closeHilinkAPI
        if [ ! -z "$infos" ]; then echo -n "$infos" > $info_file; fi
    fi

    case "$property" in
        device|devicename)
          key="devicename"
          ;;
        ipaddress|wanipaddress)
          key="wanipaddress"
          ;;
        mode)
          key="workmode"
          ;;
        telnumber)
          key="msisdn"
          ;;
        imei|imsi|rssi|rsrq|rsrp|sinr|ecio)
          key="$property"
          ;;
        signal)
          key="rsrq"
        ;;
        operator|fullname)
          key="fullname"
        ;;
        *)
          key="device"
        ;;
      esac
      if [ -z "$key" ]; then result="none"; fi
      result=$(echo "$infos" | sed -rn 's/'$key'=\"([^ \s]*)\"/\1/ip')
      if [ -z "$result" ]; then result="none"; fi
fi
echo -n "$result"

