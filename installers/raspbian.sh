#!/bin/bash
#
# RaspAP Quick Installer
# author: @billz
# license: GNU General Public License v3.0
#
# Command-line options:
# -y, --yes, --assume-yes
# Assume "yes" as answer to all prompts and run non-interactively
#
# c, --crt, --certficate
# Installs mkcert and generates an SSL certificate for lighttpd

UPDATE_URL="https://raw.githubusercontent.com/billz/raspap-webgui/master/"
VERSION=$(curl -s "https://api.github.com/repos/billz/raspap-webgui/releases/latest" | grep -Po '"tag_name": "\K.*?(?=")' )
USAGE=$'Usage: raspbian.sh [OPTION] \n\n-y, --yes, --assume-yes\n\tAssumes "yes" as an answer to all prompts'
USAGE+=$'\n-c, --crt, --certficate\n\tInstalls an SSL certificate for lighttpd\n'

assume_yes=0

while :; do
    case $1 in
        -y|--yes|--assume-yes)
        assume_yes=1
        apt_option="-y"
        ;;
        -c|--crt|--certificate)
        install_cert=1
        ;;
        -*|--*)
        echo "Unknown option: $1";
        echo "$USAGE"
        exit 1
        ;;
        *)
        break
        ;;
    esac
    shift
done

# Outputs a welcome message
function display_welcome() {
    raspberry='\033[0;35m'
    green='\033[1;32m'

    echo -e "${raspberry}\n"
    echo -e " 888888ba                              .d888888   888888ba"
    echo -e " 88     8b                            d8     88   88     8b"
    echo -e "a88aaaa8P' .d8888b. .d8888b. 88d888b. 88aaaaa88a a88aaaa8P"
    echo -e " 88    8b. 88    88 Y8ooooo. 88    88 88     88   88"
    echo -e " 88     88 88.  .88       88 88.  .88 88     88   88"
    echo -e " dP     dP  88888P8  88888P  88Y888P  88     88   dP"
    echo -e "                             88"
    echo -e "                             dP       version ${VERSION}"
    echo -e "${green}"
    echo -e "The Quick Installer will guide you through a few easy steps\n\n"
}

# Outputs a RaspAP Install log line
function install_log() {
    echo -e "\033[1;32mRaspAP Install: $*\033[m"
}

# Outputs a RaspAP Install Error log line and exits with status code 1
function install_error() {
    echo -e "\033[1;37;41mRaspAP Install Error: $*\033[m"
    exit 1
}

# Outputs a RaspAP Warning line
function install_warning() {
    echo -e "\033[1;33mWarning: $*\033[m"
}

# Outputs a RaspAP divider
function install_divider() {
    echo -e "\033[1;32m***************************************************************$*\033[m"
}

function update_system_packages() {
    install_log "Updating sources"
    sudo apt-get update || install_error "Unable to update package list"
}

if [ "${install_cert:-}" = 1 ]; then
    source="mkcert"
    wget -q ${UPDATE_URL}installers/${source}.sh -O /tmp/raspap_${source}
    source /var/www/html/installers/raspap_${source}.sh
    install_certificate
else
    source="common"
    wget -q ${UPDATE_URL}installers/${source}.sh -O /tmp/raspap_${source}
    source /var/www/html/installers/raspap_${source}.sh
    install_raspap
fi

