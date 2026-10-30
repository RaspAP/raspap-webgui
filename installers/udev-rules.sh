#!/bin/bash
#
# RaspAP udev rules helper
#
# Description: Changes the RaspAP rules for network devices in
# /etc/udev/rules.d/80-raspap-net-devices.rules. www-data runs this script
# with sudo. The script accepts only the keys and values of the RaspAP device
# rules (see config/client_udev_prototypes.json), and it keeps the file owned
# by root.
# Installed as /usr/local/sbin/raspap-udev-rules.sh.
# License: GNU General Public License v3.0
#
# You are not obligated to bundle the LICENSE file with your RaspAP projects as long
# as you leave these references intact in the header comments of your source files.
#
# Usage: raspap-udev-rules.sh init
#        raspap-udev-rules.sh replace <mac|-> <vid|-> <pid|->   (new rule on stdin)

set -o nounset
set -o pipefail

readonly rules_file="/etc/udev/rules.d/80-raspap-net-devices.rules"
readonly max_rule_length=512

die() {
    echo "raspap-udev-rules: $2" >&2
    exit "$1"
}

usage() {
    die 2 "usage: $0 init, or replace <mac|-> <vid|-> <pid|-> (rule on stdin)"
}

header() {
    printf '%s\n' "# UDEV Rules managed by RaspAP" \
        "# This file contains persistent naming rules for network interfaces." \
        "# Automatically generated. Do not edit manually."
}

# Prints the current rules file, or the header when the file does not exist
current() {
    if [ -f "$rules_file" ]; then cat "$rules_file"; else header; fi
}

# Replaces the rules file with stdin. udev reads only files that end in
# .rules, so it does not read the temporary file.
write_file() {
    local tmp
    tmp="$(mktemp "$rules_file.XXXXXX")" || die 1 "mktemp failed"
    if ! { cat > "$tmp" && chmod 644 "$tmp" && mv -f "$tmp" "$rules_file"; }; then
        rm -f "$tmp"
        die 1 "can not write $rules_file"
    fi
}

# A rule of RaspAP: keys and values of the prototypes only, separated by ", "
valid_rule() {
    local rule="$1" token address=0 vendor=0 product=0 kernel=0
    local -a tokens
    [ "${#rule}" -le "$max_rule_length" ] || return 1
    [[ "$rule" =~ ^[A-Za-z0-9\ ,=+\"{}:%._@-]+$ ]] || return 1
    [[ "$rule" == SUBSYSTEM==* ]] || return 1
    IFS=',' read -r -a tokens <<< "$rule"
    for token in "${tokens[@]}"; do
        token="${token#"${token%%[! ]*}"}"
        token="${token%"${token##*[! ]}"}"
        case "$token" in
            'SUBSYSTEM=="net"'|'SUBSYSTEM=="tty"'|'SUBSYSTEMS=="usb"'|'ACTION=="add"'|'TAG+="systemd"') ;;
            'ENV{SYSTEMD_WANTS}="start start_huawei_hilink@hilink%n.service"') ;;
            'ENV{SYSTEMD_WANTS}="start start_ppp0_device.service"') ;;
            *)
                if [[ "$token" =~ ^KERNEL==\"ttyUSB[0-9]\"$ ]]; then
                    kernel=1
                elif [[ "$token" =~ ^ATTR\{address\}==\"[0-9a-f]{2}(:[0-9a-f]{2}){5}\"$ ]]; then
                    address=1
                elif [[ "$token" =~ ^ATTRS\{idVendor\}==\"[0-9a-f]{4}\"$ ]]; then
                    vendor=1
                elif [[ "$token" =~ ^ATTRS\{idProduct\}==\"[0-9a-f]{4}\"$ ]]; then
                    product=1
                elif ! [[ "$token" =~ ^NAME=\"[a-z0-9]{1,20}(%n)?\"$ ]] &&
                     ! [[ "$token" =~ ^ENV\{raspapType\}=\"[a-z]{1,16}\"$ ]]; then
                    return 1
                fi
                ;;
        esac
    done
    # the rule must match one device: a rule without a match key renames
    # each device of the subsystem
    [ "$address" = 1 ] || { [ "$vendor" = 1 ] && [ "$product" = 1 ]; } || [ "$kernel" = 1 ]
}

# Replaces the rules for a MAC address and the rules for a USB vendor and
# product ID ("-" means no value) with the rule on stdin. The script checks
# the rule first, so a refused rule does not remove a rule.
replace() {
    local mac="$1" vid="$2" pid="$3" rule
    [ "$mac" = "-" ] || [[ "$mac" =~ ^[0-9a-f]{2}(:[0-9a-f]{2}){5}$ ]] || die 2 "invalid MAC address"
    [ "$vid" = "-" ] || [[ "$vid" =~ ^[0-9a-f]{4}$ ]] || die 2 "invalid vendor ID"
    [ "$pid" = "-" ] || [[ "$pid" =~ ^[0-9a-f]{4}$ ]] || die 2 "invalid product ID"
    IFS= read -r -n $((max_rule_length + 1)) rule || [ -n "$rule" ] || die 2 "no rule on stdin"
    valid_rule "$rule" || die 2 "invalid rule"
    current | awk -v mac="$mac" -v vid="$vid" -v pid="$pid" -v rule="$rule" '
        /^[[:space:]]*#/ { print; next }
        {
            line = tolower($0)
            if ($0 == rule) next
            if (mac != "-" && index(line, "attr{address}==\"" mac "\"")) next
            if (vid != "-" && pid != "-" && index(line, "attrs{idvendor}==\"" vid "\"") &&
                index(line, "attrs{idproduct}==\"" pid "\"")) next
            print
        }
        END { print rule }' | write_file
}

[ "$(id -u)" = 0 ] || die 1 "must run as root"
[ "$#" -ge 1 ] || usage

case "$1" in
    init)
        [ "$#" -eq 1 ] || usage
        [ -f "$rules_file" ] || header | write_file
        ;;
    replace)
        [ "$#" -eq 4 ] || usage
        replace "$2" "$3" "$4"
        ;;
    *)
        usage
        ;;
esac
