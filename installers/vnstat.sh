#!/bin/bash
#
# RaspAP vnstat helper
#
# Description: Adds network interfaces to the vnstat database, so that the
# data usage page has statistics for them. vnstat adds only the interfaces
# that exist when it is installed. www-data runs this script with sudo.
# "sync" adds the interfaces that RaspAP configures and that exist. vnstatd
# removes an interface that is down and has no data, thus a VPN tunnel that
# is down gets added at a later sync. "add" adds one interface that exists,
# for the button of the data usage page. The script never removes an
# interface, so the statistics stay.
# Installed as /usr/local/sbin/raspap-vnstat.sh.
# License: GNU General Public License v3.0
#
# You are not obligated to bundle the LICENSE file with your RaspAP projects as long
# as you leave these references intact in the header comments of your source files.
#
# Usage: raspap-vnstat.sh sync
#        raspap-vnstat.sh add <interface>

set -o nounset
set -o pipefail

readonly hostapd_ini="/etc/raspap/hostapd.ini"
readonly dhcpcd_conf="/etc/dhcpcd.conf"

die() {
    echo "raspap-vnstat: $2" >&2
    exit "$1"
}

usage() {
    die 2 "usage: $0 sync, or add <interface>"
}

valid_name() {
    [[ "$1" =~ ^[A-Za-z0-9_.-]{1,15}$ ]] && [ "$1" != "lo" ]
}

# Prints the interfaces that RaspAP configures, one per line. Some of them
# do not exist now, for example a VPN tunnel that is down.
configured() {
    local key value iface
    # the access point, also as bridge or as uap0 (see servicestart.sh)
    if [ -f "$hostapd_ini" ]; then
        while IFS='=' read -r key value; do
            key="${key//[[:space:]]/}"
            value="${value//[[:space:]]/}"
            case "$key" in
                WifiInterface) echo "$value" ;;
                BridgedEnable) [ "$value" = 1 ] && echo br0 ;;
                WifiAPEnable) [ "$value" = 1 ] && echo uap0 ;;
            esac
        done < "$hostapd_ini"
    fi
    # the interfaces of the DHCP and networking settings. "# RaspAP default
    # configuration" is the block for all interfaces.
    if [ -f "$dhcpcd_conf" ]; then
        sed -n 's/^# RaspAP \([A-Za-z0-9_.-]\{1,15\}\) configuration$/\1/p' "$dhcpcd_conf" | grep -vx default
    fi
    # the WiFi client: RaspAP does not keep its interface, so add each
    # wireless interface
    for iface in /sys/class/net/*; do
        [ -d "$iface/wireless" ] || [ -e "$iface/phy80211" ] || continue
        echo "${iface##*/}"
    done
    # the VPN tunnels that have a configuration
    [ -f /etc/wireguard/wg0.conf ] && echo wg0
    [ -f /etc/openvpn/client/client.conf ] && echo tun0
    return 0
}

# Prints the interfaces in the vnstat database, one per line
in_database() {
    vnstat --dbiflist 1 2>/dev/null
}

# Adds the interfaces that RaspAP configures, that exist, and that are not
# in the database
sync() {
    local have iface
    have="$(in_database)" || die 1 "vnstat --dbiflist failed"
    for iface in $(configured | sort -u); do
        valid_name "$iface" || continue
        [ -e "/sys/class/net/$iface" ] || continue
        grep -Fqx -- "$iface" <<< "$have" && continue
        if vnstat --add -i "$iface" > /dev/null; then
            echo "added $iface"
        else
            echo "raspap-vnstat: can not add $iface" >&2
        fi
    done
}

# Adds an interface that exists
add() {
    local iface="$1"
    valid_name "$iface" || die 2 "invalid interface name"
    [ -e "/sys/class/net/$iface" ] || die 2 "no interface $iface"
    in_database | grep -Fqx -- "$iface" && return 0
    vnstat --add -i "$iface" > /dev/null || die 1 "can not add $iface"
}

[ "$(id -u)" = 0 ] || die 1 "must run as root"
command -v vnstat > /dev/null || die 1 "vnstat is not installed"
[ "$#" -ge 1 ] || usage

case "$1" in
    sync)
        [ "$#" -eq 1 ] || usage
        sync
        ;;
    add)
        [ "$#" -eq 2 ] || usage
        add "$2"
        ;;
    *)
        usage
        ;;
esac
