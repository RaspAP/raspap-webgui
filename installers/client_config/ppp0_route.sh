#!/bin/bash
#
# get gateway and ip address of UTMS modem connected to ppp0
# add a default route 
# called by /etc/network/interfaces.d/ppp0, when device is coming up
#
ppp0rt=""
let i=1
while [ -z "$ppp0rt" ] ; do
  let i+=1
  if [ $i -gt 20 ]; then
    exit 1
  fi
  sleep 1
  ppp0rt=`ip route list | grep -m 1 ppp0`
done
gate=`echo $ppp0rt |  sed -rn 's/(([0-9]{1,3}\.){3}[0-9]{1,3}).*ppp0.*src (([0-9]{1,3}\.){3}[0-9]{1,3})/\1/p'`
src=`echo $ppp0rt |  sed -rn 's/(([0-9]{1,3}\.){3}[0-9]{1,3}).*ppp0.*src (([0-9]{1,3}\.){3}[0-9]{1,3})/\3/p'`

ip route add default via $gate proto dhcp src $src metric 10
exit 0
