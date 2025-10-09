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

# Load config file into associative array
declare -A config
if [ -r "$CONFIGFILE" ]; then
    while IFS=" = " read -r key value; do
        config["$key"]="$value"
    done < "$CONFIGFILE"
fi

# Set interface from config if not set by parameter
if [ -z "$interface" ]; then
    if [ -n "${config[WifiInterface]}" ]; then
        interface="${config[WifiInterface]}"
        echo "Interface not provided. Using interface from config: $interface"
    else
        interface="wlan0"
        echo "Interface not provided and not found in config. Defaulting to: $interface"
    fi
fi

echo "Stopping network services..."
if [ $OPENVPNENABLED -eq 1 ]; then
    systemctl stop openvpn-client@client
fi
systemctl stop systemd-networkd
systemctl stop hostapd.service
systemctl stop dnsmasq.service
systemctl stop dhcpcd.service
systemctl stop 'raspap-network-activity@*.service'

if [ "${action}" = "stop" ]; then
    echo "Services stopped. Exiting."
    exit 0
fi

if [ -f "$DAEMONPATH" ] && [ -n "$interface" ]; then
    echo "Changing RaspAP Daemon --interface to $interface"
    sed -i "s/\(--interface \)[[:alnum:]]*/\1$interface/" "$DAEMONPATH"
fi

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

# Start services, mitigating race conditions
echo "Starting network services..."
systemctl start hostapd.service
sleep "${seconds}"

systemctl start dhcpcd.service
sleep "${seconds}"

systemctl start dnsmasq.service

echo "Starting raspap-network-activity@${interface}.service"
systemctl start raspap-network-activity@${interface}.service

if [ $OPENVPNENABLED -eq 1 ]; then
    systemctl start openvpn-client@client
fi

if [ "${config[WifiAPEnable]}" = 1 ]; then
    echo "Reassociating wifi client interface..."
    sleep "${seconds}"
    wpa_cli -i ${config[WifiManaged]} reassociate
fi

echo "RaspAP service start DONE"

