#!/bin/bash
# Infosramtion about HUAWEI hilink (router) modem
# -----------------------------------------------
# get info about the device and signal
# parameter: $1 - see opts list below
#            $2 - host ip address for API calls (optional)
# returns the value of the parameter, or "none" if not found or empty
#
# zbchristian 2020

opts=("device"     "imei" "imsi" "telnumber" "ipaddress"    "mode"     "signal" "rssi" "rsrq" "rsrp" "sinr" "ecio" "operator")

# xml tags to extract information from
tags=("devicename" "imei" "imsi" "msisdn"    "wanipaddress" "workmode" "rsrq"   "rssi" "rsrq" "rsrp" "sinr" "ecio" "fullname")
iurl=( 0            0      0      0          0              0          1        1      1      1      1      1      2)
# api urls
urls=("api/device/information" "api/device/signal" "api/net/current-plmn") 

host="192.168.8.1"
if [ ! -z $2 ]; then host=$2; fi

avail=`timeout 0.5 ping -c 1 $host | sed -rn 's/.*time=.*/1/p'`
if [[ -z $avail ]]; then echo "none"; exit; fi

idx=-1
opt=${opts[0]}
if [ ! -z $1 ]; then opt=$1; fi

for i in "${!opts[@]}"; do
  if [[ ${opts[$i]} == $opt ]]; then idx=$i; fi
done
if [[ $idx == -1 ]];then echo "none"; exit; fi 

par=${tags[$idx]}
iu=${iurl[$idx]}

url="http://$host/${urls[$iu]}"
# echo "Found option $opt at index $idx - tag $par url $url "


info=""
if [ ! -z $url ]; then info=`curl -s $url`; fi

result=`echo $info | sed  -rn 's/.*<'"$par"'>(.*)<\/'"$par"'>.*/\1/pi'`

if [ -z "$result" ]; then result="none"; fi

echo $result

