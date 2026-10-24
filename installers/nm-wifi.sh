#!/bin/bash
#
# RaspAP NetworkManager WiFi client helper
#
# Description: Lets the RaspAP web interface manage the WiFi client connections
# when NetworkManager manages the client interface (for example Raspberry Pi OS
# Bookworm and Trixie). www-data runs this script with sudo. The script accepts
# only the actions below and validates each argument, so that the web interface
# can not change other NetworkManager connections. Secrets come on stdin, never
# on the command line.
# Installed as /usr/local/sbin/raspap-nm-wifi.sh.
# License: GNU General Public License v3.0
#
# You are not obligated to bundle the LICENSE file with your RaspAP projects as long
# as you leave these references intact in the header comments of your source files.
#
# Usage: raspap-nm-wifi.sh status <interface>
#        raspap-nm-wifi.sh list <interface>
#        raspap-nm-wifi.sh sync <interface>    (networks on stdin, see sync())
#        raspap-nm-wifi.sh up <interface> <connection-uuid>
#        raspap-nm-wifi.sh disconnect <interface>

set -o nounset
set -o pipefail

readonly conn_dir="/etc/NetworkManager/system-connections"
readonly prefix="raspap-wifi-"
readonly comment="# Managed by RaspAP. Changes in the RaspAP web interface replace this file."
# NetworkManager removes comments when it writes a keyfile again (for example
# after "nmcli connection modify"), but it keeps the keys of the [user]
# section. Thus this key, not the comment, marks the files of this script.
readonly marker_key="org.raspap.managed"
readonly hostapd_conf="/etc/hostapd/hostapd.conf"
readonly max_networks=64

die() {
    echo "raspap-nm-wifi: $2" >&2
    exit "$1"
}

usage() {
    die 2 "usage: $0 status|list|sync|disconnect <interface>, or up <interface> <uuid>"
}

# A client interface: a valid name of an existing wireless interface that is
# not the access point interface
check_iface() {
    local iface="$1"
    [[ "$iface" =~ ^[A-Za-z0-9_.-]{1,15}$ ]] || die 2 "invalid interface name"
    [ -d "/sys/class/net/$iface/wireless" ] || [ -e "/sys/class/net/$iface/phy80211" ] ||
        die 2 "$iface is not a wireless interface"
    if [ -f "$hostapd_conf" ] && grep -Fqx "interface=$iface" "$hostapd_conf"; then
        die 2 "$iface is the access point interface"
    fi
    if iw dev "$iface" info 2>/dev/null | grep -q '^\s*type AP$'; then
        die 2 "$iface is in access point mode"
    fi
}

# NetworkManager runs and manages the interface. When it does not (for
# example after a change back to dhcpcd), RaspAP uses wpa_supplicant.
nm_manages() {
    local state
    systemctl is-active --quiet NetworkManager.service || return 1
    command -v nmcli > /dev/null || return 1
    state="$(nmcli -g GENERAL.STATE device show "$1" 2>/dev/null)" || return 1
    [ -n "$state" ] && [[ "$state" != *unmanaged* ]]
}

require_managed() {
    nm_manages "$1" || die 3 "NetworkManager does not manage $1"
}

# Prints the bytes of a string as lowercase hex
to_hex() {
    printf '%s' "$1" | od -An -v -tx1 | tr -d ' \n'
}

