#!/bin/bash
#
# Determine the currently connected clients and properties
# ========================================================
# parameter
#   $1 - simple - skip the details in the json output
#
# result: json formatted list of client devices. Information depends on device type
#
# requires: udevadm, iw, ip, iwconfig, ifconfig, sed, grep
#
# defined device types
# 0 : internal ethernet interface (eth0)
# 1 : external ethernet 
# 2 : smartphone (USB tethering)- assumptions: Apple/IPhone in name, rndis_host driver for Android
# 3 : wlan device (not in Mode=Master)
# 4 : mobile data router (from list of known devices) - Huawei Hilink device
# 5 : mobile data modem (from list of known devices) - access via derial port and AT commands

# known special devices (mobile data modem, 4G stick ...)
#   Huawei   E1550        E1750      E3372h
vidpids=( "12d1/14ac" "12d1/1406" "12d1/14db" )

simple=0
if [[ ! -z $1 ]] && [[ "$1" == "simple" ]]; then simple=1; fi

# get all current interfaces (except lo)
rawdevs=`ifconfig -a | grep -oP '^(?!lo)(\w*)'`
devs=()  # device names
vends=() # vendor names
mods=()  # model names
typs=()  # device types

shopt -s nocasematch

if [[ ! -z $rawdevs ]]; then
# extract device informations
  for dev in $rawdevs; do
    if [[ "$dev" =~ ^wlan[0-9]$ ]];   then
      itsAP=`iwconfig $dev 2> /dev/null | sed -rn 's/.*(mode:master).*/1/ip'`
      if [[ ! -z $itsAP ]]; then continue; fi # skip the wlan AP
    fi
    udevinfo=`udevadm info /sys/class/net/$dev 2> /dev/null`
    mod=`echo -e $(echo "$udevinfo" | sed -rn 's/.*ID_MODEL_ENC=(.*)$/\1/gp')`
    if [[ -z $mod ]] || [[ "$mod" =~ ^[0-9a-f]{4}$ ]] ; then
      mod=`echo "$udevinfo" | sed -rn 's/.*ID_MODEL_FROM_DATABASE=(.*)$/\1/p'`
    fi
    vend=`echo -e $(echo "$udevinfo" | sed -rn 's/.*ID_VENDOR_ENC=(.*)$/\1/p')`
    if [[ -z $mod ]] || [[ "$vend" =~ ^[0-9a-f]{4}$ ]] ; then
      vend=`echo "$udevinfo" | sed -rn 's/.*ID_VENDOR_FROM_DATABASE=(.*)$/\1/p'`
    fi
    if [[ -z $mod ]]; then mod=$vend; fi
    if [[ -z $vend ]]; then vend=$mod; fi
    drv=`echo "$udevinfo" | sed -rn 's/.*ID_NET_DRIVER=(\w*).*$/\1/p'`
    vid=`echo "$udevinfo" | sed -rn 's/.*ID_VENDOR_ID=(\w*).*$/\1/p'`
    pid=`echo "$udevinfo" | sed -rn 's/.*ID_MODEL_ID=(\w*).*$/\1/p'`
    ty=-1
    if [[ "$dev" == "eth0" ]];        then ty=0; fi       # its the internal ethernet
    if [[ "$dev" =~ ^eth[1-9]$ ]];    then ty=1; fi       # seems to be an ethernet port
    if [[ "$dev" =~ ^wlan[0-9]$ ]];   then ty=3; fi       # seems to be a wireless interface
    if [[ "$vend" =~ ^.*apple.*$ ]] || [[ "$mod" =~ ^.*iphone.*$ ]]; then ty=2; fi  # likely an iPhone
    if [[ "$drv" == "rndis_host" ]];  then ty=2; fi       # look like an USB tethering device (e.g. Android phone)
    if [[ "$dev" =~ ppp[0-9] ]];      then ty=5;          # its a dial in mobile data modem
    elif [[ ! -z $vid ]] && [[ ! -z $pid ]] && [[ "${vidpids[@]}" =~ "$vid/$pid"  ]]; then ty=4; fi # mobile data in router mode
