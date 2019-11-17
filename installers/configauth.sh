#!/bin/bash
#
# Updates openvpn client.conf with auth credentials,
# adds iptables rules to forward traffic from tun0
# to configured wireless interface
# @author billz
# license: GNU General Public License v3.0

file=$1
interface=$2

echo "Enabling auth credentials in OpenVPN client.conf"
line='auth-user-pass'

if grep -q "$line" $file; then
    echo "Updating $line"
    sudo sed -i "s/$line/$line login.conf/g" $file
else
    echo "Adding $line"
    sudo sed -i "$ a $line login.conf" $file
fi

echo "Adding iptables rules for $interface"

# Generate iptables entries to place into rc.local file.
# #RASPAP is for uninstall script
lines=(
"iptables -t nat -A POSTROUTING -o tun0 -j MASQUERADE #RASPAP"
"iptables -A FORWARD -i tun0 -o $interface -m state --state RELATED,ESTABLISHED -j ACCEPT #RASPAP"
"iptables -A FORWARD -i wlan0 -o tun0 -j ACCEPT #RASPAP"
)

for line in "${lines[@]}"; do
	if grep "$line" /etc/rc.local > /dev/null; then
		# iptables rule exists
	else
		sudo sed -i "s/^exit 0$/$line\nexit 0/" /etc/rc.local
	fi
done

# Force a reload of new settings in /etc/rc.local
sudo systemctl restart rc-local.service
sudo systemctl daemon-reload

