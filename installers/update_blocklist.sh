#!/bin/bash
#
#
# @author billz
# license: GNU General Public License v3.0

# Exit on error
set -o errexit
# Exit on error inside functions
set -o errtrace
# Turn on traces, disabled by default
#set -o xtrace

# Usage: update_blocklist.sh <https URL> <hostnames.txt|domains.txt> /etc/raspap/adblock/
# www-data runs this script with sudo, so it checks each argument. It prints
# the exit status of wget.
readonly adblock_dir="/etc/raspap/adblock/"

if [ "$#" -ne 3 ]; then
    echo "Usage: $0 <https URL> <hostnames.txt|domains.txt> $adblock_dir" >&2
    exit 2
fi
update_url=$1
file=$2
destination=$3

if ! [[ "$update_url" =~ ^https://[A-Za-z0-9.-]+(/[A-Za-z0-9._~/%+-]*)?$ ]]; then
    echo "Invalid blocklist URL" >&2
    exit 2
fi
if [ "$file" != "hostnames.txt" ] && [ "$file" != "domains.txt" ]; then
    echo "Invalid blocklist file" >&2
    exit 2
fi
if [ "${destination%/}/" != "$adblock_dir" ]; then
    echo "Invalid blocklist directory" >&2
    exit 2
fi

# download to a temporary file, and replace the list only when wget succeeds
tmp=$(mktemp "${adblock_dir}.${file}.XXXXXX")
status=0
wget -q --no-use-server-timestamps -O "$tmp" -- "$update_url" &> /dev/null || status=$?
if [ "$status" -eq 0 ]; then
    chmod 644 "$tmp"
    mv -f "$tmp" "${adblock_dir}${file}"
else
    rm -f "$tmp"
fi
echo "$status"