# store found devices in reverse order
    devs=("$dev" $devs)
    typs=($ty $typs)
    vends=("$vend" $vends)
    mods=("$mod" $mods)
# append device to list
#    devs+=("$dev")
#    typs+=($ty)
#    vends+=("$vend")
#    mods+=("$mod")
  done
fi

# if no ppp device was found, check for not connected modem (ttyUSB device)
idx="-"
if [[ ! -z $rawdevs ]] && [[ "${rawdevs[@]}" =~ "ppp0" ]] ; then   # ppp0 device exists -> get index
  for i in "${!devs[@]}"; do
    if [[ "${devs[$i]}" == "ppp0" ]]; then idx=$i; fi
  done
fi
devmodem=`find /sys/bus/usb/devices/usb*/ -name dev | sed -rn 's/.*(ttyUSB0).*/\1/p'`   # check for ttyUSB0
if [[ ! -z $devmodem ]]; then
   udevinfo=`udevadm info --name="$devmodem" 2> /dev/null`
   mod=`echo -e $(echo "$udevinfo" | sed -rn 's/.*ID_MODEL_ENC=(.*)$/\1/p')`
   if [[ -z $mod ]] || [[ "$mod" =~ ^[0-9a-f]*$ ]] ; then
     mod=`echo "$udevinfo" | sed -rn 's/.*ID_MODEL_FROM_DATABASE=(.*)$/\1/p'`
   fi
   vend=`echo -e $(echo "$udevinfo" | sed -rn 's/.*ID_VENDOR_ENC=(.*)$/\1/p')`
   if [[ -z $mod ]] || [[ "$vend" =~ ^[0-9a-f]*$ ]] ; then
     vend=`echo "$udevinfo" | sed -rn 's/.*ID_VENDOR_FROM_DATABASE=(.*)$/\1/p'`
   fi
   if [[ -z $mod ]]; then mod=$vend; fi
   if [[ -z $vend ]]; then vend=$mod; fi
   vid=`echo "$udevinfo" | sed -rn 's/.*ID_VENDOR_ID=(\w*).*$/\1/p'`
   pid=`echo "$udevinfo" | sed -rn 's/.*ID_MODEL_ID=(\w*).*$/\1/p'`
   if [[ ! -z $vid ]] && [[ ! -z $pid ]] && [[ "${vidpids[@]}" =~ "$vid/$pid"  ]]; then
     if [[ ! "$idx" == "-" ]]; then
       devs[$idx]="ppp0"
       typs[$idx]=5
       vends[$idx]="$vend"
       mods[$idx]="$mod"
     else 
       devs+=("ppp0")
       typs+=(5)
       vends+=("$vend")
       mods+=("$mod")
    fi
  fi
fi


path=`dirname $0`

# create json output

