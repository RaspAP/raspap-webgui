#!/bin/bash
#
# RaspAP captive portal helper
#
# Description: Lets the clients of the access point log in to a captive portal
# of the uplink (for example a hotel network) while the WireGuard tunnel and the
# kill switch are active. A systemd timer runs "check" every minute:
# - When the tunnel is up, the DNS names of the uplink domain (DHCP option 15)
#   go to the DNS server of the uplink, so that a portal page with an internal
#   name can load.
# - The script pings two public servers through the tunnel. When they do not
#   answer, it checks the uplink for a captive portal. The probe runs as the user raspap-probe, which
#   is the only user that the kill switch lets out of the tunnel (TCP port 80).
# - When it finds a portal, "portal mode" lets the HTTP (TCP port 80) and the
#   DNS requests of the AP clients go to the uplink. The clients then show the
#   login page of the portal themselves. HTTPS and other traffic stay blocked.
# - When the uplink is free again (after the login), portal mode ends, and the
#   tunnel connects again.
# The kill switch option --allow-portal turns this on. Without it, "check"
# removes all changes of this script.
# Installed as /usr/local/sbin/raspap-captive-portal.sh.
# License: GNU General Public License v3.0
#
# You are not obligated to bundle the LICENSE file with your RaspAP projects as long
# as you leave these references intact in the header comments of your source files.
#
# Usage: raspap-captive-portal.sh check|status|off

set -o nounset
set -o pipefail

readonly wg_iface="wg0"
readonly wg_conf="/etc/wireguard/wg0.conf"
readonly hostapd_conf="/etc/hostapd/hostapd.conf"
readonly state_dir="/run/raspap"
readonly state_file="$state_dir/captive-portal"
readonly lock_file="$state_dir/captive-portal.lock"
readonly dns_conf="/etc/dnsmasq.d/096-raspap-portal.conf"
readonly probe_user="raspap-probe"
readonly probe_url="http://detectportal.firefox.com/success.txt"
readonly probe_body="success"
# servers that answer pings, tried through the tunnel
readonly ping_targets="1.1.1.1 9.9.9.9"
# protocol tag and comment of the rules of this script
readonly rule_proto="251"
readonly comment="raspap-portal"
readonly default_rule_pref="32700"

log() {
    logger -t raspap-captive-portal "$*" 2>/dev/null || true
}

usage() {
    echo "Usage: $0 check|status|off" >&2
    exit 2
}

# The kill switch of the active tunnel has the option --allow-portal
enabled() {
    systemctl is-active --quiet "wg-quick@$wg_iface.service" || return 1
    grep -Eq '^[[:space:]]*PostUp[[:space:]]*=.*wg-killswitch\.sh[[:space:]]+up([[:space:]].*)?[[:space:]]--allow-portal([[:space:]]|$)' "$wg_conf" 2>/dev/null
}

# The access point interface, from hostapd.conf
ap_iface() {
    sed -n 's/^interface=\([A-Za-z0-9_.-]\{1,15\}\)$/\1/p' "$hostapd_conf" 2>/dev/null | head -n 1
}

# The uplink: the interface of the default route in the main table
uplink_iface() {
    local dev
    dev="$(ip -4 route show table main default 2>/dev/null |
        awk '{ for (i = 1; i < NF; i++) if ($i == "dev") { print $(i + 1); exit } }')"
    [[ "$dev" =~ ^[A-Za-z0-9_.-]{1,15}$ ]] && [ "$dev" != "$(ap_iface)" ] && echo "$dev"
}

# The IPv4 DNS servers of the uplink, from NetworkManager or resolv.conf
uplink_dns() {
    local up="$1" out=""
    if command -v nmcli > /dev/null; then
        out="$(nmcli -g IP4.DNS device show "$up" 2>/dev/null | tr '|' '\n')"
    fi
    if [ -z "$out" ]; then
        out="$(awk '$1 == "nameserver" { print $2 }' /etc/resolv.conf 2>/dev/null)"
    fi
    echo "$out" | tr -d ' ' | grep -E '^([0-9]{1,3}\.){3}[0-9]{1,3}$' | grep -v '^127\.' | head -n 3
}

