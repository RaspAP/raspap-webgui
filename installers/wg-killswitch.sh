#!/bin/bash
#
# RaspAP WireGuard kill switch
#
# Description: Blocks internet traffic of this device outside the WireGuard
# tunnel. Called from the PostUp and PreDown rules of a WireGuard client
# configuration. Local traffic is not blocked: private and link-local networks,
# broadcast, multicast, DHCP and the AP subnet. Optionally, NTP and DNS to the
# resolvers in /etc/resolv.conf stay outside the tunnel. A WireGuard handshake
# fails when the clock is behind, and the clock can not be set through a tunnel
# that is down.
# License: GNU General Public License v3.0
#
# You are not obligated to bundle the LICENSE file with your RaspAP projects as long
# as you leave these references intact in the header comments of your source files.
#
# Usage: wg-killswitch.sh up <interface> [ap-subnet] [--allow-ntp] [--allow-dns] [--allow-portal]
#        wg-killswitch.sh down <interface>
#        wg-killswitch.sh migrate <config-file> ...

set -o nounset

readonly chain4="RASPAP_KILLSWITCH"
readonly chain6="RASPAP_KILLSWITCH6"
# priority of the routing rules when wg-quick added no rules of its own
readonly default_rule_pref="32700"
# protocol tag of the routing rules of this script, so that cleanup only
# deletes these rules and not the same rules of other tools
readonly rule_proto="250"
readonly hostapd_conf="/etc/hostapd/hostapd.conf"
readonly resolv_conf="/etc/resolv.conf"
# the only user whose HTTP requests (TCP port 80) go outside the tunnel with
# --allow-portal: the captive portal probe of raspap-captive-portal.sh
readonly probe_user="raspap-probe"
readonly portal_helper="/usr/local/sbin/raspap-captive-portal.sh"
readonly self="/etc/raspap/networking/wg-killswitch.sh"

usage() {
    echo "Usage: $0 up <interface> [ap-subnet] [--allow-ntp] [--allow-dns] [--allow-portal]" >&2
    echo "       $0 down <interface>" >&2
    echo "       $0 migrate <config-file> ..." >&2
    exit 1
}

