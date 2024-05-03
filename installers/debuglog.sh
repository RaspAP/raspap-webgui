#!/bin/bash
#
# RaspAP Debug log generator
# Author: @billz <billzimmerman@gmail.com>
# Author URI: https://github.com/billz/
# License: GNU General Public License v3.0
# License URI: https://github.com/raspap/raspap-webgui/blob/master/LICENSE
#
# Typically used in an ajax call from the RaspAP UI, this utility may also
# be invoked directly to generate a detailed system debug log.
#
# Usage: debuglog.sh [options]
#
# OPTIONS:
# -w, --write       Writes the debug log to /tmp (useful if sourced directly)
# -i, --install     Overrides the default RaspAP install location (/var/www/html)
#
# NOTE
# Detailed system information is gathered for debugging and/or troubleshooting
# purposes only. Passwords or other sensitive data are NOT included.
#
# You are not obligated to bundle the LICENSE file with your RaspAP projects as long
# as you leave these references intact in the header comments of your source files.

# Exit on error
set -o errexit
# Exit on error inside functions
set -o errtrace
# Turn on traces, disabled by default
# set -o xtrace

# Set defaults
readonly RASPAP_DIR="/etc/raspap"
readonly DNSMASQ_D_DIR="/etc/dnsmasq.d"
readonly RASPAP_DHCDPCD="/etc/dhcpcd.conf"
readonly RASPAP_HOSTAPD="$RASPAP_DIR/hostapd.ini"
readonly RASPAP_PROVIDER="$RASPAP_DIR/provider.ini"
readonly RASPAP_LOGPATH="/tmp"
readonly RASPAP_LOGFILE="$RASPAP_LOGPATH/raspap_debug.log"
readonly RASPAP_DEBUG_VERSION="1.0"
readonly PREAMBLE="
   888888ba                              .d888888   888888ba
   88     8b                            d8     88   88     8b
  a88aaaa8P' .d8888b. .d8888b. 88d888b. 88aaaaa88a a88aaaa8P
   88    8b. 88    88 Y8ooooo. 88    88 88     88   88
   88     88 88.  .88       88 88.  .88 88     88   88
   dP     dP  88888P8  88888P  88Y888P  88     88   dP
                               88
                               dP     Debug Log Generator $RASPAP_DEBUG_VERSION

This process collects debug and troubleshooting information about your RaspAP installation.
It is intended to assist users with a self-diagnosis of their installations, as well as 
provide useful information as a starting point for others to assist with troubleshooting.
Debug log information contains the RaspAP version, current state and configuration of AP
related services, relevant installed package versions, Linux kernel version and local
networking configuration details. 

If you wish to share your debug info, paste the output to one of the following:
  https://pastebin.com/
  https://paste.ubuntu.com/

Please do NOT paste the log in its entirety to RaspAP's discussions, issues or other
support channels. Use one of the above links instead.

DISCLAIMER: This log DOES contain details about your system, including networking
settings. However, NO passwords or other sensitive data are included in the debug output.
========================================================================================"

function _main() {
    _parse_params "$@"
    _initialize
    _output_preamble
    _generate_log
}

function _parse_params() {
    # default option values
    install_dir="/var/www/html"
    writelog=0

    while :; do
        case "${1-}" in
            -w|--write)
            writelog=1
            ;;
            -i|--install)
            install_dir="$2"
            shift
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

function _generate_log() {
    _log_write "Debug log generation started at $(date)"
    _system_info
    _packages_info
    _raspap_info
    _usb_info
    _rfkill_info
    _wpa_info
    _dnsmasq_info
    _dhcpcd_info
    _interface_info
    _routing_info
    _iw_dev_info
    _iw_reg_info
    _systemd_info
    _log_write "RaspAP debug log generation complete."
    exit 0
}

# Fetches hardware, OS, uptime & used memory
function _system_info() {
    local model=$(tr -d '\0' < /proc/device-tree/model)
    local system_uptime=$(uptime | awk -F'( |,|:)+' '{if ($7=="min") m=$6; else {if ($7~/^day/){if ($9=="min") {d=$6;m=$8} else {d=$6;h=$8;m=$9}} else {h=$6;m=$7}}} {print d+0,"days,",h+0,"hours,",m+0,"minutes"}')
    local free_mem=$(free -m | awk 'NR==2{ total=$2 ; used=$3 } END { print used/total*100}')
    _log_separator "System Info"
    _log_write "Hardware: ${model}"
    _log_write "Detected OS: ${DESC} ${LONG_BIT}-bit"
    _log_write "Kernel: ${KERNEL}"
    _log_write "System Uptime: ${system_uptime}"
    _log_write "Memory Usage: ${free_mem}%"
}

# Fetch installed package versions
function _packages_info() {
    local php_version="Not present"
    local dnsmasq_version="Not present"
    local dhcpcd_version="Not present"
    local lighttpd_version="Not present"
    local vnstat_version="Not present"

    if [ -x "$(command -v php)" ]; then
        php_version=$(php -v | grep -oP "PHP \K[0-9]+\.[0-9]+.*")
    fi
    if [ -x "$(command -v dnsmasq)" ]; then
        dnsmasq_version=$(dnsmasq -v | grep -oP "Dnsmasq version \K[0-9]+\.[0-9]+")
    fi
    if [ -x "$(command -v dhcpcd)" ]; then
        dhcpcd_version=$(dhcpcd --version | grep -oP '\d+\.\d+\.\d+')
    fi
    if [ -x "$(command -v dhcpcd)" ]; then
        lighttpd_version=$(lighttpd -v | grep -oP '(\d+\.\d+\.\d+)')
    fi
    if [ -x "$(command -v dhcpcd)" ]; then
        vnstat_version=$(vnstat -v | grep -oP "vnStat \K[0-9]+\.[0-9]+")
    fi

    _log_separator "Installed Packages"
    _log_write "PHP Version: ${php_version}"
    _log_write "Dnsmasq Version: ${dnsmasq_version}"
    _log_write "dhcpcd Version: ${dhcpcd_version}"
    _log_write "lighttpd Version: ${lighttpd_version}"
    _log_write "vnStat Version: ${vnstat_version}"
}

