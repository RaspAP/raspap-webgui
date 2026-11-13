#!/bin/bash
#
# RaspAP WireGuard forwarding rules
#
# Description: Adds the iptables rules that forward the traffic of a local
# interface (normally the access point) through a WireGuard client tunnel.
# Called from the PostUp and PostDown rules of a WireGuard client
# configuration. With "ap" (or without an interface), the script uses the
# access point interface of hostapd.conf when the tunnel starts, so the rules
# stay correct after a change of the AP interface. The rules have a comment,
# so "down" removes exactly the rules of "up".
# License: GNU General Public License v3.0
#
# You are not obligated to bundle the LICENSE file with your RaspAP projects as long
# as you leave these references intact in the header comments of your source files.
#
# Usage: wg-forward.sh up <wg-interface> [ap|<interface>]
#        wg-forward.sh down <wg-interface>
#        wg-forward.sh migrate <config-file> ...

set -o nounset

readonly hostapd_conf="/etc/hostapd/hostapd.conf"
readonly self="/etc/raspap/networking/wg-forward.sh"

usage() {
    echo "Usage: $0 up <wg-interface> [ap|<interface>]" >&2
    echo "       $0 down <wg-interface>" >&2
    echo "       $0 migrate <config-file> ..." >&2
    exit 1
}

valid_iface() {
    [[ "$1" =~ ^[A-Za-z0-9_.-]{1,15}$ ]]
}

# The access point interface, from hostapd.conf
ap_iface() {
    sed -n 's/^interface=\([A-Za-z0-9_.-]\{1,15\}\)$/\1/p' "$hostapd_conf" 2>/dev/null | head -n 1
}

# a comment without spaces, so that the rules can be removed without eval
comment_of() {
    echo "raspap-wg-forward-$1"
}

down() {
    local wg="$1" comment table rule legacy
    comment="$(comment_of "$wg")"
    # the rules of older RaspAP versions, without a comment. An update can
    # migrate the configuration while the tunnel is up, then wg-quick runs
    # this script for the rules of the old PostUp line.
    legacy="^-A (FORWARD -i [A-Za-z0-9_.-]+ -o $wg -j ACCEPT|FORWARD -i $wg -o [A-Za-z0-9_.-]+ -m state --state RELATED,ESTABLISHED -j ACCEPT|POSTROUTING -o $wg -j MASQUERADE)$"
    for table in filter nat; do
        iptables -t "$table" -S 2>/dev/null | grep -E -e "--comment $comment " -e "$legacy" | sed 's/^-A/-D/' |
            while read -r rule; do
                # shellcheck disable=SC2086
                iptables -t "$table" $rule 2>/dev/null || true
            done
    done
}

up() {
    local wg="$1" lan="${2:-ap}" comment
    if [ "$lan" = "ap" ]; then
        lan="$(ap_iface)"
        if [ -z "$lan" ]; then
            echo "wg-forward: no access point interface in $hostapd_conf" >&2
            return 1
        fi
    fi
    valid_iface "$lan" || { echo "wg-forward: invalid interface name" >&2; return 1; }
    comment="$(comment_of "$wg")"
    down "$wg"
    iptables -A FORWARD -i "$lan" -o "$wg" -m comment --comment "$comment" -j ACCEPT
    iptables -A FORWARD -i "$wg" -o "$lan" -m state --state RELATED,ESTABLISHED -m comment --comment "$comment" -j ACCEPT
    iptables -t nat -A POSTROUTING -o "$wg" -m comment --comment "$comment" -j MASQUERADE
}

# Replaces the forwarding rules of older RaspAP versions in a WireGuard
# configuration with calls to this script. A rule for the current AP
# interface becomes "ap", so it follows a change of the AP interface. The
# original file is kept as <name>.forward.bak. The name does not contain
# ".conf", so the WireGuard page does not show the backup as a configuration.
migrate() {
    local conf backup ap lan legacy_up legacy_down
    legacy_up='^PostUp = iptables -A FORWARD -i ([A-Za-z0-9_.-]+) -o %i -j ACCEPT; iptables -A FORWARD -i %i -o \1 -m state --state RELATED,ESTABLISHED -j ACCEPT; iptables -t nat -A POSTROUTING -o %i -j MASQUERADE$'
    legacy_down='^PostDown = iptables -D FORWARD -i ([A-Za-z0-9_.-]+) -o %i -j ACCEPT; iptables -D FORWARD -i %i -o \1 -m state --state RELATED,ESTABLISHED -j ACCEPT; iptables -t nat -D POSTROUTING -o %i -j MASQUERADE$'
    ap="$(ap_iface)"
    for conf in "$@"; do
        if [ ! -f "$conf" ] || [ -L "$conf" ]; then
            continue
        fi
        lan="$(sed -n -E "s/$legacy_up/\1/p" "$conf" | head -n 1)"
        [ -n "$lan" ] || continue
        [ "$lan" = "$ap" ] && lan="ap"
        backup="${conf%.conf}.forward.bak"
        cp -a "$conf" "$backup"
        sed -E -i \
            -e "s|$legacy_up|PostUp = $self up %i $lan|" \
            -e "s|$legacy_down|PostDown = $self down %i|" \
            "$conf"
        echo "Migrated forwarding rules in $conf (backup: $backup)"
    done
}

[ "$#" -ge 2 ] || usage
action="$1"
shift

case "$action" in
    up)
        [ "$#" -le 2 ] || usage
        valid_iface "$1" || usage
        up "$1" "${2:-ap}"
        ;;
    down)
        [ "$#" -eq 1 ] || usage
        valid_iface "$1" || usage
        down "$1"
        ;;
    migrate)
        migrate "$@"
        ;;
    *)
        usage
        ;;
esac
