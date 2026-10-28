#!/bin/bash
#
# RaspAP captive portal helper
#
# Description: Lets the clients of the access point log in to a captive portal
# of the uplink (for example a hotel network) while the WireGuard tunnel and the
# kill switch are active. A systemd timer runs "check" every minute:
# - The script pings two public servers through the tunnel. When they do not
#   answer, it checks the uplink for a captive portal. The probe runs as the
#   user raspap-probe, which is the only user that the kill switch lets out of
#   the tunnel (TCP port 80). Only a redirect, or a login page instead of the
#   expected answer, counts as a portal.
# - "Portal mode" lets the HTTP (TCP port 80) and the DNS requests of the AP
#   clients go to the uplink. The clients then show the login page of the
#   portal themselves. HTTPS and other traffic stay blocked. During portal
#   mode, the DNS names of the uplink domain (DHCP option 15) also go to the
#   DNS server of the uplink, so that a login page with an internal name loads.
# - By default, portal mode starts only when the user clicks the button in the
#   web interface ("open"). It then ends after 5 minutes. With the kill switch
#   option --portal-auto, portal mode starts when the script finds a portal.
# - Portal mode ends when the tunnel works again, when the uplink is free
#   (after the login), or with "close".
# The kill switch option --allow-portal turns this on. Without it, "check"
# removes all changes of this script. "off" removes all changes, also at boot.
# Installed as /usr/local/sbin/raspap-captive-portal.sh.
# License: GNU General Public License v3.0
#
# You are not obligated to bundle the LICENSE file with your RaspAP projects as long
# as you leave these references intact in the header comments of your source files.
#
# Usage: raspap-captive-portal.sh check|status|open|close|off

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
# duration of portal mode after "open" (s)
readonly open_window=300

log() {
    logger -t raspap-captive-portal "$*" 2>/dev/null || true
}

usage() {
    echo "Usage: $0 check|status|open|close|off" >&2
    exit 2
}

# The kill switch of the active tunnel has the option (--allow-portal)
killswitch_option() {
    grep -Eq '^[[:space:]]*PostUp[[:space:]]*=.*wg-killswitch\.sh[[:space:]]+up([[:space:]].*)?[[:space:]]'"$1"'([[:space:]]|$)' "$wg_conf" 2>/dev/null
}

enabled() {
    systemctl is-active --quiet "wg-quick@$wg_iface.service" && killswitch_option --allow-portal
}

# portal mode starts without a click of the user (--portal-auto)
automatic() {
    killswitch_option --portal-auto
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
    out="${out,,}"
    valid_domain "$out" && not_public_suffix "$out" && echo "$out"
}

# A network must not send a public suffix (for example "com" or "co.uk") to
# its DNS server. psl uses the Public Suffix List (package psl, installed by
# RaspAP). Without psl, no domain is accepted.
not_public_suffix() {
    command -v psl > /dev/null || return 1
    [ "$(psl --is-public-suffix "$1" 2>/dev/null)" = "$1: 0" ]
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
    elif [ "$status" = "200" ]; then
        # a login page instead of the expected answer: a portal without a redirect
        echo "portal -"
    else
        # no answer, or an error (for example 403 from a filter): not a portal
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

# Restarts dnsmasq if it runs. --no-block: "off" also runs at boot in
# raspap-captive-portal-cleanup.service, which starts before dnsmasq. A
# restart that waits for dnsmasq would then wait for itself.
restart_dnsmasq() {
    systemctl try-restart --no-block dnsmasq.service || true
}

# Writes the dnsmasq file and restarts dnsmasq when the content changes.
# $1: uplink interface, $2: "portal" for portal mode, $3: portal URL or "-"
write_dns() {
    local up="$1" mode="${2:-}" url="${3:--}" domain host ns content=""
    # only during portal mode: outside the tunnel, the DNS of the uplink is
    # necessary only for the login
    [ "$mode" = "portal" ] && domain="$(uplink_domain "$up" || true)" || domain=""
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
        restart_dnsmasq
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

# $1: state, $2: uplink, $3: portal URL or "-", $4: end of portal mode
# (epoch, 0 = no end)
write_state() {
    mkdir -p "$state_dir"
    chmod 755 "$state_dir"
    printf 'state=%s\nsince=%s\nuplink=%s\nurl=%s\nuntil=%s\nauto=%s\n' "$1" "$(date +%s)" "${2:-}" "${3:--}" "${4:-0}" \
        "$(automatic && echo 1 || echo 0)" > "$state_file.tmp" &&
        chmod 644 "$state_file.tmp" && mv -f "$state_file.tmp" "$state_file"
}

current_state() {
    sed -n 's/^state=\([a-z]*\)$/\1/p' "$state_file" 2>/dev/null | head -n 1
}

# a field of the state file: only digits, or a URL that matches the probe pattern
state_field() {
    case "$1" in
        until) sed -n 's/^until=\([0-9]\{1,12\}\)$/\1/p' "$state_file" 2>/dev/null | head -n 1 ;;
        url) sed -n 's/^url=\(https\{0,1\}:\/\/[A-Za-z0-9.-]*[^[:space:]]*\)$/\1/p' "$state_file" 2>/dev/null | head -n 1 ;;
    esac
}

