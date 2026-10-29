#!/bin/bash
#
# RaspAP set time
# License: GNU General Public License v3.0
# License URI: https://github.com/raspap/raspap-webgui/blob/master/LICENSE
#
# Sets the system clock. The web UI calls this script with the time of the
# browser when the clock of the device is wrong and NTP can not correct it,
# for example when a VPN tunnel is down because the clock is behind.
#
# Usage: settime.sh <seconds since 1970-01-01 UTC>

set -o nounset

readonly min_time=1735689600   # 2025-01-01 00:00:00 UTC
readonly max_time=4102444800   # 2100-01-01 00:00:00 UTC

time="${1:-}"
if ! [[ "$time" =~ ^[0-9]{1,12}$ ]] || [ "$time" -lt "$min_time" ] || [ "$time" -gt "$max_time" ]; then
    echo "Usage: $0 <seconds since 1970-01-01 UTC, from 2025 until 2100>" >&2
    exit 1
fi

date -u -s "@$time" > /dev/null || exit 1

# keep the time across a reboot on devices without an RTC battery
if command -v fake-hwclock > /dev/null; then
    fake-hwclock save > /dev/null 2>&1 || true
fi
date -u +%s
