#!/bin/bash
# When wireless client AP mode is enabled, this script handles starting
# up network services in a specific order and timing to avoid race conditions.

PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin
NAME=raspap
DESC="Service control for RaspAP"
CONFIGFILE="/etc/raspap/hostapd.ini"

positional=()
while [[ $# -gt 0 ]]
do
key="$1"

case $key in
    -i|--interface)
    interface="$2"
    shift # past argument
    shift # past value
    ;;
    -s|--seconds)
    seconds="$2"
    shift # past argument
    shift # past value
    ;;
esac
done
set -- "${positional[@]}"

echo "Stopping network services..."
systemctl stop hostapd.service
systemctl stop dnsmasq.service
systemctl stop dhcpcd.service

if [ -r "$CONFIGFILE" ]; then
    declare -A config
    while IFS=" = " read -r key value; do
        config["$key"]="$value"
    done < "$CONFIGFILE"

    if [ "${config[WifiAPEnable]}" = 1 ]; then
        if [ "${interface}" = "uap0" ]; then
            echo "Removing uap0 interface..."
            iw dev uap0 del
 
            echo "Adding uap0 interface to ${config[WifiManaged]}"
            iw dev ${config[WifiManaged]} interface add uap0 type __ap
            # Bring up uap0 interface
            ifconfig uap0 up
        fi
    fi
fi

# Start services, mitigating race conditions
echo "Starting network services..."
systemctl start hostapd.service
sleep "${seconds}"

systemctl start dhcpcd.service
sleep "${seconds}"

systemctl start dnsmasq.service

echo "RaspAP service start DONE"

