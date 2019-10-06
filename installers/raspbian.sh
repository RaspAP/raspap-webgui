#!/bin/bash
#
# RaspAP Quick Installer
# author: @billz
# license: GNU General Public License v3.0
#
# Command-line options: -y, --yes, --assume-yes
# Assume "yes" as answer to all prompts and run non-interactively

UPDATE_URL="https://raw.githubusercontent.com/billz/raspap-webgui/master/"
wget -q ${UPDATE_URL}/installers/common.sh -O /tmp/raspapcommon.sh
source /tmp/raspapcommon.sh && rm -f /tmp/raspapcommon.sh

assume_yes=0
positional=()
while [[ $# -gt 0 ]]
do
key="$1"

case $key in
    -y|--yes|--assume-yes)
    assume_yes=1
    apt_option="-y"
    shift # past argument
    shift # past value
    ;;
    *)    # unknown option
    shift # past argument
    ;;
esac
done

function update_system_packages() {
    install_log "Updating sources"
    sudo apt-get update || install_error "Unable to update package list"
}

function install_dependencies() {
    install_log "Installing required packages"
    sudo apt-get install $apt_option lighttpd $php_package git hostapd dnsmasq vnstat || install_error "Unable to install dependencies"
}

install_raspap
