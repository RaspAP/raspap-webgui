#!/bin/bash
# connect/disconnect Huawei mobile data stick in Hilink mode (e.g. E3372h)
# ========================================================================
# - send xml formatted string via HTTP API to stick
# - Requires session and verification token, which is obtained by an API call
#
# params: $1 - mode 0 (down) - disconnect, 1 (up) - connect
#         $2 - IP address of the host (default 192.168.8.1)
#
# required software: curl, base64
#
# TODO: implement login into API - currently the login has to be disabled!
#
# zbchristian 2020

mode=0
if [ ! -z $1 ]; then
  if [[ $1 == "1" ]] || [[ $1 == "up" ]]; then mode=1; fi
fi

host="192.168.8.1"
if [[ ! -z $2 ]] && [[ $2 =~ ^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$ ]]; then host="$2"; fi

echo  "Hilink: switch device at $host to mode $mode" | systemd-cat

# check if device is reachable
avail=`timeout 0.5 ping -c 1 $host | sed -rn 's/.*time=.*/1/p'`
if [[ -z $avail ]]; then 
  echo "Hilink: no link to host" | systemd-cat
  exit 
fi

# obtain session and verification token
SesTok=`sudo curl -s http://$host/api/webserver/SesTokInfo -m 5 2> /dev/null`
if [ -z "$SesTok" ]; then exit; fi

token=`echo $SesTok | sed  -r 's/.*<TokInfo>(.*)<\/TokInfo>.*/\1/'`
sesinfo=`echo $SesTok | sed  -r 's/.*<SesInfo>(.*)<\/SesInfo>.*/\1/'`

# login to web api - NOT TESTED - NOT USED
#if [[ ! -z $3 ]]; then
# pw64=`echo $3 | base64`
# curl -s http://$host/api/user/login" -m 5\
#  -H "Content-Type: application/xml"  \
#  -H "Cookie: $sesinfo" \
#  -H "__RequestVerificationToken: $token" \
#  -d '<?xml version="1.0" encoding="UTF-8"?><request>\
#<Username>admin</Username>\
#<Password>'"$pw64"'</Password>\
#</request>'
#fi

curl -s http://$host/api/dialup/mobile-dataswitch -m 10 \
  -H "Content-Type: application/xml"  \
  -H "Cookie: $sesinfo" \
  -H "__RequestVerificationToken: $token" \
  -d '<?xml version: "1.0" encoding="UTF-8"?><request><dataswitch>'"$mode"'</dataswitch></request>' > /dev/null
