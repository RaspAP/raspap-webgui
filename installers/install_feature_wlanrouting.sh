#!/bin/bash
#
# RaspAP feature installation: WLAN Routing
# to be sourced by the RaspAP installer script
# Author: @billz <billzimmerman@gmail.com>
# Author URI: https://github.com/billz/
# License: GNU General Public License v3.0
# License URI: https://github.com/raspap/raspap-webgui/blob/master/LICENSE

function _install_feature_wlanrouting() {
    name="Feature WLAN routing"

    _install_log "$name"
    _install_log "Configuring Wireless LAN routing support"

    echo "Copying installers/toggle-routing.sh to $raspap_network"
    sudo cp $webroot_dir/installers/toggle-routing.sh $raspap_network || _install_status 1 "Unable to move toggle-routing.sh script"
    sudo chown -c root:root "$raspap_network/"*.sh || _install_status 1 "Unable change owner and/or group"
    sudo chmod 750 "$raspap_network/"*.sh || _install_status 1 "Unable to change file permissions"
    _install_status 0
}

