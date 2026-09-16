#!/bin/bash
#
# RaspAP toggle routing script
#
# Description: Adds/deletes iptables NAT rules to enable network traffic
# routing between the specified interfaces. Roughly analogous to WireGuard's
# PostUp and PostDown rules.
# Author: @billz <billzimmerman@gmail.com>
# License: GNU General Public License v3.0
#
# You are not obligated to bundle the LICENSE file with your RaspAP projects as long
# as you leave these references intact in the header comments of your source files.

# Exit on error
set -o errexit
# Exit on error inside functions
set -o errtrace
# Turn on traces, disabled by default
# set -o xtrace

readonly rulesv4="/etc/iptables/rules.v4"

if [ "$#" -ne 3 ]; then
    echo "Usage: $0 <input> <output> <action>"
    exit 1
fi

input="$1"
output="$2"
action="$3"

case "$3" in
    "add")
        action="A"
        ;;
    "delete")
        action="D"
        ;;
    *)
        echo "Invalid action. Please use 'add' or 'delete'."
        exit 1
        ;;
esac

# Identify WAN interface by checking which of the two has the default route.
# The caller may pass either (WAN, LAN) or (LAN, WAN) depending on the use case
# (WLAN routing vs. USB tethering)
wan=$(ip route show default | awk '/dev/ {for(i=1;i<=NF;i++) if ($i=="dev") {print $(i+1); exit}}')
if [ "$wan" = "$output" ]; then
    wan_iface="$output"
    lan_iface="$input"
else
    wan_iface="$input"
    lan_iface="$output"
fi

rules=(
    "-t nat -${action} POSTROUTING -o ${wan_iface} -j MASQUERADE"
    "-${action} FORWARD -i ${wan_iface} -o ${lan_iface} -m state --state RELATED,ESTABLISHED -j ACCEPT"
    "-${action} FORWARD -i ${lan_iface} -o ${wan_iface} -j ACCEPT"
)

for rule in "${rules[@]}"; do
    if grep -- "$rule" $rulesv4 > /dev/null; then
        echo "Rule already exits: ${rule}"
    else
        echo "Executing rule: sudo iptables ${rule}"
        sudo iptables $rule || echo "Unable to execute iptables"
        added=true
    fi
done

# Persist rules if added
if [ "$added" = true ]; then
    echo "Persisting IP tables rules"
    sudo iptables-save | sudo tee $rulesv4 > /dev/null || echo "Unable to execute iptables-save"
fi

