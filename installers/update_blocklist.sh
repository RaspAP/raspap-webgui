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

update_url=$1
file=$2
destination=$3

target="${destination}${file}"

# Download to a temporary file, and replace the list only when the download
# is successful. A failed download keeps the current list.
tmp=$(mktemp "${target}.XXXXXX")
trap 'rm -f "$tmp"' EXIT

wget -q --no-use-server-timestamps ${update_url} -O "$tmp" &> /dev/null
if [ ! -s "$tmp" ]; then
    echo "Downloaded list is empty" >&2
    exit 1
fi

if [ -f "$target" ]; then
    chown --reference="$target" "$tmp"
    chmod --reference="$target" "$tmp"
else
    chmod 644 "$tmp"
fi
mv -f "$tmp" "$target"

echo "$?"

