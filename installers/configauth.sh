#!/bin/bash
#
# Updates openvpn client.conf with auth credentials and,
# optionally, the private key password (askpass), adds
# iptables rules to forward traffic from tun0 to configured
# wireless interface
# @author billz
# license: GNU General Public License v3.0

# Exit on error
set -o errexit
# Exit on error inside functions
set -o errtrace
# Turn on traces, disabled by default
#set -o xtrace

# Usage: configauth.sh <uploaded config in /tmp/ovpn/> <0|1 auth> <AP interface|""> <0|1 keypass>
# www-data runs this script with sudo, so it checks each argument.
if [ "$#" -ne 4 ]; then
    echo "Usage: $0 <config in /tmp/ovpn/> <0|1> <interface> <0|1>" >&2
    exit 2
fi
file=$1
auth=$2
interface=$3
keypass=$4

# the upload of the web interface, a regular file in /tmp/ovpn
case "$(realpath -e -- "$file" 2>/dev/null)" in
    /tmp/ovpn/*) ;;
    *) echo "Invalid OpenVPN configuration file" >&2; exit 2 ;;
esac
if [ -L "$file" ] || [ ! -f "$file" ]; then
    echo "Invalid OpenVPN configuration file" >&2
    exit 2
fi
if ! [[ "$auth" =~ ^[01]$ && "$keypass" =~ ^[01]$ ]]; then
    echo "Invalid auth or keypass value" >&2
    exit 2
fi
if [ -n "$interface" ] && ! [[ "$interface" =~ ^[A-Za-z0-9_.-]{1,15}$ ]]; then
    echo "Invalid interface name" >&2
    exit 2
fi
readonly rulesv4="/etc/iptables/rules.v4"

if [ "$auth" = 1 ]; then
    echo "Enabling auth-user-pass in OpenVPN client.conf"
    line='auth-user-pass'
    if grep -q "$line" "$file"; then
        echo "Updating $line"
        sudo sed -i "s/$line/$line login.conf/g" "$file"
    else
        echo "Adding $line"
        sudo sed -i "$ a $line login.conf" "$file"
    fi
fi

if [ "$keypass" = 1 ]; then
    echo "Enabling askpass in OpenVPN client.conf"
    sudo sed -i '/^[[:space:]]*askpass\([[:space:]]\|$\)/d' "$file"
    sudo sed -i '$ a askpass keypass.conf' "$file"
fi

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

