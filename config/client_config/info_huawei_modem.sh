#!/bin/bash
# Information about HUAWEI modem - via AT commands
# ------------------------------------------------
# get info about the device and signal
# parameter: $1 - see opts list below
#            $2 - tty device name for the communicaton (optional)
# returns the value of the parameter, or "none" if not found or empty
#
# requires: socat
#
# zbchristian 2020

opts=("manufacturer" "device"     "imei"    "imsi"    "telnumber"            "mode"         "signal"         "operator")

# at command to extract information
atcmds=("AT+CGMI"    "AT+CGMM"    "AT+CGSN" "AT+CIMI" "AT+CNUM"              "AT+COPS?"     "AT+CSQ"         "AT+COPS?")
# regexp pattern to extract wanted information from result string
pats=(  " "          " "          " "       " "       ".*\,\"([0-9\+]*)\".*" '.*\,([0-9])$' ".*: ([0-9]*).*" '.*\,\"([^ ]*)\".*$')

# tty device for communication - usually 3 tty devices are created and the 3rd ttyUSB2 is available, even, when the device is connected
dev="/dev/ttyUSB2"

atsilent="AT^CURC=0"

if [ ! -z $2 ]; then dev=$2; fi

idx=-1
opt=${opts[0]}
if [ ! -z $1 ]; then opt=$1; fi

for i in "${!opts[@]}"; do
  if [[ ${opts[$i]} == $opt ]]; then idx=$i; fi
done
if [[ $idx == -1 ]];then echo "none"; exit; fi 

atcmd=${atcmds[$idx]}
pat=${pats[$idx]}


result=`(echo $atsilent; echo $atcmd) | sudo /usr/bin/socat - $dev`
# escape the AT command to be used in the regexp
atesc=${atcmd//[\+]/\\+}
atesc=${atesc//[\?]/\\?}
result=`echo $result | sed -rn 's/.*'"$atesc"'\s([^ ]+|[^ ]+ [^ ]+)\sOK.*$/\1/pg'`
if [[ $pat != " " ]]; then
  result=`echo $result | sed -rn 's/'"$pat"'/\1/pg'`
fi

if [ -z "$result" ]; then result="none"; fi

echo $result

