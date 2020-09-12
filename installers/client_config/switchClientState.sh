#!/bin/bash
#
# parameters: up or down
# current client from getClients.sh
#
# requires: getClients.sh, onoff_huawei_hilink.sh in same path
#           ifconfig, ifup, ifdown, systemd-cat
#
#
# zbchristian 2020

path=`dirname $0`
clients=$($path/getClients.sh simple)

if [[ -z $clients ]] || [[ $(echo $clients| grep -oP '(?<="clients": )\d') == 0  ]]; then echo "$0 : No client found"|systemd-cat; exit; fi

devs=( $(echo $clients | grep -oP '(?<="name": ")\w*(?=")') )
types=( $(echo $clients | grep -oP '(?<="type": )\d') )

# find the device with the max type number
imax=0
type=0
for i in "${!devs[@]}"; do
  if [[ ${types[$i]} > $type ]]; then  imax=$i; type=${types[$i]}; fi
done
device=${devs[$imax]}

echo "$0: try to set $device $1" | systemd-cat

connected=`ifconfig -a | grep -i $device -A 1 | grep -oP "(?<=inet )([0-9]{1,3}\.){3}[0-9]{1,3}"`

if [ -z "$connected" ] && [[ $1 == "up" ]]; then
   if [[ $type == 3 ]]; then  ip link set $device up; fi
   if [[ $type == 5 ]]; then  ifup $device; fi
fi
if [[ ! -z "$connected" ]] &&  [[ $1 == "down" ]]; then
   if [[ $type == 3 ]]; then  ip link set $device down; fi
   if [[ $type == 5 ]]; then  ifdown $device; fi
fi
if [[ $type == 4 ]]; then
   ipadd=$(echo $connected | grep -oP "([0-9]{1,3}\.){2}[0-9]{1,3}")".1"  # get ip address of the Hilink API
   $path/onoff_huawei_hilink.sh $1 $ipadd; 
fi