# Removes the rule that older RaspAP versions added in PostUp:
# iptables -I OUTPUT ! -o <if> -m mark ! --mark <mark> -m addrtype ! --dst-type LOCAL ! -d <subnet> -j REJECT
# iptables -S prints it as:
# -A OUTPUT ! -d <subnet> ! -o <if> -m mark ! --mark <mark> -m addrtype ! --dst-type LOCAL -j REJECT ...
remove_legacy_rule() {
    local iface="$1" rule
    iptables -S OUTPUT 2>/dev/null |
        grep -E -- "^-A OUTPUT (! -d [^ ]+ )?! -o $iface -m mark ! --mark [^ ]+ -m addrtype ! --dst-type LOCAL -j REJECT" |
        while read -r rule; do
            # shellcheck disable=SC2086
            iptables ${rule/#-A/-D} 2>/dev/null || true
        done
}

# Removes the chains and routing rules; also cleans up after an interrupted run
cleanup() {
    local iface="$1"
    while iptables -D OUTPUT -j "$chain4" 2>/dev/null; do :; done
    iptables -F "$chain4" 2>/dev/null || true
    iptables -X "$chain4" 2>/dev/null || true
    while ip6tables -D OUTPUT -j "$chain6" 2>/dev/null; do :; done
    ip6tables -F "$chain6" 2>/dev/null || true
    ip6tables -X "$chain6" 2>/dev/null || true
    remove_rules 4
    remove_rules 6
    remove_legacy_rule "$iface"
    return 0
}

# Removes the NTP and DNS routing rules of this script. Their priority depends
# on the wg-quick rules, so they are found by content. The kernel deletes a
# rule only if its protocol tag is $rule_proto, so the same rules of other
# tools stay.
remove_rules() {
    local family="$1" line
    ip "-$family" rule show 2>/dev/null |
        grep -E "ipproto (udp|tcp) dport (53|123) lookup main|uidrange [0-9]+-[0-9]+ lookup main" |
        while read -r line; do
            # shellcheck disable=SC2086
            ip "-$family" rule del pref ${line%%:*} ${line#*:} protocol "$rule_proto" 2>/dev/null || true
        done
}

# Prints the priority for the routing rules: one below the first rule that
# wg-quick added. wg-quick adds its rules without a priority, so the kernel
# puts them before the first existing rule (e.g. before the Tailscale rules at
# 5210), not always at 32764 and 32765.
rule_pref() {
    local family="$1" pref
    pref=$(ip "-$family" rule show 2>/dev/null |
        awk '/suppress_prefixlength 0|not from all fwmark/ { sub(":", "", $1); print $1 }' |
        sort -n | head -n 1)
    if [ -n "$pref" ] && [ "$pref" -gt 1 ]; then
        echo $((pref - 1))
    else
        echo "$default_rule_pref"
    fi
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

# Sends NTP and/or DNS to the nameservers through the main routing table, so
# that wg-quick does not route them into the tunnel. Not all iproute2 versions
# support these selectors, so a failure only gives a warning.
add_rules() {
    local family="$1" ns proto pref
    pref=$(rule_pref "$family")
    if [ "$allow_ntp" = 1 ]; then
        ip "-$family" rule add pref "$pref" ipproto udp dport 123 lookup main protocol "$rule_proto" ||
            echo "wg-killswitch: unable to add NTP rule (IPv$family)" >&2
    fi
    if [ "$allow_portal" = 1 ] && [ -n "$probe_uid" ]; then
        ip "-$family" rule add pref "$pref" uidrange "$probe_uid-$probe_uid" lookup main protocol "$rule_proto" ||
            echo "wg-killswitch: unable to add captive portal probe rule (IPv$family)" >&2
    fi
    if [ "$allow_dns" = 1 ]; then
        for ns in $(nameservers "$family"); do
            for proto in udp tcp; do
                ip "-$family" rule add pref "$pref" to "$ns" ipproto "$proto" dport 53 lookup main protocol "$rule_proto" ||
                    echo "wg-killswitch: unable to add DNS rule for $ns" >&2
            done
        done
    fi
}

# Adds the exceptions for NTP and DNS to a chain
add_exceptions() {
    local cmd="$1" family="$2" ns proto
    if [ "$allow_ntp" = 1 ]; then
        "$cmd" -A "$3" -p udp --dport 123 -j RETURN
    fi
    # a failure must not stop the kill switch before its REJECT rule
    if [ "$allow_portal" = 1 ] && [ -n "$probe_uid" ]; then
        "$cmd" -A "$3" -m owner --uid-owner "$probe_uid" -p tcp --dport 80 -j RETURN ||
            echo "wg-killswitch: unable to add the captive portal probe exception" >&2
    fi
    if [ "$allow_dns" = 1 ]; then
        for ns in $(nameservers "$family"); do
            for proto in udp tcp; do
                "$cmd" -A "$3" -d "$ns" -p "$proto" --dport 53 -j RETURN
            done
        done
    fi
}

# Prints the subnet of the access point interface of hostapd.conf, as it is
# when the tunnel starts, for example 10.3.141.1/24
current_ap_subnet() {
    local ap
    ap="$(sed -n 's/^interface=\([A-Za-z0-9_.-]\{1,15\}\)$/\1/p' "$hostapd_conf" 2>/dev/null | head -n 1)"
    [ -n "$ap" ] || return 0
    ip -4 -o addr show dev "$ap" 2>/dev/null | awk '{ print $4 }' | grep -E '^([0-9]{1,3}\.){3}[0-9]{1,3}/[0-9]{1,2}$' || true
}

up() {
    local fwmark net ap_nets

    set -o errexit
    cleanup "$iface"
    fwmark="$(wg show "$iface" fwmark 2>/dev/null || echo off)"
    # the AP subnet of the command line (older configurations) and the
    # current AP subnet, so that a change of the AP does not break the rules
    ap_nets="$(printf '%s\n' "$ap_subnet" | grep -E '^([0-9]{1,3}\.){3}[0-9]{1,3}(/[0-9]{1,2})?$' || true) $(current_ap_subnet || true)"

    # IPv4
    iptables -N "$chain4"
    iptables -A "$chain4" -o "$iface" -j RETURN
    iptables -A "$chain4" -o lo -j RETURN
    if [ "$fwmark" != "off" ]; then
        iptables -A "$chain4" -m mark --mark "$fwmark" -j RETURN
    fi
    iptables -A "$chain4" -m addrtype --dst-type LOCAL,BROADCAST,MULTICAST -j RETURN
    for net in 10.0.0.0/8 172.16.0.0/12 192.168.0.0/16 169.254.0.0/16 $ap_nets; do
        # the AP subnet can be in the argument and in hostapd.conf
        iptables -C "$chain4" -d "$net" -j RETURN 2>/dev/null ||
            iptables -A "$chain4" -d "$net" -j RETURN
    done
    iptables -A "$chain4" -p udp --sport 67:68 --dport 67:68 -j RETURN
    add_exceptions iptables 4 "$chain4"
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
    add_exceptions ip6tables 6 "$chain6"
    ip6tables -A "$chain6" -j REJECT
    ip6tables -I OUTPUT -j "$chain6"
    set +o errexit

    add_rules 4
    add_rules 6
}

# Replaces the kill switch rules of older RaspAP versions in a WireGuard
# configuration with calls to this script. NTP stays outside the tunnel, as
# for a new configuration. Other lines do not change. The original file is
# kept as <name>.killswitch.bak. The name does not contain ".conf", so the
# WireGuard page does not show the backup as a configuration.
migrate() {
    local conf backup legacy_up legacy_down
    legacy_up='^PostUp = iptables -I OUTPUT ! -o %i -m mark ! --mark \$\(wg show %i fwmark\) -m addrtype ! --dst-type LOCAL ! -d ([^ ]+) -j REJECT$'
    legacy_down='^PreDown = iptables -D OUTPUT ! -o %i -m mark ! --mark \$\(wg show %i fwmark\) -m addrtype ! --dst-type LOCAL ! -d ([^ ]+) -j REJECT$'
    for conf in "$@"; do
        if [ ! -f "$conf" ] || [ -L "$conf" ]; then
            continue
        fi
        if ! grep -Eq "$legacy_up" "$conf"; then
            continue
        fi
        backup="${conf%.conf}.killswitch.bak"
        cp -a "$conf" "$backup"
        sed -E -i \
            -e "s|$legacy_up|PostUp = $self up %i \\1 --allow-ntp|" \
            -e "s|$legacy_down|PreDown = $self down %i|" \
            "$conf"
        echo "Migrated kill switch in $conf (backup: $backup)"
    done
}

[ "$#" -ge 2 ] || usage
action="$1"
shift

case "$action" in
    up)
        iface="$1"
        shift
        ap_subnet=""
        allow_ntp=0
        allow_dns=0
        allow_portal=0
        for arg in "$@"; do
            case "$arg" in
                --allow-ntp) allow_ntp=1 ;;
                --allow-dns) allow_dns=1 ;;
                --allow-portal) allow_portal=1 ;;
                -*) usage ;;
                *)
                    # an AP subnet of older configurations; other values are ignored
                    if [[ "$arg" =~ ^([0-9]{1,3}\.){3}[0-9]{1,3}(/[0-9]{1,2})?$ ]]; then
                        ap_subnet="$arg"
                    else
                        echo "wg-killswitch: ignoring argument $arg" >&2
                    fi
                    ;;
            esac
        done
        probe_uid="$(id -u "$probe_user" 2>/dev/null || true)"
        up
        ;;
    down)
        cleanup "$1"
        # end the captive portal mode of the AP clients with the tunnel
        if [ -x "$portal_helper" ]; then
            "$portal_helper" off || true
        fi
        ;;
    migrate)
        migrate "$@"
        ;;
    *)
        usage
        ;;
esac
