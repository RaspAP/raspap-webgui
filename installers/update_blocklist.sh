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

wget -q ${update_url} -O ${destination}${file} &> /dev/null

echo "$?"

