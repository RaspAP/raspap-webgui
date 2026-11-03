#!/bin/bash
#
# RaspAP WiFi helper
#
# Description: Runs wpa_cli, wpa_supplicant and iw for the web interface, and
# installs and reads wpa_supplicant.conf. www-data runs this script with sudo.
# The script accepts only the commands below and checks each argument, so
# that the web interface can not give other options to these root programs.
# The new wpa_supplicant.conf comes on stdin, so there is no file in /tmp.
#   wpa <interface> status|scan|scan_results|reconfigure|reassociate|add_network|list_networks
#   wpa <interface> disconnect [<id>]
#   wpa <interface> enable_network|select_network|remove_network <id>
#   wpa <interface> disable_network <id>|all
#   wpa <interface> set_network <id> ssid|psk|key_mgmt <value>
#   start <interface> [force]     start wpa_supplicant, "force" removes the old socket
#   config read|install|create    wpa_supplicant.conf: print it, stdin to it, or a
#                                 minimal file if it does not exist
#   scan <interface>              iw dev <interface> scan
#   txpower <interface> auto|<mBm>
# Installed as /usr/local/sbin/raspap-wifi.sh.
# License: GNU General Public License v3.0
#
# You are not obligated to bundle the LICENSE file with your RaspAP projects as long
# as you leave these references intact in the header comments of your source files.

set -o nounset
set -o pipefail
# SSID lengths are bytes
export LC_ALL=C

readonly wpa_conf="/etc/wpa_supplicant/wpa_supplicant.conf"
readonly ctrl_dir="/var/run/wpa_supplicant"
readonly max_size=65536

die() {
    echo "raspap-wifi: $2" >&2
    exit "$1"
}

usage() {
    die 2 "usage: $0 wpa <interface> <command> ..., start <interface> [force], config read|install|create, scan <interface>, txpower <interface> auto|<mBm>"
}

valid_iface() {
    [[ "$1" =~ ^[A-Za-z0-9][A-Za-z0-9_.-]{0,14}$ ]]
}

valid_id() {
    [[ "$1" =~ ^[0-9]{1,4}$ ]]
}

# The values that WiFiManager gives to "set_network". An SSID is a quoted
# text of up to 32 bytes or up to 32 bytes in hex. A PSK is a quoted
# passphrase of 8 to 63 characters or 64 hex digits.
valid_network_value() {
    local field="$1" value="$2" re_text='^"[^[:cntrl:]]{1,32}"$' re_pass='^"[^[:cntrl:]]{8,63}"$'
    case "$field" in
        ssid) [[ "$value" =~ ^([0-9A-Fa-f]{2}){1,32}$ ]] || [[ "$value" =~ $re_text ]] ;;
        psk) [[ "$value" =~ ^[0-9A-Fa-f]{64}$ ]] || [[ "$value" =~ $re_pass ]] ;;
        key_mgmt) [ "$value" = "NONE" ] ;;
        *) return 1 ;;
    esac
}

