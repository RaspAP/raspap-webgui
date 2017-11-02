#!/bin/bash
/bin/sed -i 's|DAEMON_OPTS=" -f /tmp/hostapd.log"|#DAEMON_OPTS=""|' /etc/default/hostapd

