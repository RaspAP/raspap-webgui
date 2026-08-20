#!/bin/bash
#
# RaspAP feature installation: handling of mobile data clients and client configuration
# to be sources by the RaspAP installer script
# Author: @zbchristian <christian@zeitnitz.eu>
# Author URI: https://github.com/zbchristian/
# License: GNU General Public License v3.0
# License URI: https://github.com/raspap/raspap-webgui/blob/master/LICENSE

# path for mobile modem scripts 
readonly raspap_clients_scripts="/usr/local/sbin"
#
# table of mobile network operators - links the 5 digit operator code (from the modem) with a clear text operator name 
readonly raspap_clients_operator_table="https://raw.githubusercontent.com/musalbas/mcc-mnc-table/master/mcc-mnc-table.csv"

function _install_feature_clients() {
    name="feature clients"

    _install_log "Install $name"

    _install_log " - required packages for mobile data clients"
    sudo apt-get -y install wvdial socat bc || _install_status 1 "Unable to install dependencies for $name"

    _install_log " - copy configuration files and scripts"
    # Move scripts 
    sudo cp "$webroot_dir/config/client_config/"*.sh "$raspap_clients_scripts/" || _install_status 1 "Unable to move client scripts ($name)"
    sudo chmod a+rx "$raspap_clients_scripts/"*.sh  || _install_status 1 "Unable to chmod client scripts ($name)"
    #    wget $raspap_clients_operator_table -o "$raspap_clients_scripts/"mcc-mnc-table.csv || _install_status 1 "Unable to wget operator table ($name)"
    sudo cp "$webroot_dir/config/client_config/mcc-mnc-table.csv" "$raspap_clients_scripts/" || _install_status 1 "Unable to move client data ($name)"
    # wvdial settings
    sudo cp "$webroot_dir/config/client_config/wvdial.conf" "/etc/" || _install_status 1 "Unable to install client configuration ($name)"
    sudo cp "$webroot_dir/config/client_config/interfaces" "/etc/network/interfaces" || _install_status 1 "Unable to install interface settings ($name)"
    # udev rules/services to auto start mobile data services
    sudo cp "$webroot_dir/config/client_config/70-mobile-data-sticks.rules" "/etc/udev/rules.d/" || _install_status 1 "Unable to install client udev rules ($name)"
    sudo cp "$webroot_dir/config/client_config/80-raspap-net-devices.rules" "/etc/udev/rules.d/" || _install_status 1 "Unable to install client udev rules ($name)"
    sudo cp "$webroot_dir/config/client_config/"*.service "/etc/systemd/system/" || _install_status 1 "Unable to install client startup services ($name)"
    # client configuration and udev rule templates
    sudo cp "$webroot_dir/config/client_udev_prototypes.json" "/etc/raspap/networking/" || _install_status 1 "Unable to install client configuration ($name)"
    _install_status 0
}
