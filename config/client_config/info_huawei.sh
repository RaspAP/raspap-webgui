#!/bin/bash
# get info about device and signal of Huawei mobile USB devices
# parm:
# $1 : requested information (manufacturer, device, imei, imsi, telnumber, ipaddress, mode, signal, operator)
# $2 : (optional) type - hilink or modem (default: hilink)
# $3 : (optional) for hilink: ip address of the device (default: 192.168.8.1)
#                 for modem: tty interface for communication (default: /dev/ttypUSB2)
# $4 : more options can be added for Hilink devices ('-u user -P password -p pin'). These are passed to the corresponding script 
#
# requires: bc
# calls the scripts info_huawei_hilink.sh and info_huawei_modem.sh (same path as this script)
#
# zbchristian 2020
#
path=$(dirname "$0")
opt="device"
if [ ! -z "$1" ]; then opt=${1,,}; fi
type="hilink"
if [ ! -z "$2" ]; then type=${2,,}; fi

parms=""
if [ "$type" = "hilink" ]; then
  connect="-h 192.168.8.1"
  if [ ! -z "$3" ]; then connect="-h $3"; fi
  if [ ! -z "$4" ]; then parms="$4"; fi
  script="$path/info_huawei_hilink.sh"
else
  connect="/dev/ttyUSB2"
  if [ ! -z "$3" ]; then connect=$3; fi
  script="$path/info_huawei_modem.sh"
fi
res=$($script $opt $connect $parms)

# some results require special treatment
case $opt in

#  manufacturer)
#    if [ "$res" = "none" ]; then res="Huawei"; fi
#    ;;

#  device)
#    if [ ! "$res" = "none" ]; then res="Huawei $res"; 
#    else res="Huawei"; fi
#    ;;

  mode)
    if [ ! "$res" = "none" ]; then
      if [ "$type" = "hilink" ]; then
        if [ "$res" = "LTE" ]; then res="4G"
        elif [ "$res" = "WCDMA" ]; then  res="3G";
        else res="2G"; fi
      else
        if [ $res -eq 7 ]; then res="4G"
        elif [ $res -lt 7 ] && [ $res -gt 2 ] ; then  res="3G";
        else res="2G"; fi
      fi
    fi
    ;;

  signal)
  # return signal strength/quality in %
    if [ "$type" = "hilink" ]; then
     # signal request tries to get RSRQ value
     # try to get RSRQ (4G), EC/IO (3G) or RSSI (2G) value
     if [ "$res" = "none" ]; then res=$($script "ecio"); fi
     if [ ! "$res" = "none" ]; then 
       # for rsrq and ecio assume: -3dB (100%) downto -20dB (0%)
       qual=${res//dB/}
       if [[ ! "$qual" =~ [-0-9\.]* ]]; then qual=-100; fi
       qual=$(bc <<< "scale=0;res=$qual-0.5;res/1") # just round to next integer 
       if [ $qual -le -20 ]; then qual=0; 
       elif [ $qual -ge -3 ]; then qual=100; 
       else  qual=$(bc <<< "scale=0;res=100.0/17.0*$qual+2000.0/17.0;res/1"); fi
     else
      # try rssi: >-70dBm (100%) downto -100dBm (0%)
      res=$($script "rssi");
      if [ ! "$res" = "none" ]; then
        if [[ ! $res =~ [-0-9\.]* ]]; then res="-120 dBm"; fi
        qual=${res//dBm/}
        qual=$(bc <<< "scale=0;res=$qual+0.5;res/1") # just round to next integer 
        if [ $qual -le -110 ]; then qual=0;
        elif [ $qual -ge -70 ]; then qual=100; 
        else  qual=$(bc <<< "scale=0;res=2.5*$qual+275;res/1"); fi
      fi
     fi
    else
     # modem returns RSSI as number 0-31 - 0 = -113dB (0%), 1 = -111dB, 31 = >=51dB (100%)
     qual=$(bc <<< "scale=0;res=$res*3.5+0.5;res/1")
     if [ $qual -gt 100 ]; then res=100; fi
    fi
    if [ ! "$res" = "none" ]; then res="$res (${qual}%)"; fi
    ;;

  operator)
    # check if operator/network is just a 5 digit number -> extract network name from table
    if [[ $res =~ ^[0-9]{5}$ ]]; then
      mcc=${res:0:3}
      mnc=${res:3:2}
      op=$(cat $path/mcc-mnc-table.csv | sed -rn 's/^'$mcc'\,[0-9]*\,'$mnc'\,(.*\,){4}(.*)$/\2/p')
      if [ ! -z "$op" ]; then res="$op ($res)"; fi
    fi
   ;;

  *)
    ;;
esac

echo $res

