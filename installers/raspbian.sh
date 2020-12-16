#!/bin/bash
#
# RaspAP Quick Installer
# Author: @billz <billzimmerman@gmail.com>
# Author URI: https://github.com/billz/
# License: GNU General Public License v3.0
# License URI: https://github.com/billz/raspap-webgui/blob/master/LICENSE
#
# Usage: raspbian.sh options
#
# Installs an instance of RaspAP.
#
# OPTIONS:
# -y, --yes, --assume-yes           Assume "yes" as answer to all prompts and run non-interactively
# -c, --cert, --certficate          Installs mkcert and generates an SSL certificate for lighttpd
# -o, --openvpn <flag>              Used with -y, --yes, sets OpenVPN install option (0=no install)
# -a, --adblock <flag>              Used with -y, --yes, sets Adblock install option (0=no install)
# -r, --repo, --repository <name>   Overrides the default GitHub repo (billz/raspap-webgui)
# -b, --branch <name>               Overrides the default git branch (master)
# -h, --help                        Outputs usage notes and exits
# -u, --upgrade                     Upgrades an existing installation to the latest release version
# -v, --version                     Outputs release info and exits
#
# Depending on options passed to the installer, ONE of the following
# additional shell scripts will be downloaded and sourced:
#
# https://raw.githubusercontent.com/billz/raspap-webgui/master/installers/common.sh
# - or -
# https://raw.githubusercontent.com/billz/raspap-webgui/master/installers/mkcert.sh
#
# You are not obligated to bundle the LICENSE file with your RaspAP projects as long
# as you leave these references intact in the header comments of your source files.

set -eo pipefail

function _main() {
    # set defaults
    repo="billz/raspap-webgui" # override with -r, --repo option

    _parse_params "$@"
    _setup_colors
    _log_output
    _load_installer
}

function _parse_params() {
    # default flag values
    assume_yes=0
    upgrade=0
    ovpn_option=1
    adblock_option=1

    while :; do
        case "${1-}" in
            -y|--yes|--assume-yes)
            assume_yes=1
            apt_option="-y"
            ;;
            -o|--openvpn)
            ovpn_option="$2"
            shift
            ;;
            -a|--adblock)
            adblock_option="$2"
            shift
            ;;
            -c|--cert|--certificate)
            install_cert=1
            ;;
            -r|--repo|--repository)
            repo="$2"
            shift
            ;;
            -b|--branch)
            branch="$2"
            shift
            ;;
            -h|--help)
            _usage
            ;;
            -u|--upgrade)
            upgrade=1
            ;;
            -v|--version)
            _version
            ;;
            -*|--*)
            echo "Unknown option: $1"
            _usage
            exit 1
            ;;
            *)
            break
            ;;
        esac
        shift
    done
}

function _setup_colors() {
    ANSI_RED="\033[0;31m"
    ANSI_GREEN="\033[0;32m"
    ANSI_YELLOW="\033[0;33m"
    ANSI_RASPBERRY="\033[0;35m"
    ANSI_ERROR="\033[1;37;41m"
    ANSI_RESET="\033[m"
}

function _log_output() {
    readonly LOGFILE_PATH="/tmp"
    exec > >(tee -i $LOGFILE_PATH/raspap_install.log)
    exec 2>&1
}

function _usage() {
    cat << EOF
Usage: raspbian.sh options

Installs an instance of RaspAP.

OPTIONS:
-y, --yes, --assume-yes             Assumes "yes" as an answer to all prompts
-c, --cert, --certificate           Installs an SSL certificate for lighttpd
-o, --openvpn <flag>                Used with -y, --yes, sets OpenVPN install option (0=no install)
-a, --adblock <flag>                Used with -y, --yes, sets Adblock install option (0=no install)
-r, --repo, --repository <name>     Overrides the default GitHub repo (billz/raspap-webgui)
-b, --branch <name>                 Overrides the default git branch (latest release)
-u, --upgrade                       Upgrades an existing installation to the latest release version
-v, --version                       Outputs release info and exits
-h, --help                          Outputs usage notes and exits

Examples:
    Run locally specifying GitHub repo and branch:
    raspbian.sh --repo foo/bar --branch my/branch

    Run locally requesting release info:
    raspbian.sh --version

    Invoke installer remotely, run non-interactively with option flags:
    curl -sL https://install.raspap.com | bash -s -- --yes --openvpn 1 --adblock 0

EOF
    exit
}

function _version() {
    _get_release
    echo -e "RaspAP v${RASPAP_LATEST} - Simple AP setup & WiFi management for Debian-based devices"
    exit
}

# Outputs a welcome message
function _display_welcome() {
    echo -e "${ANSI_RASPBERRY}\n"
    echo -e " 888888ba                              .d888888   888888ba"
    echo -e " 88     8b                            d8     88   88     8b"
    echo -e "a88aaaa8P' .d8888b. .d8888b. 88d888b. 88aaaaa88a a88aaaa8P"
    echo -e " 88    8b. 88    88 Y8ooooo. 88    88 88     88   88"
    echo -e " 88     88 88.  .88       88 88.  .88 88     88   88"
    echo -e " dP     dP  88888P8  88888P  88Y888P  88     88   dP"
    echo -e "                             88"
    echo -e "                             dP       version ${RASPAP_LATEST}"
    echo -e "${ANSI_GREEN}"
    echo -e "The Quick Installer will guide you through a few easy steps${ANSI_RESET}\n\n"
}

# Fetch latest release from GitHub API
function _get_release() {
    readonly RASPAP_LATEST=$(curl -s "https://api.github.com/repos/$repo/releases/latest" | grep -Po '"tag_name": "\K.*?(?=")' )
}

# Outputs a RaspAP Install log line
function _install_log() {
    echo -e "${ANSI_GREEN}RaspAP Install: $1${ANSI_RESET}"
}

# Outputs a RaspAP divider
function _install_divider() {
    echo -e "\033[1;32m***************************************************************$*\033[m"
}

# Outputs a RaspAP status indicator
function _install_status() {
    case $1 in
        0)
        echo -e "[$ANSI_GREEN \U2713 ok $ANSI_RESET] $2"
        ;;
        1)
        echo -e "[$ANSI_RED \U2718 error $ANSI_RESET] $ANSI_ERROR $2 $ANSI_RESET"
        ;;
        2)
        echo -e "[$ANSI_YELLOW \U26A0 warning $ANSI_RESET] $2"
        ;;
    esac
}

function _update_system_packages() {
    _install_log "Updating sources"
    sudo apt-get update || _install_status 1 "Unable to update package list"
}

# Fetch required installer functions
function _load_installer() {
    # fetch latest release tag
    _get_release

    # assign default branch if not defined with -b, --branch option
    if [ -z ${branch} ]; then
        branch=$RASPAP_LATEST
    fi

    UPDATE_URL="https://raw.githubusercontent.com/$repo/$branch/"
    if [ "${install_cert:-}" = 1 ]; then
        source="mkcert"
        wget -q ${UPDATE_URL}installers/${source}.sh -O /tmp/raspap_${source}.sh
        source /tmp/raspap_${source}.sh && rm -f /tmp/raspap_${source}.sh
        _install_certificate || _install_status 1 "Unable to install certificate"
    else
        source="common"
        wget -q ${UPDATE_URL}installers/${source}.sh -O /tmp/raspap_${source}.sh
        source /tmp/raspap_${source}.sh && rm -f /tmp/raspap_${source}.sh
        _install_raspap || _install_status 1 "Unable to install RaspAP"
    fi
}

_main "$@"