# The DNS domain of the uplink (DHCP option 15), from NetworkManager or resolv.conf
uplink_domain() {
    local up="$1" out=""
    if command -v nmcli > /dev/null; then
        out="$(nmcli -g IP4.DOMAIN device show "$up" 2>/dev/null | tr '|' '\n' | head -n 1)"
    fi
    if [ -z "$out" ]; then
        out="$(awk '$1 == "search" || $1 == "domain" { print $2; exit }' /etc/resolv.conf 2>/dev/null)"
    fi
    out="${out%.}"
    valid_domain "$out" && echo "$out"
}

valid_domain() {
    [[ "$1" =~ ^([A-Za-z0-9]([A-Za-z0-9-]{0,61}[A-Za-z0-9])?\.)*[A-Za-z0-9]([A-Za-z0-9-]{0,61}[A-Za-z0-9])?$ ]] &&
        [ "${#1}" -le 253 ]
}

# The tunnel works: one of the ping targets answers through the tunnel. The
# kill switch lets these pings pass, and nothing goes outside the tunnel. A
# portal blocks the tunnel at once, also when the last handshake is recent.
tunnel_works() {
    local target
    for target in $ping_targets; do
        ping -n -q -c 2 -W 2 -I "$wg_iface" "$target" > /dev/null 2>&1 && return 0
    done
    return 1
}

