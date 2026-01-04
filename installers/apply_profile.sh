#!/bin/bash
#
# RaspAP adapter profile application
# Author: @billz <billzimmerman@gmail.com>
# Author URI: https://github.com/billz
# Project URI: https://github.com/RaspAP/
# License: GNU General Public License v3.0
# License URI: https://github.com/RaspAP/raspap-webgui/blob/master/LICENSE
#
# Applies adapter-specific hostapd configuration while preserving user settings
#
# Usage: apply_profile.sh <interface> <profile_path>

INTERFACE="$1"
PROFILE_PATH="$2"
HOSTAPD_CONF="/etc/hostapd/hostapd.conf"
BACKUP_CONF="/etc/hostapd/hostapd.conf.backup"

# Validate parameters
if [ -z "$INTERFACE" ] || [ -z "$PROFILE_PATH" ]; then
    echo "ERROR: Missing required parameters" >&2
    echo "Usage: $0 <interface> <profile_path>" >&2
    exit 1
fi

# Check if profile exists
if [ ! -f "$PROFILE_PATH" ]; then
    echo "ERROR: Profile not found: $PROFILE_PATH" >&2
    exit 1
fi

# Preserve existing SSID and passphrase if hostapd.conf exists
EXISTING_SSID=""
EXISTING_PASSPHRASE=""

if [ -f "$HOSTAPD_CONF" ]; then
    EXISTING_SSID=$(grep -oP '^ssid=\K.*' "$HOSTAPD_CONF" | head -1)
    EXISTING_PASSPHRASE=$(grep -oP '^wpa_passphrase=\K.*' "$HOSTAPD_CONF" | head -1)
    
    cp "$HOSTAPD_CONF" "$BACKUP_CONF"
    echo "Backed up existing configuration to $BACKUP_CONF"
fi

# Copy profile template to hostapd config
cp "$PROFILE_PATH" "$HOSTAPD_CONF"
echo "Applied profile: $PROFILE_PATH"

# Update interface in config
sed -i "s/^interface=.*/interface=$INTERFACE/" "$HOSTAPD_CONF"
echo "Updated interface to: $INTERFACE"

# Restore user's SSID if it exists and isn't default
if [ -n "$EXISTING_SSID" ] && [ "$EXISTING_SSID" != "RaspAP" ]; then
    sed -i "s/^ssid=.*/ssid=$EXISTING_SSID/" "$HOSTAPD_CONF"
    echo "Preserved existing SSID: $EXISTING_SSID"
fi

# Restore user's passphrase if it exists and isn't default
if [ -n "$EXISTING_PASSPHRASE" ] && [ "$EXISTING_PASSPHRASE" != "ChangeMe" ]; then
    sed -i "s/^wpa_passphrase=.*/wpa_passphrase=$EXISTING_PASSPHRASE/" "$HOSTAPD_CONF"
    echo "Preserved existing passphrase"
fi

echo "Successfully applied adapter profile configuration"
exit 0

