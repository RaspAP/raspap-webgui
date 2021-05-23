#!/bin/bash
# start with "sudo"
# parameters: up or on 
#
# switch client  state to UP
# the actual code is in PHP

# get webroot
webroot=$(cat /etc/lighttpd/lighttpd.conf | sed -rn 's/server.document-root\s*=\s*\"(.*)\"\s*$/\1/p')
webuser=$(cat /etc/lighttpd/lighttpd.conf | sed -rn 's/server.username\s*=\s*\"(.*)\"\s*$/\1/p')
if [ -z "$webroot" ] || [ ! -d "$webroot" ] || [ -z "$webuser" ]; then 
    echo "$0 : Problem to obtain webroot directory and/or web user - exit" | systemd-cat 
    exit
fi
cd $webroot

state=""
if [ ! -z $1 ] && [[ $1 =~ ^(up|on|UP|ON)$ ]]; then
  state="up"
elif [ ! -z $1 ] && [[ $1 =~ ^(down|off|DOWN|OFF)$ ]]; then
  state="down"
fi

[ -z "$state" ] && exit

sudo -u $webuser php << _EOF_
<?php
require_once("includes/config.php");
require_once("includes/get_clients.php");

 loadClientConfig();
 setClientState("$state");
?>
_EOF_


