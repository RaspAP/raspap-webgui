#!/bin/bash
# When wireless client AP or Bridge mode is enabled, this script handles starting
# up network services in a specific order and timing to avoid race conditions.

PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin
NAME=raspapd
DESC="Service control for RaspAP"
CONFIGFILE="/etc/raspap/hostapd.ini"
DAEMONPATH="/lib/systemd/system/raspapd.service"
OPENVPNENABLED=$(pidof openvpn | wc -l)

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
    shift
    shift
    ;;
    -a|--action)
    action="$2"
    shift
    shift
    ;;
esac
done
set -- "${positional[@]}"

echo "Stopping network services..."
if [ $OPENVPNENABLED -eq 1 ]; then
    systemctl stop openvpn-client@client
fi
systemctl stop systemd-networkd
systemctl stop hostapd.service
systemctl stop dnsmasq.service
systemctl stop dhcpcd.service

if [ "${action}" = "stop" ]; then
    echo "Services stopped. Exiting."
    exit 0
fi

if [ -f "$DAEMONPATH" ] && [ ! -z "$interface" ]; then
    echo "Changing RaspAP Daemon --interface to $interface"
    sed -i "s/\(--interface \)[[:alnum:]]*/\1$interface/" "$DAEMONPATH"
fi

if [ -r "$CONFIGFILE" ]; then
    declare -A config
    while IFS=" = " read -r key value; do
        config["$key"]="$value"
    done < "$CONFIGFILE"

    if [ "${config[BridgedEnable]}" = 1 ]; then
        if [ "${interface}" = "br0" ]; then
            echo "Stopping systemd-networkd"
            systemctl stop systemd-networkd

            echo "Restarting eth0 interface..."
            ip link set down eth0
            ip link set up eth0

            echo "Removing uap0 interface..."
            iw dev uap0 del

            echo "Enabling systemd-networkd"
            systemctl start systemd-networkd
            systemctl enable systemd-networkd
        fi
    else
        echo "Disabling systemd-networkd"
        systemctl disable systemd-networkd

        ip link ls up | grep -q 'br0' &> /dev/null
        if [ $? == 0 ]; then
            echo "Removing br0 interface..."
            ip link set down br0
            ip link del dev br0
        fi

        if [ "${config[WifiAPEnable]}" = 1 ]; then
            if [ "${interface}" = "uap0" ]; then

                ip link ls up | grep -q 'uap0' &> /dev/null
                if [ $? == 0 ]; then
                    echo "Removing uap0 interface..."
                    iw dev uap0 del
                fi

                echo "Adding uap0 interface to ${config[WifiManaged]}"
                iw dev ${config[WifiManaged]} interface add uap0 type __ap
                # Bring up uap0 interface
                ifconfig uap0 up
            fi
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

if [ $OPENVPNENABLED -eq 1 ]; then
    systemctl start openvpn-client@client
fi

echo "RaspAP service start DONE"