# Checks the uplink. Prints "free", "portal <url>" or "unknown".
probe() {
    local out status location
    id -u "$probe_user" > /dev/null 2>&1 || { echo unknown; return; }
    out="$(runuser -u "$probe_user" -- curl -s -i -m 8 --max-filesize 65536 --proto =http \
        "$probe_url" 2>/dev/null | head -c 65536 | tr -d '\r')" || true
    status="$(printf '%s\n' "$out" | awk 'NR == 1 && /^HTTP\// { print $2 }')"
    if [ "$status" = "200" ] && printf '%s' "$out" | grep -q "^$probe_body"; then
        echo free
        return
    fi
    location="$(printf '%s\n' "$out" | sed -n 's/^[Ll]ocation:[[:space:]]*\(.*\)$/\1/p' | head -n 1)"
    if [[ "$status" =~ ^30[1278]$ ]] && [[ "$location" =~ ^https?://[A-Za-z0-9.-]+(:[0-9]{1,5})?(/[^[:space:]\"\'\<\>]*)?$ ]] &&
        [ "${#location}" -le 2048 ]; then
        echo "portal $location"
    elif [ -n "$status" ]; then
        # an answer, but not the expected one: a portal without a redirect
        echo "portal -"
    else
        echo unknown
    fi
}

# Priority for the rules: one below the first rule that wg-quick added
rule_pref() {
    local pref
    pref="$(ip -4 rule show 2>/dev/null |
        awk '/suppress_prefixlength 0|not from all fwmark/ { sub(":", "", $1); print $1 }' | sort -n | head -n 1)"
    if [ -n "$pref" ] && [ "$pref" -gt 1 ]; then echo $((pref - 1)); else echo "$default_rule_pref"; fi
}

# Writes the dnsmasq file and restarts dnsmasq when the content changes.
# $1: uplink interface, $2: "portal" for portal mode, $3: portal URL or "-"
write_dns() {
    local up="$1" mode="${2:-}" url="${3:--}" domain host ns content=""
    domain="$(uplink_domain "$up" || true)"
    for ns in $(uplink_dns "$up"); do
        [ -n "$domain" ] && content+="server=/$domain/$ns"$'\n'
        if [ "$mode" = "portal" ]; then
            content+="server=$ns"$'\n'
            host="$(printf '%s' "$url" | sed -n 's|^https\{0,1\}://\([A-Za-z0-9.-]*\).*|\1|p')"
            if [ -n "$host" ] && valid_domain "$host" && ! [[ "$host" =~ ^[0-9.]+$ ]]; then
                content+="server=/$host/$ns"$'\n'
            fi
        fi
    done
    if [ -n "$content" ]; then
        content="# Added by RaspAP (raspap-captive-portal.sh). Do not change."$'\n'"$content"
    fi
    # both sides without the trailing newlines, so that an equal file is not changed
    if [ "$(printf '%s' "$content")" != "$(cat "$dns_conf" 2>/dev/null)" ]; then
        if [ -n "$content" ]; then
            printf '%s' "$content" > "$dns_conf.tmp" && chmod 644 "$dns_conf.tmp" && mv -f "$dns_conf.tmp" "$dns_conf"
        else
            rm -f "$dns_conf"
        fi
        systemctl try-restart dnsmasq.service
    fi
}

# Lets the HTTP requests of the AP clients go to the uplink
portal_rules_on() {
    local ap="$1" up="$2" pref
    portal_rules_off
    pref="$(rule_pref)"
    ip -4 rule add pref "$pref" iif "$ap" ipproto tcp dport 80 lookup main protocol "$rule_proto"
    iptables -I FORWARD 1 -i "$ap" -o "$up" -p tcp --dport 80 -m comment --comment "$comment" -j ACCEPT
    iptables -I FORWARD 2 -i "$up" -o "$ap" -m conntrack --ctstate RELATED,ESTABLISHED -m comment --comment "$comment" -j ACCEPT
    iptables -t nat -I POSTROUTING 1 -o "$up" -m comment --comment "$comment" -j MASQUERADE
}

# Removes the rules of portal_rules_on(), found by protocol tag and comment
portal_rules_off() {
    local line table
    ip -4 rule show 2>/dev/null | grep -E "iif [^ ]+ ipproto tcp dport 80 lookup main" |
        while read -r line; do
            # shellcheck disable=SC2086
            ip -4 rule del pref ${line%%:*} ${line#*:} protocol "$rule_proto" 2>/dev/null || true
        done
    for table in filter nat; do
        iptables -t "$table" -S 2>/dev/null | grep -F -- "--comment $comment" | sed 's/^-A/-D/' |
            while read -r line; do
                # shellcheck disable=SC2086
                iptables -t "$table" $line 2>/dev/null || true
            done
    done
}

write_state() {
    mkdir -p "$state_dir"
    chmod 755 "$state_dir"
    printf 'state=%s\nsince=%s\nuplink=%s\nurl=%s\n' "$1" "$(date +%s)" "${2:-}" "${3:--}" > "$state_file.tmp" &&
        chmod 644 "$state_file.tmp" && mv -f "$state_file.tmp" "$state_file"
}

current_state() {
    sed -n 's/^state=\([a-z]*\)$/\1/p' "$state_file" 2>/dev/null | head -n 1
}

off() {
    portal_rules_off
    if [ -f "$dns_conf" ]; then
        rm -f "$dns_conf"
        systemctl try-restart dnsmasq.service
    fi
    rm -f "$state_file"
}

check() {
    local ap up result url state
    if ! enabled; then
        [ -f "$state_file" ] || [ -f "$dns_conf" ] && off
        return 0
    fi
    ap="$(ap_iface)"
    up="$(uplink_iface || true)"
    if [ -z "$ap" ] || [ -z "$up" ]; then
        [ "$(current_state)" = "portal" ] && { log "no uplink, portal mode off"; portal_rules_off; }
        write_state none "$up"
        return 0
    fi
    state="$(current_state)"

    if [ "$state" != "portal" ] && tunnel_works; then
        write_dns "$up"
        [ "$state" = "free" ] || write_state free "$up"
        return 0
    fi

    result="$(probe)"
    case "$result" in
        free)
            if [ "$state" = "portal" ]; then
                log "uplink $up is free again, portal mode off"
                portal_rules_off
            fi
            write_dns "$up"
            write_state free "$up"
            ;;
        portal*)
            url="${result#portal }"
            if [ "$state" != "portal" ]; then
                log "captive portal on $up ($url), portal mode on"
                portal_rules_on "$ap" "$up"
            fi
            write_dns "$up" portal "$url"
            write_state portal "$up" "$url"
            ;;
        *)
            # no answer: no change
            ;;
    esac
}

[ "$(id -u)" = 0 ] || { echo "$0: must run as root" >&2; exit 1; }
[ "$#" -eq 1 ] || usage
mkdir -p "$state_dir"
exec 9> "$lock_file"
flock -w 30 9 || exit 1

case "$1" in
    check) check ;;
    status) cat "$state_file" 2>/dev/null || echo "state=off" ;;
    off) off ;;
    *) usage ;;
esac
