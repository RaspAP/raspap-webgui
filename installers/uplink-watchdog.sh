#!/bin/bash
#
# RaspAP uplink watchdog
#
# Description: Connects the WiFi client uplink again when the link stays up
# but the gateway does not answer. A WiFi network can stop the traffic of a
# client without a disconnect (for example after an expired hotel session).
# Then wpa_supplicant and NetworkManager see no problem, and the uplink stays
# dead until a manual reconnect. A systemd timer runs "check" every 30 s:
# - The script finds the uplink: the interface of the default route in the
#   main table. It skips the access point interface, and interfaces that are
#   not a connected WiFi client.
# - It pings the gateway through the uplink. If there is no reply, it reads
#   the ARP state of the gateway. Only an ARP failure counts as a failure, so
#   a gateway that blocks ping, a captive portal or a stopped VPN tunnel is
#   not a failure.
# - After 3 failures in a series, it connects the uplink again: with
#   NetworkManager, "nmcli connection up" of the active connection, otherwise
#   "wpa_cli reassociate" and a DHCP renew. It does this at most one time in
#   10 minutes.
# The timer is off by default. "enable" and "disable" turn it on and off.
# Installed as /usr/local/sbin/raspap-uplink-watchdog.sh.
# License: GNU General Public License v3.0
#
# You are not obligated to bundle the LICENSE file with your RaspAP projects as long
# as you leave these references intact in the header comments of your source files.
#
# Usage: raspap-uplink-watchdog.sh check|status|enable|disable

set -o nounset
set -o pipefail

readonly hostapd_conf="/etc/hostapd/hostapd.conf"
readonly state_dir="/run/raspap"
readonly state_file="$state_dir/uplink-watchdog"
readonly lock_file="$state_dir/uplink-watchdog.lock"
readonly timer="raspap-uplink-watchdog.timer"
# failures in a series before a reconnect
readonly max_failures=3
# minimum time between two reconnects (s)
readonly min_interval=600

# state, read from and written to $state_file
failures=0
last_action=0
last_result=""
uplink=""
gateway=""

log() {
    logger -t raspap-uplink-watchdog "$*" 2>/dev/null || true
}

usage() {
    echo "Usage: $0 check|status|enable|disable" >&2
    exit 2
}

valid_iface() {
    [[ "$1" =~ ^[A-Za-z0-9_.-]{1,15}$ ]]
}

valid_ipv4() {
    [[ "$1" =~ ^([0-9]{1,3}\.){3}[0-9]{1,3}$ ]]
}

# The access point interface, from hostapd.conf
ap_iface() {
    sed -n 's/^interface=\([A-Za-z0-9_.-]\{1,15\}\)$/\1/p' "$hostapd_conf" 2>/dev/null | head -n 1
}

# Prints "<interface> <gateway>" of the first default route in the main table
default_route() {
    ip -4 route show table main default 2>/dev/null | awk '
        {
            dev = ""; via = ""
            for (i = 1; i < NF; i++) {
                if ($i == "dev") dev = $(i + 1)
                if ($i == "via") via = $(i + 1)
            }
            if (dev != "" && via != "") { print dev, via; exit }
        }'
}

# The interface is a WiFi client that is connected to an access point
wifi_connected() {
    [ -d "/sys/class/net/$1/wireless" ] || return 1
    iw dev "$1" link 2>/dev/null | grep -q '^Connected to '
}

# The gateway answers on the link. A reply to ping is enough. Without a reply,
# the ARP state decides, because some gateways do not answer ping.
gateway_alive() {
    local dev="$1" gw="$2" state
    ping -n -q -c 1 -W 2 -I "$dev" "$gw" > /dev/null 2>&1 && return 0
    state="$(ip neigh show "$gw" dev "$dev" 2>/dev/null | awk '{ print $NF; exit }')"
    case "$state" in
        INCOMPLETE|FAILED|"") return 1 ;;
        *) return 0 ;;
    esac
}

# NetworkManager manages the interface
nm_managed() {
    local state
    command -v nmcli > /dev/null || return 1
    state="$(nmcli -t -f DEVICE,STATE device 2>/dev/null | awk -F: -v d="$1" '$1 == d { print $2; exit }')"
    [ -n "$state" ] && [ "$state" != "unmanaged" ]
}

