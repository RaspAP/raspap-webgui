#!/bin/bash
#
# RaspAP wireless adapter hardware detection
# Author: @billz <billzimmerman@gmail.com>
# Author URI: https://github.com/billz/
# License: GNU General Public License v3.0
# License URI: https://github.com/raspap/raspap-webgui/blob/master/LICENSE
#
# Usage: detect_adapter.sh <interface>
# Exit codes: 0=detected, 1=not found, 2=error
#
# Example output:
#   INTERFACE=wlan1
#   DRIVER=mt7921u
#   VENDOR_ID=0e8d
#   ADAPTER_PROFILE=hostapd-mt7921u-ax.conf
#   DETECTED=true

INTERFACE="${1:-wlan0}"

# Check if interface exists
if [ ! -d "/sys/class/net/$INTERFACE" ]; then
    echo "ERROR: Interface $INTERFACE does not exist" >&2
    exit 2
fi

# Check if interface is wireless
if [ ! -d "/sys/class/net/$INTERFACE/wireless" ]; then
    echo "ERROR: $INTERFACE is not a wireless interface" >&2
    exit 2
fi

# Fetch udev properties
UDEV_INFO=$(udevadm info "/sys/class/net/$INTERFACE" 2>/dev/null)
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to get udev info for $INTERFACE" >&2
    exit 2
fi

DRIVER=$(echo "$UDEV_INFO" | grep -oP 'E: ID_NET_DRIVER=\K.*' | head -1)
VENDOR_ID=$(echo "$UDEV_INFO" | grep -oP 'E: ID_VENDOR_ID=\K.*' | head -1)
MODEL_ID=$(echo "$UDEV_INFO" | grep -oP 'E: ID_MODEL_ID=\K.*' | head -1)
VENDOR_NAME=$(echo "$UDEV_INFO" | grep -oP 'E: ID_VENDOR_FROM_DATABASE=\K.*' | head -1)

# Output results as key-value pairs
echo "INTERFACE=$INTERFACE"
echo "DRIVER=${DRIVER:-unknown}"
echo "VENDOR_ID=${VENDOR_ID:-unknown}"
echo "MODEL_ID=${MODEL_ID:-unknown}"
echo "VENDOR_NAME=${VENDOR_NAME:-unknown}"

# Detect mt7921u driver, assign profile
if [ "$DRIVER" = "mt7921u" ]; then
    echo "ADAPTER_PROFILE=hostapd-mt7921u-ax.conf"
    echo "DETECTED=true"
    exit 0
fi

# No profile detected
echo "DETECTED=false"
exit 1

