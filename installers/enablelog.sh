#!/bin/bash
/bin/sed -i 's|#DAEMON_OPTS=""|DAEMON_OPTS=" -f /tmp/hostapd.log"|' /etc/default/hostapd
touch /tmp/hostapd.log
