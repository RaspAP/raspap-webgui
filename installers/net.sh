#!/bin/bash
#
# RaspAP network tools helper
#
# Description: Runs ip, iw and nmap for the web interface, and changes the
# settings of the mobile data dialer. www-data runs this script with sudo.
# The script accepts only the commands below and checks each argument, so
# that the web interface can not give other options to these root programs.
#   link <interface> up|down
#   link <interface> address <mac>
#   reg <country>                 iw reg set <country>
#   dhcp-discover <interface>     nmap --script=broadcast-dhcp-discover -e <interface>
#   wvdial                        stdin: 4 lines (PIN, APN, user name, password)
#                                 for /etc/wvdial.conf
# Installed as /usr/local/sbin/raspap-net.sh.
# License: GNU General Public License v3.0
#
# You are not obligated to bundle the LICENSE file with your RaspAP projects as long
# as you leave these references intact in the header comments of your source files.

set -o nounset
set -o pipefail
export LC_ALL=C

readonly wvdial_conf="/etc/wvdial.conf"

die() {
    echo "raspap-net: $2" >&2
    exit "$1"
}

usage() {
    die 2 "usage: $0 link <interface> up|down|address <mac>, reg <country>, dhcp-discover <interface>, wvdial"
}

valid_iface() {
    [[ "$1" =~ ^[A-Za-z0-9][A-Za-z0-9_.-]{0,14}$ ]]
}

# Sets the PIN, the APN and the login in wvdial.conf, as the sed commands of
# MobileDataHandler did: the value in the quotes of "Init1 = AT+CPIN=" and of
# the APN in "Init3 = AT+CGDCONT=1,"IP","", and the lines "Username = " and
# "Password = ".
wvdial() {
    local pin apn user password dir tmp
    IFS= read -r pin || true
    IFS= read -r apn || true
    IFS= read -r user || true
    IFS= read -r password || true
    [[ "$pin" =~ ^[0-9]{0,8}$ ]] || die 2 "invalid PIN"
    [[ "$apn" =~ ^[A-Za-z0-9._-]{0,100}$ ]] || die 2 "invalid APN"
    local re='^[^[:cntrl:]]{0,100}$'
    [[ "$user" =~ $re ]] || die 2 "invalid user name"
    [[ "$password" =~ $re ]] || die 2 "invalid password"
    [ -f "$wvdial_conf" ] && [ ! -L "$wvdial_conf" ] || die 1 "no file $wvdial_conf"

    dir="$(dirname "$wvdial_conf")"
    tmp="$(mktemp "$dir/.wvdial.conf.XXXXXX")" || die 1 "mktemp failed"
    trap 'rm -f "$tmp"' EXIT
    chown --reference="$wvdial_conf" "$tmp" && chmod --reference="$wvdial_conf" "$tmp" || die 1 "can not set the mode of $tmp"
    # the values come in the environment, so awk does not parse them
    PIN="$pin" APN="$apn" APN_USER="$user" APN_PW="$password" awk '
        # replaces the text from "start" to the last quote of the line
        function quoted(line, start, value,   i, rest, j, k) {
            i = index(line, start)
            if (!i) return line
            rest = substr(line, i + length(start))
            j = 0
            for (k = 1; k <= length(rest); k++) if (substr(rest, k, 1) == "\"") j = k
            if (!j) return line
            return substr(line, 1, i - 1) start value "\"" substr(rest, j + 1)
        }
        {
            line = $0
            line = quoted(line, "Init1 = AT+CPIN=\"", ENVIRON["PIN"])
            line = quoted(line, "Init3 = AT+CGDCONT=1,\"IP\",\"", ENVIRON["APN"])
            if (index(line, "Username = ") == 1) line = "Username = " ENVIRON["APN_USER"]
            if (index(line, "Password = ") == 1) line = "Password = " ENVIRON["APN_PW"]
            print line
        }' "$wvdial_conf" > "$tmp" || die 1 "can not write $tmp"
    mv -f "$tmp" "$wvdial_conf" || die 1 "can not write $wvdial_conf"
    trap - EXIT
}

[ "$(id -u)" = 0 ] || die 1 "must run as root"
[ "$#" -ge 1 ] || usage

case "$1 $#" in
    "link 3")
        valid_iface "$2" || usage
        case "$3" in
            up|down) exec /sbin/ip link set dev "$2" "$3" ;;
            *) usage ;;
        esac
        ;;
    "link 4")
        valid_iface "$2" && [ "$3" = "address" ] || usage
        [[ "$4" =~ ^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$ ]] || die 2 "invalid MAC address"
        exec /sbin/ip link set dev "$2" address "$4"
        ;;
    "reg 2")
        [[ "$2" =~ ^([A-Z]{2}|00)$ ]] || die 2 "invalid country code"
        exec /sbin/iw reg set "$2"
        ;;
    "dhcp-discover 2")
        valid_iface "$2" || usage
        exec /usr/bin/nmap --script=broadcast-dhcp-discover -e "$2"
        ;;
    "wvdial 1")
        wvdial
        ;;
    *)
        usage
        ;;
esac
