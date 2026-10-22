#!/bin/bash
#
# RaspAP iptables save
#
# Description: Saves the current IPv4 iptables rules to /etc/iptables/rules.v4,
# without the rules of the WireGuard kill switch. These rules are active only
# while the tunnel is up. If they are saved, they block the internet at boot
# before the tunnel starts. The file is written to a temporary file first, so
# that it is never partly written.
# Installed as /usr/local/sbin/raspap-iptables-save.sh. All RaspAP scripts that
# save iptables rules call it.
# License: GNU General Public License v3.0
#
# You are not obligated to bundle the LICENSE file with your RaspAP projects as long
# as you leave these references intact in the header comments of your source files.
#
# Usage: raspap-iptables-save.sh

set -o errexit
set -o nounset
set -o pipefail

readonly rulesv4="/etc/iptables/rules.v4"
# RASPAP_KILLSWITCH(6) and the rule of older RaspAP versions
readonly killswitch_rules='RASPAP_KILLSWITCH|^-A OUTPUT (! -d [^ ]+ )?! -o [^ ]+ -m mark ! --mark [^ ]+ -m addrtype ! --dst-type LOCAL -j REJECT'

mkdir -p "$(dirname "$rulesv4")"
tmp="$(mktemp "$rulesv4.XXXXXX")"
trap 'rm -f "$tmp"' EXIT

# grep -v returns 1 when it prints no lines, which is not an error here
iptables-save | { grep -v -E -- "$killswitch_rules" || [ "$?" = 1 ]; } > "$tmp"
chmod 644 "$tmp"
mv -f "$tmp" "$rulesv4"
