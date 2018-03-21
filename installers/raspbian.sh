#UPDATE_URL="https://raw.githubusercontent.com/billz/raspap-webgui/master/"
# Temporary change to test quick installer
UPDATE_URL="https://raw.githubusercontent.com/njkeng/raspap-webgui/reset-button/"
git clone -b reset-button --single-branch https://github.com/njkeng/raspap-webgui /tmp/raspap-webgui
wget -q ${UPDATE_URL}/installers/common.sh -O /tmp/raspapcommon.sh
source /tmp/raspapcommon.sh && rm -f /tmp/raspapcommon.sh

function update_system_packages() {
    install_log "Updating sources"
    sudo apt-get update || install_error "Unable to update package list"
}

function install_dependencies() {
    install_log "Installing required packages"
    sudo apt-get install lighttpd $php_package git hostapd dnsmasq || install_error "Unable to install dependencies"
}

install_raspap