# Outputs installed RaspAP version & settings 
function _raspap_info() {
    local version="Not present"
    local hostapd_ini="Not present"
    local provider_ini="Not present"

    if [ -f ${install_dir}/includes/defaults.php ]; then
        version=$(grep "RASPI_VERSION" $install_dir/includes/defaults.php | awk -F"'" '{print $4}')
    fi
    if [ -f ${RASPAP_HOSTAPD} ]; then
        hostapd_ini=$(cat ${RASPAP_HOSTAPD})
    fi
    if [ -f ${RASPAP_PROVIDER} ]; then
        provider_ini=$(cat ${RASPAP_PROVIDER})
    fi

    _log_separator "RaspAP Install"
    _log_write "RaspAP Version: ${version}"
    _log_write "RaspAP Installation Directory: ${install_dir}"
    _log_write "RaspAP hostapd.ini contents:\n${hostapd_ini}"
    _log_write "RaspAP provider.ini: ${provider_ini}"
}

function _usb_info() {
    local stdout=$(lsusb)
    _log_separator "USB Devices"
    _log_write "${stdout}"
}

function _rfkill_info() {
    local stdout=$(rfkill list)
     _log_separator "rfkill"
     _log_write "${stdout}"
}

function _wpa_info() {
    local stdout=$(wpa_cli status)
    _log_separator "WPA Supplicant"
    _log_write "${stdout}"
}

# Iterates the contents of RaspAP's 090_*.conf files in dnsmasq.d
function _dnsmasq_info() {
    local stdout=$(ls -h ${DNSMASQ_D_DIR}/090_*.conf)
    local contents
    _log_separator "Dnsmasq Contents"
    _log_write "${stdout}"
    IFS= # set IFS to empty
    if [ -d "${DNSMASQ_D_DIR}" ]; then
        for file in "${DNSMASQ_D_DIR}"/090_*.conf; do
            if [ -f "$file" ]; then
                contents+="\n$file contents:\n"
                contents+="$(cat $file)"
                contents="${contents}$\n"
            fi
        done
        _log_write $contents
    else
        _log_write "Not found: ${DNSMASQ_D_DIR}"
    fi
}

function _dhcpcd_info() {
    _log_separator "Dhcpcd Contents"
    if [ -f "${RASPAP_DHCDPCD}" ]; then
        local stdout=$(cat ${RASPAP_DHCDPCD});
        _log_write "${stdout}"

    else
        _log_write "${RASPAP_DHCDPCD} not present"
    fi
}

function _interface_info() {
    local stdout=$(ip a)
    _log_separator "Interfaces"
    _log_write "${stdout}"
}

function _iw_reg_info() {
     local stdout=$(iw reg get)
    _log_separator "IW Regulatory Info"
    _log_write "${stdout}"
}

function _iw_dev_info() {
     local stdout=$(iw dev)
    _log_separator "IW Device Info"
    _log_write "${stdout}"
}

function _routing_info() {
    local stdout=$(ip route)
    _log_separator "Routing Table"
    _log_write "${stdout}"
}

# Status of systemd services
function _systemd_info() {
    local SYSTEMD_SERVICES=(
        "hostapd"
        "dnsmasq"
        "dhcpcd"
        "systemd-networkd"
        "wg-quick@wg0"
        "openvpn-client@client"
        "lighttpd")
    _log_separator "Systemd Services"
    for i in "${!SYSTEMD_SERVICES[@]}"; do
        _log_write "${SYSTEMD_SERVICES[$i]} status:"
        stdout=$(systemctl status "${SYSTEMD_SERVICES[$i]}" || echo "")
        _log_write "${stdout}\n"
    done
}

function _output_preamble() {
    _log_write "${PREAMBLE}\n"
}

# Fetches host Linux distribution details
function _get_linux_distro() {
    if type lsb_release >/dev/null 2>&1; then # linuxbase.org
        OS=$(lsb_release -si)
        RELEASE=$(lsb_release -sr)
        CODENAME=$(lsb_release -sc)
        DESC=$(lsb_release -sd)
        LONG_BIT=$(getconf LONG_BIT)
    elif [ -f /etc/os-release ]; then # freedesktop.org
        . /etc/os-release
        OS=$ID
        RELEASE=$VERSION_ID
        CODENAME=$VERSION_CODENAME
        DESC=$PRETTY_NAME
    else
        OS="Unsupported Linux distribution"
    fi
    KERNEL=$(uname -a)
}

function _initialize() {
    if [ -e "${RASPAP_LOGFILE}" ] && [ "${writelog}" = 1 ]; then
        rm "${RASPAP_LOGFILE}"
    fi
    _get_linux_distro
}

function _log_separator(){
    local separator=""
    local msg="$1"
    local length=${#msg}
    _log_write "\n$1"
    for ((i=1; i<=length; i++)); do
         separator+="="
    done
    _log_write $separator
}

function _log_write() {
    if [ "${writelog}" = 1 ]; then
        echo -e "${@}" | tee -a $RASPAP_LOGFILE
    else
        echo -e "${@}"
    fi
}

_main "$@"

