#!/bin/bash

# When wireless client AP mode is enabled, the hostapdstart script handles starting up network services 
# in a specific order and timing to avoid race conditions. Disabling them here ensures they are not run
# at system startup.

sudo systemctl stop hostapd
sudo systemctl stop dnsmasq
sudo systemctl stop dhcpcd
sudo systemctl disable hostapd
sudo systemctl disable dnsmasq
sudo systemctl disable dhcpcd