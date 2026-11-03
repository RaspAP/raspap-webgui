#!/bin/bash
#
# Adds iptables rules to forward traffic from tun0 to the
# configured wireless interface. The web interface adds
# auth-user-pass and askpass to the OpenVPN client config
# itself, and installs it with raspap-vpn-file.sh.
# @author billz
# license: GNU General Public License v3.0

# Exit on error
set -o errexit
# Exit on error inside functions
set -o errtrace
# Turn on traces, disabled by default
#set -o xtrace

# Usage: configauth.sh <AP interface|"">
# www-data runs this script with sudo, so it checks the argument.
if [ "$#" -ne 1 ]; then
    echo "Usage: $0 <interface>" >&2
    exit 2
fi
interface=$1

if [ -n "$interface" ] && ! [[ "$interface" =~ ^[A-Za-z0-9_.-]{1,15}$ ]]; then
    echo "Invalid interface name" >&2
    exit 2
fi
readonly rulesv4="/etc/iptables/rules.v4"

# Configure NAT and forwarding with iptables
echo "Checking iptables rules"
rules=("-A POSTROUTING -o tun0 -j MASQUERADE")
if [ -n "$interface" ]; then
    rules+=(
    "-A FORWARD -i tun0 -o ${interface} -m state --state RELATED,ESTABLISHED -j ACCEPT"
    "-A FORWARD -i ${interface} -o tun0 -j ACCEPT"
    )
else
    echo "No AP interface: forwarding rules not changed"
fi

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
    sudo /usr/local/sbin/raspap-iptables-save.sh
fi

