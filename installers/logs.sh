#!/bin/bash
#
# RaspAP log helper
#
# Description: Reads and clears the service logs that the web interface
# shows. www-data runs this script with sudo. The logs have fixed names, so
# the web interface can not read or clear other files, and the logs need not
# be readable for each user.
#   read dnsmasq [<bytes>]        /var/log/dnsmasq.log, or its last bytes
#   hostapd <lines>               journalctl -u hostapd.service -n <lines>
#   wireguard                     journalctl --identifier wg-quick
#   clear dnsmasq|hostapd|openvpn /var/log/dnsmasq.log, /tmp/hostapd.log, /tmp/openvpn.log
# Installed as /usr/local/sbin/raspap-logs.sh.
# License: GNU General Public License v3.0
#
# You are not obligated to bundle the LICENSE file with your RaspAP projects as long
# as you leave these references intact in the header comments of your source files.

set -o nounset
set -o pipefail

readonly dnsmasq_log="/var/log/dnsmasq.log"

die() {
    echo "raspap-logs: $2" >&2
    exit "$1"
}

usage() {
    die 2 "usage: $0 read dnsmasq [<bytes>], hostapd <lines>, wireguard, clear dnsmasq|hostapd|openvpn"
}

# A log file is a regular file, not a link. A log in /tmp must belong to
# root, because the services and scripts of RaspAP write it as root.
valid_log() {
    local file="$1" owners="$2"
    [ -f "$file" ] && [ ! -L "$file" ] || return 1
    [[ " $owners " == *" $(stat -c %U "$file") "* ]]
}

[ "$(id -u)" = 0 ] || die 1 "must run as root"
[ "$#" -ge 1 ] || usage

case "$1 $#" in
    "read 2"|"read 3")
        [ "$2" = "dnsmasq" ] || usage
        [ -e "$dnsmasq_log" ] || exit 0
        valid_log "$dnsmasq_log" "dnsmasq root" || die 1 "invalid file $dnsmasq_log"
        if [ "$#" -eq 3 ]; then
            [[ "$3" =~ ^[0-9]{1,9}$ ]] || usage
            exec tail -c "$3" -- "$dnsmasq_log"
        fi
        exec cat -- "$dnsmasq_log"
        ;;
    "hostapd 2")
        [[ "$2" =~ ^[0-9]{1,4}$ ]] && [ "$2" -ge 1 ] && [ "$2" -le 1000 ] || usage
        exec journalctl -u hostapd.service -n "$2" --no-pager
        ;;
    "wireguard 1")
        exec journalctl --identifier wg-quick --no-pager
        ;;
    "clear 2")
        case "$2" in
            dnsmasq) file="$dnsmasq_log"; owners="dnsmasq root" ;;
            hostapd) file="/tmp/hostapd.log"; owners="root" ;;
            openvpn) file="/tmp/openvpn.log"; owners="root" ;;
            *) usage ;;
        esac
        [ -e "$file" ] || exit 0
        valid_log "$file" "$owners" || die 1 "invalid file $file"
        # -c: no new file
        exec truncate -c -s 0 -- "$file"
        ;;
    *)
        usage
        ;;
esac
