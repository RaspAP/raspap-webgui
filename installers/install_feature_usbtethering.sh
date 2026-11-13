#!/bin/bash
#
# RaspAP feature installation: USB tethering
# to be sourced by the RaspAP installer script
# Author: @billz <billzimmerman@gmail.com>
# Author URI: https://github.com/billz/
# License: GNU General Public License v3.0
# License URI: https://github.com/raspap/raspap-webgui/blob/master/LICENSE

function _install_feature_usbtethering() {
    name="Feature USB tethering"

    _install_log "$name"
    _install_log "Configuring USB tethering support"

    # udev runs this script as root, so it must be in a directory that only
    # root can change
    echo "Copying installers/usb-tethering.sh to /usr/local/sbin"
    sudo install -o root -g root -m 755 "$webroot_dir/installers/usb-tethering.sh" /usr/local/sbin/raspap-usb-tethering.sh || _install_status 1 "Unable to install usb-tethering.sh"

    echo "Installing udev rule 90-raspap-usb-tethering.rules"
    sudo cp $webroot_dir/installers/90-raspap-usb-tethering.rules /etc/udev/rules.d/ || _install_status 1 "Unable to copy udev rules"
    sudo udevadm control --reload-rules || _install_status 1 "Unable to reload udev rules"

    _install_status 0
}
