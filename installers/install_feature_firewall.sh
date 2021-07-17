#!/bin/bash
#
# RaspAP feature installation: Firewall
# to be sources by the RaspAP installer script
# Author: @zbchristian <christian@zeitnitz.eu>
# Author URI: https://github.com/zbchristian/
# License: GNU General Public License v3.0
# License URI: https://github.com/raspap/raspap-webgui/blob/master/LICENSE

function _install_feature_firewall() {
    name="feature firewall"

    _install_log "Install $name"
    _install_log " - copy configuration file"
	# create config dir
	sudo mkdir "/etc/raspap/networking/firewall" || _install_status 1 "Unable to create firewall config directory 
    # copy firewall configuration 
    sudo cp "$webroot_dir/config/iptables_rules.json" "/etc/raspap/networking/" || _install_status 1 "Unable to install client configuration ($name)"
    _install_status 0
}
