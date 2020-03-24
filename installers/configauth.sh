#!/bin/bash
#
# Updates openvpn client.conf with auth credentials,
# adds iptables rules to forward traffic from tun0
# to configured wireless interface
# @author billz
# license: GNU General Public License v3.0

file=$1
auth=$2
interface=$3

if [ "$auth" = 1 ]; then
    echo "Enabling auth-user-pass in OpenVPN client.conf"
    line='auth-user-pass'
    if grep -q "$line" $file; then
        echo "Updating $line"
        sudo sed -i "s/$line/$line login.conf/g" $file
    else
        echo "Adding $line"
        sudo sed -i "$ a $line login.conf" $file
    fi
fi

# Configure NAT and forwarding with iptables
echo "Adding iptables rules for $interface"
sudo iptables -t nat -A POSTROUTING -o tun0 -j MASQUERADE
sudo iptables -A FORWARD -i tun0 -o $interface -m state --state RELATED,ESTABLISHED -j ACCEPT
sudo iptables -A FORWARD -i wlan0 -o tun0 -j ACCEPT

echo "Persisting IP tables rules"
sudo iptables-save | sudo tee /etc/iptables/rules.v4 > /dev/null

