#!/bin/bash

# When wireless client AP mode is enabled, this script handles starting up network services in a specific order and timing to avoid race conditions.
# Todo: update /etc/rc.local script with /bin/bash /usr/local/bin/hostapdstart.sh to enable at system startup

# Make sure services are not running
echo "Stopping network services..."
systemctl stop hostapd.service
systemctl stop dnsmasq.service
systemctl stop dhcpcd.service

# Check that no uap0 interface exists 
echo "Removing uap0 interface..."
iw dev uap0 del

# Add uap0 interface 
echo "Adding uap0 interface..."
iw dev wlan0 interface add uap0 type __ap

# Modify iptables (todo: persist to /etc/rc.local as with default rules)
echo "IPV4 forwarding: setting..."
sysctl net.ipv4.ip_forward=1
echo "Editing IP tables..."
iptables -t nat -A POSTROUTING -s 192.168.50.0/24 ! -d 192.168.50.0/24 -j MASQUERADE

# Enable uap0 interface
ifconfig uap0 up

# Start hostapd, mitigating race condition
echo "Starting hostapd service..."
systemctl start hostapd.service
sleep 5

# Start dhcpcd
echo "Starting dhcpcd service..."
systemctl start dhcpcd.service
sleep 5

echo "Starting dnsmasq service..."
systemctl start dnsmasq.service

echo "hostapdstart DONE"

