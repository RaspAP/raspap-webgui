#!/bin/bash
#
# RaspAP network activity monitor
# Reads from /proc/net/dev to get TX/RX byte counters for a given interface (wlan0 by default),
# calculates the difference and writes to /dev/shm/net_activity.
#
# Author: @billz <billzimmerman@gmail.com>
# Author URI: https://github.com/billz/
# License: GNU General Public License v3.0
# License URI: https://github.com/raspap/raspap-webgui/blob/master/LICENSE
#
# /usr/local/bin/raspap-network-activity.sh

# Exit on error
set -o errexit
# Exit on error inside functions
set -o errtrace
# Turn on traces, disabled by default
# set -o xtrace

IFACE="${1:-wlan0}"              # default to wlan0 if not specified
TMPFILE="/dev/shm/net_activity"  # tmpfs that resides in RAM 
INTERVAL=0.05                    # 50 ms

# initialize
prev_rx=0
prev_tx=0

get_bytes() {
  awk -v iface="$IFACE" '$1 ~ iface":" {
    gsub(":", "", $1);
    print $2, $10
  }' /proc/net/dev
}

read rx tx < <(get_bytes)
prev_rx=$rx
prev_tx=$tx

while true; do
  sleep $INTERVAL
  read rx tx < <(get_bytes)

  rx_diff=$((rx - prev_rx))
  tx_diff=$((tx - prev_tx))
  total_diff=$((rx_diff + tx_diff))
  echo "$total_diff" > "$TMPFILE"

  prev_rx=$rx
  prev_tx=$tx
done