# Starts portal mode. $1: AP interface, $2: uplink, $3: URL or "-", $4: end (epoch, 0 = no end)
portal_on() {
    portal_rules_on "$1" "$2"
    write_dns "$2" portal "$3"
    write_state portal "$2" "$3" "$4"
}

# Ends portal mode. $1: uplink, $2: the new state, $3: reason for the log
portal_end() {
    local url="-"
    log "portal mode off: $3"
    portal_rules_off
    write_dns "$1"
    # the portal is still there: keep its URL for the link in the warning
    [ "$2" = "detected" ] && url="$(state_field url)"
    write_state "$2" "$1" "${url:--}"
}

off() {
    portal_rules_off
    if [ -f "$dns_conf" ]; then
        rm -f "$dns_conf"
        restart_dnsmasq
    fi
    rm -f "$state_file"
}

check() {
    local ap up result url state until now
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
    now="$(date +%s)"

    if [ "$state" = "portal" ]; then
        until="$(state_field until)"
        if tunnel_works; then
            portal_end "$up" free "the tunnel works again"
            return 0
        fi
        if [ "${until:-0}" -gt 0 ] && [ "$now" -ge "$until" ]; then
            portal_end "$up" detected "the time for the login is over"
            return 0
        fi
    elif tunnel_works; then
        write_dns "$up"
        [ "$state" = "free" ] || write_state free "$up"
        return 0
    fi

    result="$(probe)"
    case "$result" in
        free)
            if [ "$state" = "portal" ]; then
                portal_end "$up" free "the uplink $up is free again"
            else
                write_dns "$up"
                write_state free "$up"
            fi
            ;;
        portal*)
            url="${result#portal }"
            if [ "$state" = "portal" ]; then
                # keep the end of portal mode
                write_dns "$up" portal "$url"
                write_state portal "$up" "$url" "$(state_field until)"
            elif automatic; then
                log "captive portal on $up ($url), portal mode on"
                portal_on "$ap" "$up" "$url" 0
            else
                [ "$state" = "detected" ] || log "captive portal on $up ($url), waiting for the user"
                write_dns "$up"
                write_state detected "$up" "$url"
            fi
            ;;
        *)
            # no answer, or not a portal: no change
            ;;
    esac
}

# The user clicked "open" in the web interface: portal mode for $open_window s
open() {
    local ap up url
    enabled || { echo "captive portal login is not enabled" >&2; return 1; }
    ap="$(ap_iface)"
    up="$(uplink_iface || true)"
    if [ -z "$ap" ] || [ -z "$up" ]; then
        echo "no access point or no uplink" >&2
        return 1
    fi
    url="$(state_field url)"
    log "portal mode on for $open_window s (user)"
    portal_on "$ap" "$up" "${url:--}" $(($(date +%s) + open_window))
}

# The user clicked "close" in the web interface
close() {
    local up
    [ "$(current_state)" = "portal" ] || return 0
    up="$(uplink_iface || true)"
    portal_end "$up" detected "closed by the user"
}

[ "$(id -u)" = 0 ] || { echo "$0: must run as root" >&2; exit 1; }
[ "$#" -eq 1 ] || usage
mkdir -p "$state_dir"
exec 9> "$lock_file"
flock -w 30 9 || exit 1

case "$1" in
    check) check ;;
    status) cat "$state_file" 2>/dev/null || echo "state=off" ;;
    open) open ;;
    close) close ;;
    off) off ;;
    *) usage ;;
esac
