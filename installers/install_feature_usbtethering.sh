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

    echo "Copying installers/usb-tethering.sh to $raspap_network"
    sudo cp $webroot_dir/installers/usb-tethering.sh $raspap_network || _install_status 1 "Unable to copy usb-tethering.sh"
    sudo chown -c root:root "$raspap_network/usb-tethering.sh" || _install_status 1 "Unable to change owner"
    sudo chmod 750 "$raspap_network/usb-tethering.sh" || _install_status 1 "Unable to change file permissions"

    echo "Installing udev rule 90-raspap-usb-tethering.rules"
    sudo cp $webroot_dir/installers/90-raspap-usb-tethering.rules /etc/udev/rules.d/ || _install_status 1 "Unable to copy udev rules"
    sudo udevadm control --reload-rules || _install_status 1 "Unable to reload udev rules"

    _install_status 0
}