# Prints the WiFi client connections that can use the interface, one per line:
# uuid <TAB> managed-by-raspap (1/0) <TAB> ssid-hex <TAB> active (1/0)
#      <TAB> autoconnect (1/0) <TAB> priority
# Access point, mesh and ad-hoc connections, and connections bound to another
# interface, are not shown.
list() {
    local iface="$1" uuid type active auto prio file mode ifname ssid ours
    # FILENAME is last, so that a colon in a path does not break the fields
    nmcli -t -e no -f UUID,TYPE,ACTIVE,AUTOCONNECT,AUTOCONNECT-PRIORITY,FILENAME connection show 2>/dev/null |
    while IFS=: read -r uuid type active auto prio file; do
        [ "$type" = "802-11-wireless" ] || continue
        [[ "$uuid" =~ ^[0-9a-f-]{36}$ ]] || continue
        mode="$(nmcli -e no -g 802-11-wireless.mode connection show uuid "$uuid" 2>/dev/null)"
        case "$mode" in ""|infrastructure) ;; *) continue ;; esac
        ifname="$(nmcli -e no -g connection.interface-name connection show uuid "$uuid" 2>/dev/null)"
        [ -z "$ifname" ] || [ "$ifname" = "$iface" ] || continue
        ssid="$(nmcli -e no -g 802-11-wireless.ssid connection show uuid "$uuid" 2>/dev/null)"
        [ -n "$ssid" ] || continue
        ours=0
        if [[ "$file" == "$conn_dir/$prefix"*.nmconnection ]] && is_ours "$file"; then
            ours=1
        fi
        [[ "$prio" =~ ^-?[0-9]+$ ]] || prio=0
        printf '%s\t%s\t%s\t%s\t%s\t%s\n' "$uuid" "$ours" "$(to_hex "$ssid")" \
            "$([ "$active" = yes ] && echo 1 || echo 0)" "$([ "$auto" = yes ] && echo 1 || echo 0)" "$prio"
    done
}

