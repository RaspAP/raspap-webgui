#!/bin/bash
#
# RaspAP Tailscale UDP optimization
# Author: @billz <billzimmerman@gmail.com>
# License: GNU General Public License v3.0
# See: https://tailscale.com/kb/1320/performance-best-practices 


# Exit on error
set -o errexit
# Exit on error inside functions
set -o errtrace
# Turn on traces, disabled by default
# set -o xtrace

# resolve active network interface
NETDEV=$(ip -o route get 8.8.8.8 | awk '{print $5}')

if [ -z "$NETDEV" ]; then
  echo "Error: Unable to detect active network interface" >&2
  exit 1
fi

echo "Optimizing UDP performance for interface: $NETDEV"

# execute ethtool
ethtool -K "$NETDEV" rx-udp-gro-forwarding on
ethtool -K "$NETDEV" rx-gro-list off

# reload systemd if unit file was recently installed
systemctl daemon-reexec
systemctl daemon-reload

# enable and start the tailscale-ethtool@ service
systemctl enable --now "tailscale-ethtool@${NETDEV}.service"

echo "Tailscale UDP optimization applied successfully"

