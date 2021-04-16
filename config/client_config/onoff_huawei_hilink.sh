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
# required software: curl, base64
#
# TODO: implement login into API - currently the login has to be disabled!
#
# zbchristian 2020

# obtain session and verification token
function _SessToken() {
  SesTok=`sudo curl -s http://$host/api/webserver/SesTokInfo -m 5 2> /dev/null`
  if [ -z "$SesTok" ]; then exit; fi

  token=`echo $SesTok | sed  -r 's/.*<TokInfo>(.*)<\/TokInfo>.*/\1/'`
  sesinfo=`echo $SesTok | sed  -r 's/.*<SesInfo>(.*)<\/SesInfo>.*/\1/'`
}

function _login() {
# ----------------------- THIS DOES NOT WORK ------------------------------------------
# login to web api
  _SessToken

  if [[ ! -z $user ]] && [[ ! -z $pw ]]; then
    # password encoding
    # type 3 : base64(pw) encoded
    # type 4 : base64(sha256sum(user + base64(sha256sum(pw)) + token))
    pwtype3=$(echo $pw | base64 --wrap=0)
    hashedpw=$(echo -n "$pw" | sha256sum -b | cut -d " " -f1 | base64 --wrap=0)
    pwtype4=$(echo -n "$user$hashedpw$token" | sha256sum -b | cut -d " " -f1 | base64 --wrap=0)
    apiurl="api/user/login"
    xmldata="<?xml version='1.0' encoding='UTF-8'?><request><Username>$user</Username><Password>$pwtype4</Password><password_type>4</password_type></request>"
#    xmldata="<?xml version='1.0' encoding='UTF-8'?><request><Username>$user</Username><Password>$pwtype3</Password><password_type>3</password_type></request>"
    xtraopts="--dump-header /tmp/hilink_login_hdr.txt"
    _sendRequest
    # get updated session cookie
    sesinfo=$(grep "SessionID=" /tmp/hilink_login_hdr.txt | cut -d ':' -f2 | cut -d ';' -f1)
    token=$(grep "__RequestVerificationTokenone" /tmp/hilink_login_hdr.txt | cut -d ':' -f2)
echo "Login Cookie $sesinfo"
echo "Login Token $token"
  fi
# ------------------------------ DO NOT USE THE LOGIN CODE ----------------------------------
}

function _switchMobileData() {
# switch mobile data on/off
  if [[ $datamode -ge 0 ]]; then
    xmldata="<?xml version: '1.0' encoding='UTF-8'?><request><dataswitch>$datamode</dataswitch></request>"
    apiurl="api/dialup/mobile-dataswitch"
    _sendRequest
  fi
}

function _enableSIM() {
#SimState:
#255 - no SIM,
#256 - error CPIN,
#257 - ready,
#258 - PIN disabled,
#259 - check PIN,
#260 - PIN required,
#261 - PUK required
   status=`curl -s http://$host/api/pin/status -m 10`
   state=`echo $status | sed  -rn 's/.*<simstate>(.*)<\/simstate>.*/\1/pi'`
   if [[ $state -eq 257  ]]; then echo "Hilink: SIM ready"|systemd-cat; return; fi
   if [[ $state -eq 260  ]]; then echo "Hilink: Set PIN"|systemd-cat; _setPIN; fi
}

function _setPIN() {
  if [[ ! -z $pin ]]; then
    xmldata="<?xml version: '1.0' encoding='UTF-8'?><request><OperateType>0</OperateType><CurrentPin>$pin</CurrentPin><NewPin></NewPin><PukCode></PukCode></request>"
    apiurl="api/pin/operate"
    _sendRequest
  fi
}

function _sendRequest() {
  result=""
  if [[ -z $xmldata ]]; then return; fi 
  result=`curl -s http://$host/$apiurl -m 10 \
           -H "Content-Type: application/xml"  \
           -H "Cookie: $sesinfo" \
           -H "__RequestVerificationToken: $token" \
           -d "$xmldata" $xtraopts 2> /dev/null`
  xtraopts=""
}

# handle options

host="192.168.8.1"
pin=""
user=""
pw=""
datamode=-1
connect=-1
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

# check if device is reachable
avail=`timeout 0.5 ping -c 1 $host | sed -rn 's/.*time=.*/1/p'`
if [[ -z $avail ]]; then 
  echo "Hilink: no link to host" | systemd-cat
  exit 
fi

token=""
Sesinfo=""
xmldata=""
xtraopts=""
result=""

_SessToken
_enableSIM
_switchMobileData	# check and perform enable/disable mobile data connection


