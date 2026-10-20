#!/bin/bash
#
# RaspAP WireGuard kill switch
#
# Description: Blocks internet traffic of this device outside the WireGuard
# tunnel. Called from the PostUp and PreDown rules of a WireGuard client
# configuration. Local traffic is not blocked: private and link-local networks,
# broadcast, multicast, DHCP and the AP subnet. NTP, and DNS to the resolvers in
# /etc/resolv.conf, also stay outside the tunnel, so the clock can be set and
# names can be resolved while the tunnel is down. A WireGuard handshake fails
# when the clock is behind.
# License: GNU General Public License v3.0
#
# You are not obligated to bundle the LICENSE file with your RaspAP projects as long
# as you leave these references intact in the header comments of your source files.
#
# Usage: wg-killswitch.sh up <interface> [ap-subnet]
#        wg-killswitch.sh down <interface>

set -o nounset

readonly chain4="RASPAP_KILLSWITCH"
readonly chain6="RASPAP_KILLSWITCH6"
# before the wg-quick rules (32764, 32765)
readonly rule_pref="32700"
readonly resolv_conf="/etc/resolv.conf"

usage() {
    echo "Usage: $0 up <interface> [ap-subnet] | down <interface>" >&2
    exit 1
}

[ "$#" -ge 2 ] || usage
action="$1"
iface="$2"
ap_subnet="${3:-}"

# Removes the chains and routing rules; also cleans up after an interrupted run
cleanup() {
    while iptables -D OUTPUT -j "$chain4" 2>/dev/null; do :; done
    iptables -F "$chain4" 2>/dev/null || true
    iptables -X "$chain4" 2>/dev/null || true
    while ip6tables -D OUTPUT -j "$chain6" 2>/dev/null; do :; done
    ip6tables -F "$chain6" 2>/dev/null || true
    ip6tables -X "$chain6" 2>/dev/null || true
    while ip -4 rule del pref "$rule_pref" 2>/dev/null; do :; done
    while ip -6 rule del pref "$rule_pref" 2>/dev/null; do :; done
    return 0
}

# Prints the IPv4 or IPv6 nameservers from resolv.conf, without loopback addresses
nameservers() {
    local family="$1"
    awk '$1 == "nameserver" { sub(/%.*/, "", $2); print $2 }' "$resolv_conf" 2>/dev/null |
    while read -r ns; do
        case "$family:$ns" in
            4:127.*|6:::1) ;;
            4:*:*) ;;
            4:*) echo "$ns" ;;
            6:*:*) echo "$ns" ;;
        esac
    done
}

# Sends NTP, and DNS to the nameservers, through the main routing table, so
# that wg-quick does not route them into the tunnel. Not all iproute2 versions
# support these selectors, so a failure only gives a warning.
add_rules() {
    local family="$1" ns proto
    ip "-$family" rule add pref "$rule_pref" ipproto udp dport 123 lookup main ||
        echo "wg-killswitch: unable to add NTP rule (IPv$family)" >&2
    for ns in $(nameservers "$family"); do
        for proto in udp tcp; do
            ip "-$family" rule add pref "$rule_pref" to "$ns" ipproto "$proto" dport 53 lookup main ||
                echo "wg-killswitch: unable to add DNS rule for $ns" >&2
        done
    done
}

up() {
    local fwmark ns proto net

    set -o errexit
    cleanup
    fwmark="$(wg show "$iface" fwmark 2>/dev/null || echo off)"

    # IPv4
    iptables -N "$chain4"
    iptables -A "$chain4" -o "$iface" -j RETURN
    iptables -A "$chain4" -o lo -j RETURN
    if [ "$fwmark" != "off" ]; then
        iptables -A "$chain4" -m mark --mark "$fwmark" -j RETURN
    fi
    iptables -A "$chain4" -m addrtype --dst-type LOCAL,BROADCAST,MULTICAST -j RETURN
    for net in 10.0.0.0/8 172.16.0.0/12 192.168.0.0/16 169.254.0.0/16 $ap_subnet; do
        iptables -A "$chain4" -d "$net" -j RETURN
    done
    iptables -A "$chain4" -p udp --sport 67:68 --dport 67:68 -j RETURN
    iptables -A "$chain4" -p udp --dport 123 -j RETURN
    for ns in $(nameservers 4); do
        for proto in udp tcp; do
            iptables -A "$chain4" -d "$ns" -p "$proto" --dport 53 -j RETURN
        done
    done
    iptables -A "$chain4" -j REJECT
    iptables -I OUTPUT -j "$chain4"

    # IPv6
    ip6tables -N "$chain6"
    ip6tables -A "$chain6" -o "$iface" -j RETURN
    ip6tables -A "$chain6" -o lo -j RETURN
    if [ "$fwmark" != "off" ]; then
        ip6tables -A "$chain6" -m mark --mark "$fwmark" -j RETURN
    fi
    for net in fe80::/10 fc00::/7 ff00::/8; do
        ip6tables -A "$chain6" -d "$net" -j RETURN
    done
    ip6tables -A "$chain6" -p udp --dport 123 -j RETURN
    for ns in $(nameservers 6); do
        for proto in udp tcp; do
            ip6tables -A "$chain6" -d "$ns" -p "$proto" --dport 53 -j RETURN
        done
    done
    ip6tables -A "$chain6" -j REJECT
    ip6tables -I OUTPUT -j "$chain6"
    set +o errexit

    add_rules 4
    add_rules 6
}

case "$action" in
    up)
        up
        ;;
    down)
        cleanup
        ;;
    *)
        usage
        ;;
esac
