#!/bin/bash
#
# Updates openvpn client.conf with auth credentials,
# adds iptables rules to forward traffic from tun0
# to configured wireless interface
# @author billz
# license: GNU General Public License v3.0

# Exit on error
set -o errexit
# Exit on error inside functions
set -o errtrace
# Turn on traces, disabled by default
#set -o xtrace

file=$1
auth=$2
interface=$3
readonly rulesv4="/etc/iptables/rules.v4"

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
echo "Checking iptables rules"
rules=(
"-A POSTROUTING -o tun0 -j MASQUERADE"
"-A FORWARD -i tun0 -o ${interface} -m state --state RELATED,ESTABLISHED -j ACCEPT"
"-A FORWARD -i wlan0 -o tun0 -j ACCEPT"
)

for rule in "${rules[@]}"; do
    if grep -- "$rule" $rulesv4 > /dev/null; then
        echo "Rule already exits: ${rule}"
    else
        rule=$(sed -e 's/^\(-A POSTROUTING\)/-t nat \1/' <<< $rule)
        echo "Adding rule: ${rule}"
        sudo iptables $rule
        added=true
    fi
done

if [ "$added" = true ]; then
    echo "Persisting IP tables rules"
    sudo iptables-save | sudo tee $rulesv4 > /dev/null
fi