# Connects the interface again. Returns 0 on success.
reconnect() {
    local dev="$1" uuid
    if nm_managed "$dev"; then
        uuid="$(nmcli -t -f UUID,DEVICE connection show --active 2>/dev/null |
            awk -F: -v d="$dev" '$2 == d { print $1; exit }')"
        [[ "$uuid" =~ ^[0-9a-fA-F-]{36}$ ]] || return 1
        nmcli --wait 45 connection up uuid "$uuid" > /dev/null 2>&1
        return
    fi
    command -v wpa_cli > /dev/null || return 1
    wpa_cli -i "$dev" reassociate 2>/dev/null | grep -q '^OK' || return 1
    if command -v dhcpcd > /dev/null; then
        sleep 5
        dhcpcd -n "$dev" > /dev/null 2>&1 || true
    fi
    return 0
}

# Reads the state file. Only known keys with valid values are used, so a
# changed file cannot inject commands.
read_state() {
    local key value
    [ -f "$state_file" ] || return 0
    while IFS='=' read -r key value; do
        case "$key" in
            failures) [[ "$value" =~ ^[0-9]{1,6}$ ]] && failures="$value" ;;
            last_action) [[ "$value" =~ ^[0-9]{1,12}$ ]] && last_action="$value" ;;
            last_result) [[ "$value" =~ ^[a-z-]{1,20}$ ]] && last_result="$value" ;;
        esac
    done < "$state_file"
}

write_state() {
    local tmp
    install -d -m 755 "$state_dir" || return 1
    tmp="$(mktemp "$state_dir/uplink-watchdog.XXXXXX")" || return 1
    printf 'failures=%s\nlast_action=%s\nlast_result=%s\nuplink=%s\ngateway=%s\n' \
        "$failures" "$last_action" "$last_result" "$uplink" "$gateway" > "$tmp" &&
        chmod 644 "$tmp" && mv -f "$tmp" "$state_file" || { rm -f "$tmp"; return 1; }
}

check() {
    local route dev gw now
    read_state
    route="$(default_route)"
    dev="${route%% *}"
    gw="${route#* }"
    # nothing to do without a WiFi client uplink: no default route, the AP
    # interface, a wired uplink, or a WiFi client that is not connected
    # (wpa_supplicant or NetworkManager then connect it themselves)
    if [ -z "$route" ] || ! valid_iface "$dev" || ! valid_ipv4 "$gw" ||
        [ "$dev" = "$(ap_iface)" ] || ! wifi_connected "$dev"; then
        failures=0
        uplink=""
        gateway=""
        write_state
        return 0
    fi
    uplink="$dev"
    gateway="$gw"

    if gateway_alive "$dev" "$gw"; then
        if [ "$failures" -gt 0 ]; then
            log "gateway $gw on $dev answers again"
        fi
        failures=0
        write_state
        return 0
    fi

    [ "$failures" -lt 999 ] && failures=$((failures + 1))
    if [ "$failures" -le "$max_failures" ]; then
        log "gateway $gw on $dev does not answer ($failures/$max_failures)"
    fi
    if [ "$failures" -lt "$max_failures" ]; then
        write_state
        return 0
    fi

    now="$(date +%s)"
    if [ $((now - last_action)) -lt "$min_interval" ]; then
        if [ "$failures" -eq "$max_failures" ]; then
            log "waiting: the last reconnect was less than $min_interval s ago"
        fi
        write_state
        return 0
    fi

    log "connecting $dev again"
    last_action="$now"
    if reconnect "$dev"; then
        last_result="reconnected"
        log "connected $dev again"
    else
        last_result="failed"
        log "could not connect $dev again"
    fi
    failures=0
    write_state
}

status() {
    local enabled=0
    systemctl is-enabled --quiet "$timer" 2>/dev/null && enabled=1
    read_state
    if [ -f "$state_file" ]; then
        uplink="$(sed -n 's/^uplink=\([A-Za-z0-9_.-]\{1,15\}\)$/\1/p' "$state_file" | head -n 1)"
        gateway="$(sed -n 's/^gateway=\(\([0-9]\{1,3\}\.\)\{3\}[0-9]\{1,3\}\)$/\1/p' "$state_file" | head -n 1)"
    fi
    printf 'enabled=%s\nfailures=%s\nlast_action=%s\nlast_result=%s\nuplink=%s\ngateway=%s\n' \
        "$enabled" "$failures" "$last_action" "$last_result" "$uplink" "$gateway"
}

[ "$#" -eq 1 ] || usage

case "$1" in
    check)
        install -d -m 755 "$state_dir" || exit 1
        exec 9> "$lock_file" || exit 1
        flock -n 9 || exit 0
        check
        ;;
    status)
        status
        ;;
    enable)
        systemctl enable --now "$timer" > /dev/null 2>&1 || exit 1
        log "enabled"
        ;;
    disable)
        systemctl disable --now "$timer" > /dev/null 2>&1 || exit 1
        rm -f "$state_file"
        log "disabled"
        ;;
    *)
        usage
        ;;
esac