# A keyfile that this script wrote: a regular file (not a symlink) owned by
# root in the connection directory, with the marker key in the [user] section
is_ours() {
    local file="$1"
    [ -f "$file" ] && [ ! -L "$file" ] && [ "$(stat -c %u "$file")" = 0 ] &&
        awk -v key="$marker_key=1" '
            /^\[/ { section = $0; next }
            section == "[user]" && $0 == key { found = 1 }
            END { exit !found }' "$file"
}

# Converts hex to the byte list of a NetworkManager keyfile, e.g. "52;61;"
hex_to_bytelist() {
    local hex="$1" out="" i
    for ((i = 0; i < ${#hex}; i += 2)); do
        out+="$((16#${hex:i:2}));"
    done
    printf '%s' "$out"
}

# Replaces the RaspAP connections with the networks on stdin. One network per
# line, fields separated by a tab:
#   ssid-hex <TAB> open|psk <TAB> psk-hex|- <TAB> priority
# ssid-hex: the SSID bytes as hex (1 to 32 bytes)
# psk-hex:  the 256-bit PSK as 64 hex digits (from wpa_passphrase), or "-" for open
# priority: an integer from -999 to 999
# Other NetworkManager connections do not change.
sync() {
    local iface="$1" line ssid sec psk prio name file tmp uuid count=0 old keep i
    local -a ssids=() secs=() psks=() prios=()
    declare -A seen=() names=()
    umask 077

    # validate all lines first, so that a bad line changes nothing
    while IFS= read -r line || [ -n "$line" ]; do
        [ -n "$line" ] || continue
        count=$((count + 1))
        [ "$count" -le "$max_networks" ] || die 2 "too many networks"
        IFS=$'\t' read -r ssid sec psk prio <<< "$line"
        [[ "$ssid" =~ ^([0-9a-f]{2}){1,32}$ ]] || die 2 "invalid SSID on line $count"
        case "$sec" in
            open) [ "$psk" = "-" ] || die 2 "open network with a PSK on line $count" ;;
            psk) [[ "$psk" =~ ^[0-9a-f]{64}$ ]] || die 2 "invalid PSK on line $count" ;;
            *) die 2 "invalid security on line $count" ;;
        esac
        [[ "$prio" =~ ^-?[0-9]{1,3}$ ]] || die 2 "invalid priority on line $count"
        [ -z "${seen[$ssid]:-}" ] || continue
        seen[$ssid]=1
        ssids+=("$ssid"); secs+=("$sec"); psks+=("$psk"); prios+=("$prio")
    done

    for i in "${!ssids[@]}"; do
        ssid="${ssids[$i]}"; sec="${secs[$i]}"; psk="${psks[$i]}"; prio="${prios[$i]}"
        name="$prefix$(printf '%s' "$ssid" | sha256sum | cut -c1-12)"
        file="$conn_dir/$name.nmconnection"
        names[$name]=1
        if [ -e "$file" ] || [ -L "$file" ]; then
            is_ours "$file" || { echo "raspap-nm-wifi: $file is not a RaspAP file, not changed" >&2; continue; }
            uuid="$(sed -n 's/^uuid=\([0-9a-f-]\{36\}\)$/\1/p' "$file" | head -n 1)"
        else
            uuid=""
        fi
        [ -n "$uuid" ] || uuid="$(cat /proc/sys/kernel/random/uuid)"

        tmp="$(mktemp "$conn_dir/.raspap.XXXXXX")" || die 1 "unable to create a file in $conn_dir"
        {
            echo "$comment"
            echo "[connection]"
            echo "id=$name"
            echo "uuid=$uuid"
            echo "type=wifi"
            echo "interface-name=$iface"
            echo "autoconnect=true"
            echo "autoconnect-priority=$prio"
            echo
            echo "[wifi]"
            echo "mode=infrastructure"
            echo "ssid=$(hex_to_bytelist "$ssid")"
            if [ "$sec" = "psk" ]; then
                echo
                echo "[wifi-security]"
                echo "key-mgmt=wpa-psk"
                echo "psk=$psk"
            fi
            echo
            echo "[ipv4]"
            echo "method=auto"
            echo
            echo "[ipv6]"
            echo "method=auto"
            echo
            echo "[user]"
            echo "$marker_key=1"
        } > "$tmp" || { rm -f "$tmp"; die 1 "unable to write $tmp"; }
        chmod 600 "$tmp"
        mv -f "$tmp" "$file" || { rm -f "$tmp"; die 1 "unable to write $file"; }
    done

    # remove the RaspAP connections of networks that are no longer configured
    for old in "$conn_dir/$prefix"*.nmconnection; do
        [ -e "$old" ] || continue
        keep="$(basename "$old" .nmconnection)"
        [ -n "${names[$keep]:-}" ] && continue
        is_ours "$old" && rm -f "$old"
    done

    nmcli connection reload || die 1 "nmcli connection reload failed"
}

# Activates a WiFi client connection that can use the interface
up() {
    local iface="$1" uuid="$2"
    [[ "$uuid" =~ ^[0-9a-f]{8}-([0-9a-f]{4}-){3}[0-9a-f]{12}$ ]] || die 2 "invalid connection UUID"
    list "$iface" | cut -f1 | grep -qx "$uuid" || die 2 "no WiFi client connection $uuid for $iface"
    nmcli --wait 30 connection up uuid "$uuid" ifname "$iface"
}

[ "$(id -u)" = 0 ] || die 1 "must run as root"
[ "$#" -ge 2 ] || usage
action="$1"
iface="$2"
check_iface "$iface"

case "$action" in
    status)
        [ "$#" -eq 2 ] || usage
        if nm_manages "$iface"; then echo "managed"; else echo "unmanaged"; fi
        ;;
    list)
        [ "$#" -eq 2 ] || usage
        require_managed "$iface"
        list "$iface"
        ;;
    sync)
        [ "$#" -eq 2 ] || usage
        require_managed "$iface"
        [ -d "$conn_dir" ] || die 1 "$conn_dir does not exist"
        sync "$iface"
        ;;
    up)
        [ "$#" -eq 3 ] || usage
        require_managed "$iface"
        up "$iface" "$3"
        ;;
    disconnect)
        [ "$#" -eq 2 ] || usage
        require_managed "$iface"
        nmcli device disconnect "$iface"
        ;;
    *)
        usage
        ;;
esac
