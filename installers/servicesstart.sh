#!/bin/bash

# When wireless client AP mode is enabled, this script handles starting up network services in a specific order and timing to avoid race conditions.
# Todo: update /etc/rc.local script with /bin/bash /etc/raspap/hostapd/servicesstart.sh to enable at system startup

echo "Stopping network services..."
systemctl stop hostapd.service
systemctl stop dnsmasq.service
systemctl stop dhcpcd.service

echo "Removing uap0 interface..."
iw dev uap0 del
 
echo "Adding uap0 interface..."
iw dev wlan0 interface add uap0 type __ap

# Add iptables rules (todo: persist to /etc/rc.local as with default rules)
echo "IPV4 forwarding: setting..."
sysctl net.ipv4.ip_forward=1
echo "Editing IP tables..."
iptables -t nat -A POSTROUTING -s 192.168.50.0/24 ! -d 192.168.50.0/24 -j MASQUERADE

# Bring up uap0 interface
ifconfig uap0 up

# Start services, mitigating race conditions
echo "Starting hostapd service..."
systemctl start hostapd.service
sleep 5

echo "Starting dhcpcd service..."
systemctl start dhcpcd.service
sleep 5

echo "Starting dnsmasq service..."
systemctl start dnsmasq.service

echo "servicesstart DONE"

