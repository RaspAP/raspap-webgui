#!/bin/bash
#
# RaspAP USB tethering script
#
# Description: Automatically enables or disables NAT routing when a USB
# tethering interface (typically  usb0) is connected or removed. Invoked by
# the udev rule 90-raspap-usb-tethering.rules.
# Author: @billz <billzimmerman@gmail.com>
# License: GNU General Public License v3.0
#
# You are not obligated to bundle the LICENSE file with your RaspAP projects as long
# as you leave these references intact in the header comments of your source files.

PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

readonly RASPAP_CONFIG="/etc/raspap"
readonly HOSTAPD_INI="${RASPAP_CONFIG}/hostapd.ini"
readonly TOGGLE_ROUTING="${RASPAP_CONFIG}/networking/toggle-routing.sh"
readonly RULES_V4="/etc/iptables/rules.v4"

if [ "$#" -ne 2 ]; then
    echo "Usage: $0 <add|remove> <interface>" >&2
    exit 1
fi

action="$1"
iface="$2"

# Read AP interface from hostapd.ini; fall back to wlan0
ap_iface=$(awk -F' *= *' '/^WifiInterface/ {print $2; exit}' "$HOSTAPD_INI" 2>/dev/null | tr -d '[:space:]')
ap_iface="${ap_iface:-wlan0}"

case "$action" in
    add)
        "$TOGGLE_ROUTING" "$iface" "$ap_iface" add
        ;;
    remove)
        iptables -t nat -D POSTROUTING -o "$iface" -j MASQUERADE 2>/dev/null || true
        iptables -D FORWARD -i "$iface" -o "$ap_iface" -m state --state RELATED,ESTABLISHED -j ACCEPT 2>/dev/null || true
        iptables -D FORWARD -i "$ap_iface" -o "$iface" -j ACCEPT 2>/dev/null || true
        iptables-save | tee "$RULES_V4" > /dev/null 2>&1 || true
        ;;
    *)
        echo "Invalid action '$action'. Use 'add' or 'remove'." >&2
        exit 1
        ;;
esac
