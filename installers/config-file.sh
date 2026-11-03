#!/bin/bash
#
# RaspAP config file helper
#
# Description: Installs the configuration files of the hotspot services that
# the web interface changes. www-data runs this script with sudo. The new
# content comes on stdin, so there is no temporary file in /tmp. Each kind of
# file has a fixed target, and the script refuses the options that make a
# service run a program, include another file, or write a file as root.
#   install hostapd [<interface>]   /etc/hostapd/hostapd.conf (hostapd-<interface>.conf)
#   install dhcpcd                  /etc/dhcpcd.conf
#   install dnsmasq <name>          /etc/dnsmasq.d/090_<name>.conf
#   install adblock-custom          /etc/raspap/adblock/custom.txt
#   remove dnsmasq <name>           /etc/dnsmasq.d/090_<name>.conf
# Installed as /usr/local/sbin/raspap-config-file.sh.
# License: GNU General Public License v3.0
#
# You are not obligated to bundle the LICENSE file with your RaspAP projects as long
# as you leave these references intact in the header comments of your source files.

set -o nounset
set -o pipefail

readonly max_size=1048576
readonly adblock_dir="/etc/raspap/adblock/"

die() {
    echo "raspap-config-file: $2" >&2
    exit "$1"
}

usage() {
    die 2 "usage: $0 install hostapd [<interface>] | dhcpcd | dnsmasq <name> | adblock-custom, or remove dnsmasq <name>"
}

# An interface name, or a name of a RaspAP dnsmasq file (raspap, adblock)
valid_name() {
    [[ "$1" =~ ^[A-Za-z0-9][A-Za-z0-9_.-]{0,14}$ ]] && [[ "$1" != *..* ]]
}

# The options of each kind that the file must not have. A line is
# "option=value", "option value" or "option", with spaces before it.
check_hostapd() {
    awk '
        /^[[:space:]]*(#|$)/ { next }
        {
            line = $0; sub(/^[[:space:]]+/, "", line)
            key = line; sub(/[[:space:]]*=.*/, "", key)
            value = line; sub(/^[^=]*=/, "", value)
            if (key == "dump_file") { print key; exit 1 }
            if (key == "ctrl_interface" && value != "/var/run/hostapd" && value !~ /^DIR=\/var\/run\/hostapd( |$)/) { print key; exit 1 }
        }' "$1"
}

check_dhcpcd() {
    awk '
        /^[[:space:]]*(#|$)/ { next }
        {
            line = $0; sub(/^[[:space:]]+/, "", line)
            key = line; sub(/[[:space:]].*/, "", key)
            if (key == "script" || key == "env") { print key; exit 1 }
        }' "$1"
}

check_dnsmasq() {
    awk -v adblock="$adblock_dir" '
        /^[[:space:]]*(#|$)/ { next }
        {
            line = $0; sub(/^[[:space:]]+/, "", line); sub(/[[:space:]]+$/, "", line)
            key = line; sub(/=.*/, "", key)
            value = line; if (index(line, "=")) { sub(/^[^=]*=/, "", value) } else { value = "" }
            if (key ~ /^(dhcp-script|dhcp-luascript|dhcp-scriptuser|script-arp|conf-script|user|group|dumpfile|pid-file|enable-tftp|tftp-root|servers-file)$/) { print key; exit 1 }
            if (key == "conf-dir" && value != "/etc/dnsmasq.d") { print key; exit 1 }
            if ((key == "conf-file" || key == "addn-hosts") && (index(value, adblock) != 1 || value ~ /\.\./)) { print key; exit 1 }
            if (key == "log-facility" && value != "/var/log/dnsmasq.log" && value !~ /^[A-Za-z0-9]+$/) { print key; exit 1 }
        }' "$1"
}

# Writes stdin to the target. The temporary file is in the same directory and
# starts with ".", so dnsmasq does not read it. The new file gets the owner and
# the mode of the old file.
install_file() {
    local target="$1" kind="$2" dir base tmp option
    dir="$(dirname "$target")"
    base="$(basename "$target")"
    [ -d "$dir" ] || die 1 "no directory $dir"
    tmp="$(mktemp "$dir/.$base.XXXXXX")" || die 1 "mktemp failed"
    trap 'rm -f "$tmp"' EXIT
    head -c $((max_size + 1)) > "$tmp" || die 1 "can not write $tmp"
    [ "$(stat -c %s "$tmp")" -le "$max_size" ] || die 2 "the file is too large"
    [ "$(tr -d '\000' < "$tmp" | wc -c)" = "$(stat -c %s "$tmp")" ] || die 2 "the file has a NUL byte"
    case "$kind" in
        hostapd|dhcpcd|dnsmasq)
            if ! option="$(check_"$kind" "$tmp")"; then
                die 2 "option not allowed in the $kind configuration: $option"
            fi
            ;;
    esac
    if [ -e "$target" ]; then
        chown --reference="$target" "$tmp" && chmod --reference="$target" "$tmp" || die 1 "can not set the mode of $tmp"
    else
        chown root:root "$tmp" && chmod 644 "$tmp" || die 1 "can not set the mode of $tmp"
    fi
    mv -f "$tmp" "$target" || die 1 "can not write $target"
    trap - EXIT
}

[ "$(id -u)" = 0 ] || die 1 "must run as root"
[ "$#" -ge 2 ] || usage

case "$1 $2" in
    "install hostapd")
        if [ "$#" -eq 2 ]; then
            install_file /etc/hostapd/hostapd.conf hostapd
        elif [ "$#" -eq 3 ] && valid_name "$3"; then
            install_file "/etc/hostapd/hostapd-$3.conf" hostapd
        else
            usage
        fi
        ;;
    "install dhcpcd")
        [ "$#" -eq 2 ] || usage
        install_file /etc/dhcpcd.conf dhcpcd
        ;;
    "install dnsmasq")
        [ "$#" -eq 3 ] && valid_name "$3" || usage
        install_file "/etc/dnsmasq.d/090_$3.conf" dnsmasq
        ;;
    "install adblock-custom")
        [ "$#" -eq 2 ] || usage
        install_file "${adblock_dir}custom.txt" adblock-custom
        ;;
    "remove dnsmasq")
        [ "$#" -eq 3 ] && valid_name "$3" || usage
        [ -f "/etc/dnsmasq.d/090_$3.conf" ] || die 1 "no file /etc/dnsmasq.d/090_$3.conf"
        rm -f "/etc/dnsmasq.d/090_$3.conf"
        ;;
    *)
        usage
        ;;
esac
