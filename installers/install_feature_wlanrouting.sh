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

    # www-data can run this script with sudo, so it must be in a directory that
    # only root can change
    echo "Copying installers/toggle-routing.sh to /usr/local/sbin"
    sudo install -o root -g root -m 755 "$webroot_dir/installers/toggle-routing.sh" /usr/local/sbin/raspap-toggle-routing.sh || _install_status 1 "Unable to install toggle-routing.sh script"
    _install_status 0
}

