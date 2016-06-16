UPDATE_URL="https://raw.githubusercontent.com/billz/raspap-webgui/master/"
wget -q ${UPDATE_URL}/installers/common.sh -O /tmp/raspapcommon.sh
source /tmp/raspapcommon.sh && rm -f /tmp/raspapcommon.sh

function update_system_packages() {
    install_log "Updating sources"
    sudo apt-get update || install_error "Unable to update package list"
    sudo apt-get upgrade || install_error "Unable to upgrade packages"
}

function install_dependencies() {
    install_log "Installing required packages"
    sudo apt-get install lighttpd php5-cgi git || install_error "Unable to install dependencies"
}

install_raspap