# Only the global options and network keys that the web interface writes,
# and some network keys without a file or a program. The control interface
# must be the default: its group can control wpa_supplicant.
check_wpa_conf() {
    awk '
        function trim(s) { sub(/^[[:space:]]+/, "", s); sub(/[[:space:]]+$/, "", s); return s }
        function bad(s) { print s; failed = 1; exit 1 }
        BEGIN {
            n = split("ssid psk key_mgmt scan_ssid priority bssid proto pairwise group ieee80211w disabled id_str", list, " ")
            for (i = 1; i <= n; i++) netkeys[list[i]] = 1
        }
        /^[[:space:]]*(#|$)/ { next }
        {
            line = trim($0)
            if (line == "network={") { if (innet) bad("network"); innet = 1; next }
            if (line == "}") { if (!innet) bad("}"); innet = 0; next }
            if (!index(line, "=")) bad(substr(line, 1, 32))
            key = line; sub(/=.*/, "", key); key = trim(key)
            value = line; sub(/^[^=]*=/, "", value)
            if (innet) {
                if (!(key in netkeys)) bad(key)
            } else if (key == "ctrl_interface") {
                if (value != "DIR=/var/run/wpa_supplicant GROUP=netdev" && value != "/var/run/wpa_supplicant") bad(key)
            } else if (key == "update_config") {
                if (value !~ /^[01]$/) bad(key)
            } else if (key == "country") {
                if (value !~ /^[A-Z][A-Z]$/) bad(key)
            } else {
                bad(key)
            }
        }
        END { if (!failed && innet) { print "network"; exit 1 } }' "$1"
}

# Writes stdin to wpa_supplicant.conf. The temporary file is in the same
# directory and starts with ".". The new file gets the owner and the mode of
# the old file, or root and 600.
install_conf() {
    local dir tmp option
    dir="$(dirname "$wpa_conf")"
    [ -d "$dir" ] || die 1 "no directory $dir"
    tmp="$(mktemp "$dir/.wpa_supplicant.conf.XXXXXX")" || die 1 "mktemp failed"
    trap 'rm -f "$tmp"' EXIT
    chmod 600 "$tmp" || die 1 "can not set the mode of $tmp"
    head -c $((max_size + 1)) > "$tmp" || die 1 "can not write $tmp"
    [ "$(stat -c %s "$tmp")" -le "$max_size" ] || die 2 "the file is too large"
    [ "$(tr -d '\000' < "$tmp" | wc -c)" = "$(stat -c %s "$tmp")" ] || die 2 "the file has a NUL byte"
    if ! option="$(check_wpa_conf "$tmp")"; then
        die 2 "option not allowed in wpa_supplicant.conf: $option"
    fi
    if [ -f "$wpa_conf" ] && [ ! -L "$wpa_conf" ]; then
        chown --reference="$wpa_conf" "$tmp" && chmod --reference="$wpa_conf" "$tmp" || die 1 "can not set the mode of $tmp"
    else
        chown root:root "$tmp" || die 1 "can not set the owner of $tmp"
    fi
    mv -f "$tmp" "$wpa_conf" || die 1 "can not write $wpa_conf"
    trap - EXIT
}

wpa() {
    local iface="$1" cmd="${2:-}"
    valid_iface "$iface" || usage
    case "$cmd $#" in
        "status 2"|"scan 2"|"scan_results 2"|"reconfigure 2"|"reassociate 2"|"add_network 2"|"list_networks 2"|"disconnect 2")
            ;;
        "disconnect 3"|"enable_network 3"|"select_network 3"|"remove_network 3")
            valid_id "$3" || usage
            ;;
        "disable_network 3")
            valid_id "$3" || [ "$3" = "all" ] || usage
            ;;
        "set_network 5")
            valid_id "$3" || usage
            valid_network_value "$4" "$5" || die 2 "invalid value for $4"
            ;;
        *)
            usage
            ;;
    esac
    shift
    exec /sbin/wpa_cli -i "$iface" "$@"
}

[ "$(id -u)" = 0 ] || die 1 "must run as root"
[ "$#" -ge 2 ] || usage

case "$1 $#" in
    "wpa "*)
        shift
        wpa "$@"
        ;;
    "start 2"|"start 3")
        valid_iface "$2" || usage
        if [ "$#" -eq 3 ]; then
            [ "$3" = "force" ] || usage
            rm -f -- "$ctrl_dir/$2"
        fi
        exec /sbin/wpa_supplicant -i "$2" -c "$wpa_conf" -B
        ;;
    "config 2")
        case "$2" in
            read) [ ! -f "$wpa_conf" ] || cat -- "$wpa_conf" ;;
            install) install_conf ;;
            create)
                [ -e "$wpa_conf" ] && exit 0
                printf 'ctrl_interface=DIR=%s GROUP=netdev\nupdate_config=1\n' "$ctrl_dir" | install_conf
                ;;
            *) usage ;;
        esac
        ;;
    "scan 2")
        valid_iface "$2" || usage
        exec /sbin/iw dev "$2" scan
        ;;
    "txpower 3")
        valid_iface "$2" || usage
        if [ "$3" = "auto" ]; then
            exec /sbin/iw dev "$2" set txpower auto
        fi
        [[ "$3" =~ ^[0-9]{1,5}$ ]] || usage
        exec /sbin/iw dev "$2" set txpower fixed "$3"
        ;;
    *)
        usage
        ;;
esac
