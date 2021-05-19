#!/bin/bash
# Information about HUAWEI hilink (router) modem
# ----------------------------------------------
# get info about the device and signal
# parameter: $1 - see opts list below
#            $2 - host ip address for API calls (optional)
# returns the value of the parameter, or "none" if not found or empty
#
# All device informations are buffered for 5 secs to speed up subsequent calls
#
# zbchristian 2020

if [ -z "$1" ]; then echo "none"; exit; fi

host="192.168.8.1"

status="no option given"
if [ ! -z "$2" ]; then host="$2"; fi

opt="${1,,}"
result="none"
if [ "$opt" = "connected" ]; then
  source /usr/local/sbin/huawei_hilink_api.sh
  if ! _initHilinkAPI; then echo "none"; exit; fi
  result=$(_getMobileDataStatus)
  _closeHilinkAPI
else
  info_file="/tmp/huawei_infos_$host.dat"
  if [ -f "$info_file" ]; then
    age=$(( $(date +%s) - $(stat $info_file -c %Y) )) 
    if [[ $age -gt 5 ]]; then rm -f $info_file; fi
  fi

  if [ -f "$info_file" ]; then
     infos=$(cat $info_file)
  else
     source /usr/local/sbin/huawei_hilink_api.sh
     if ! _initHilinkAPI; then echo "none"; exit; fi
     infos=$(_getAllInformations)
    _closeHilinkAPI
     if [ ! -z "$infos" ]; then echo "$infos" > /tmp/huawei_infos_$host.dat; fi
  fi

  case "$opt" in
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
      key="$opt"
      ;;
    signal)
      key="rsrq"
    ;;
    operator|fullname)
      key="fullname"
    ;;
    *)
      key=""
    ;;
  esac
  if [ -z "$key" ]; then result="none"; fi
  result=$(echo "$infos" | sed -rn 's/'$key'=\"([^ \s]*)\"/\1/ip')
  if [ -z "$result" ]; then result="none"; fi
fi
echo -n "$result"