outjs='{ "clients": '${#devs[@]}', "device": [ '
if [[ ! -z $devs ]]; then
  for i in "${!devs[@]}"; do
    outjs+='{ "name": "'${devs[$i]}'", "vendor": "'${vends[$i]}'", "type": '${typs[$i]}
    mod=${mods[$i]}
    ipadd=`ifconfig ${devs[$i]} 2> /dev/null | sed -rn 's/.*inet ([0-9\.]+) .*/\1/p'`
    # get more information (signal strength, connection mode)  for wlan and mobile data interfaces
    if [[ ${typs[$i]} == 3  ]]; then      # its a wlan interface 
       iwout=`iw dev ${devs[$i]} link 2> /dev/null`
       res=`echo "$iwout" | sed -rn 's/.*SSID.*/1/ip'`
       if [[ ! -z $res ]] && [[ $simple == 0 ]]; then
         outjs+=', "connected": "y"'
         res=`echo "$iwout" | sed -rn 's/.*SSID: (\w*).*/\1/p'`
         outjs+=', "ssid": "'$res'"'
         res=`echo "$iwout" | sed -rn 's/^Connected to ([0-9a-f\:]*).*$/\1/p'`
         outjs+=', "ap-mac": "'$res'"'
         res=`echo "$iwout" | sed -rn 's/.*signal: (.*)$/\1/p'`
         sig=`echo $res | grep -oP '^[0-9\.-]*'`
         if [[ $sig -gt -50 ]]; then qual=100;
         elif [[ $sig -lt -100 ]]; then qual=0;
         else qual=$(bc <<< "scale=0;res=$sig*2+200;res/1"); fi
         outjs+=', "signal": "'$res' ('$qual'%)"'
         res=`echo "$iwout" | sed -rn 's/.*bitrate: ([0-9\.]* \w*\/s).*$/\1/p' | head -1`
         outjs+=', "bitrate": "'$res'"'
         res=`echo "$iwout" | sed -rn 's/.*freq: (.*)$/\1/p'`
         outjs+=', "freq": "'$res'"'
       else 
          outjs+=', "connected": "n"'
       fi
    elif [[ ${typs[$i]} == 4  ]] && [[ $simple == 0 ]]; then    # its a mobile data router -> assume Huawei HiLink
       res=`ip link show ${devs[$i]} 2> /dev/null | grep -oP ' UP '`
       apiadd=$(ifconfig -a | grep -i ${devs[$i]} -A 1 | grep -oP "(?<=inet )([0-9]{1,3}\.){3}")"1"	# get the ip address of the hilink API
       res=`$path/info_huawei.sh mode hilink $apiadd`
       outjs+=', "mode": "'$res'"'
       res=`$path/info_huawei.sh device hilink $apiadd`
       if [[ ! "$res" == "none"  ]]; then mod=$res; fi
       res=`$path/info_huawei.sh signal hilink $apiadd`
       outjs+=', "signal": "'$res'"'
       ipadd=`$path/info_huawei.sh ipaddress hilink $apiadd`
       if [[ -z $ipadd ]] || [[ "$ipadd" == "none" ]]; then outjs+=', "connected": "n"'
       else outjs+=', "connected": "y"'; fi
       res=`$path/info_huawei.sh operator hilink $apiadd`
       outjs+=', "operator": "'$res'"'
    elif [[ ${typs[$i]} == 5  ]] && [[ $simple == 0 ]]; then    # its a mobile data modem  -> infos via AT commands
       res=`ip link show ${devs[$i]} 2> /dev/null | grep -oP ' UP '`
       if [[ -z $res ]] && [[ -z $ipadd ]]; then outjs+=', "connected": "n"'
       else outjs+=', "connected": "y"'; fi
       res=`$path/info_huawei.sh mode modem`
       outjs+=', "mode": "'$res'"'
       res=`$path/info_huawei.sh device modem`
       if [[ ! "$res" == "none"  ]]; then mod=$res; fi
       res=`$path/info_huawei.sh signal modem`
       outjs+=', "signal": "'$res'"'
       res=`$path/info_huawei.sh operator modem`
       outjs+=', "operator": "'$res'"'
    elif [[ $simple == 0 ]]; then
       res=`ip link show ${devs[$i]} 2> /dev/null | grep -oP '( UP | UNKNOWN)'`
       if [[ -z $res ]]; then outjs+=', "connected": "n"'
       else outjs+=', "connected": "y"'; fi
    fi
    if [[ $simple == 0 ]]; then outjs+=', "ipaddress": "'$ipadd'"'; fi
    outjs+=', "model": "'$mod'"'
    if [[ "$i" == "$((${#devs[@]} - 1))" ]]; then outjs+=" }";
    else outjs+=" },"; fi
  done
fi
outjs+=" ] }"

echo $outjs
