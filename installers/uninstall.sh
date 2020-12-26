#!/bin/bash
#
# RaspAP uninstall functions
# Author: @billz <billzimmerman@gmail.com>
# License: GNU General Public License v3.0
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
readonly raspap_dir="/etc/raspap"
readonly raspap_user="www-data"
readonly raspap_sudoers="/etc/sudoers.d/090_raspap"
readonly raspap_default="/etc/dnsmasq.d/090_raspap.conf"
readonly raspap_wlan0="/etc/dnsmasq.d/090_wlan0.conf"
readonly raspap_sysctl="/etc/sysctl.d/90_raspap.conf"
readonly raspap_adblock="/etc/dnsmasq.d/090_adblock.conf"
readonly raspap_network="/etc/systemd/network/"
readonly rulesv4="/etc/iptables/rules.v4"
webroot_dir="/var/www/html"

# Determines host Linux distrubtion details
function _get_linux_distro() {
    if type lsb_release >/dev/null 2>&1; then # linuxbase.org
        OS=$(lsb_release -si)
        RELEASE=$(lsb_release -sr)
        CODENAME=$(lsb_release -sc)
        DESC=$(lsb_release -sd)
    elif [ -f /etc/os-release ]; then # freedesktop.org
        . /etc/os-release
        OS=$ID
        RELEASE=$VERSION_ID
        CODENAME=$VERSION_CODENAME
        DESC=$PRETTY_NAME
    else
        _install_error "Unsupported Linux distribution"
    fi
}

# Sets php package option based on Linux version, abort if unsupported distro
function _set_php_package() {
    case $RELEASE in
        18.04|19.10) # Ubuntu Server
            php_package="php7.4-cgi"
            phpcgiconf="/etc/php/7.4/cgi/php.ini" ;;
        10*)
            php_package="php7.3-cgi"
            phpcgiconf="/etc/php/7.3/cgi/php.ini" ;;
        9*)
            php_package="php7.0-cgi"
            phpcgiconf="/etc/php/7.0/cgi/php.ini" ;;
    esac
}

# Outputs a RaspAP Install log line
function _install_log() {
    echo -e "\033[1;32mRaspAP Uninstall: $*\033[m"
}

# Outputs a RaspAP Install Error log line and exits with status code 1
function _install_error() {
    echo -e "\033[1;37;41mRaspAP Uninstall Error: $*\033[m"
    exit 1
}

# Checks to make sure uninstallation info is correct
function _config_uninstallation() {
    _install_log "Configure uninstall of RaspAP"
    _get_linux_distro
    echo "Detected ${DESC}" 
    echo "RaspAP install directory: ${raspap_dir}"
    echo -n "Lighttpd install directory: ${webroot_dir}? [Y/n]: "
    read answer
    if [ "$answer" != "${answer#[Nn]}" ]; then
        read -e -p "Enter alternate lighttpd directory: " -i "/var/www/html" webroot_dir
    fi
    echo "Uninstall from lighttpd directory: ${webroot_dir}"
    echo -n "Uninstall RaspAP with these values? [Y/n]: "
    read answer
    if [[ "$answer" != "${answer#[Nn]}" ]]; then
        echo "Installation aborted."
        exit 0
    fi
}

# Checks for/restore backup files
function _check_for_backups() {
    if [ -d "$raspap_dir/backups" ]; then
        if [ -f "$raspap_dir/backups/hostapd.conf" ]; then
            echo -n "Restore the last hostapd configuration file? [y/N]: "
            read answer
            if [[ $answer -eq 'y' ]]; then
                sudo cp "$raspap_dir/backups/hostapd.conf" /etc/hostapd/hostapd.conf
            fi
        fi
        if [ -f "$raspap_dir/backups/dnsmasq.conf" ]; then
            echo -n "Restore the last dnsmasq configuration file? [y/N]: "
            read answer
            if [[ $answer -eq 'y' ]]; then
                sudo cp "$raspap_dir/backups/dnsmasq.conf" /etc/dnsmasq.conf
            fi
        fi
        if [ -f "$raspap_dir/backups/dhcpcd.conf" ]; then
            echo -n "Restore the last dhcpcd.conf file? [y/N]: "
            read answer
            if [[ $answer -eq 'y' ]]; then
                sudo cp "$raspap_dir/backups/dhcpcd.conf" /etc/dhcpcd.conf
            fi
        fi
        if [ -f "$raspap_dir/backups/php.ini" ] && [ -f "$phpcgiconf" ]; then
            echo -n "Restore the last php.ini file? [y/N]: "
            read answer
            if [[ $answer -eq 'y' ]]; then
                sudo cp "$raspap_dir/backups/php.ini" "$phpcgiconf"
            fi
        fi
    fi
}

# Removes RaspAP directories
function _remove_raspap_directories() {
    _install_log "Removing RaspAP Directories"
    if [ ! -d "$raspap_dir" ]; then
        _install_error "RaspAP Configuration directory not found. Exiting."
    fi

    if [ ! -d "$webroot_dir" ]; then
        _install_error "RaspAP Installation directory not found. Exiting."
    fi
    sudo rm -rf "$webroot_dir"/* || _install_error "Unable to remove $webroot_dir"
    sudo rm -rf "$raspap_dir" || _install_error "Unable to remove $raspap_dir"
}

# Removes raspapd.service
function _remove_raspap_service() {
    _install_log "Removing raspapd.service"
    if [ -f /lib/systemd/system/raspapd.service ]; then
        sudo rm /lib/systemd/system/raspapd.service || _install_error "Unable to remove raspap.service file"
    fi
    sudo systemctl daemon-reload
    sudo systemctl disable raspapd.service || _install_error "Failed to disable raspap.service"
    echo "Done."
}

# Restores networking config to pre-install defaults
function _restore_networking() {
    _install_log "Restoring networking config to pre-install defaults"
    echo "Disabling IP forwarding in $raspap_sysctl"
    sudo rm "$raspap_sysctl" || _install_error "Unable to remove $raspap_sysctl"
    sudo /etc/init.d/procps restart || _install_error "Unable to execute procps"
    echo "Checking iptables rules"
    rules=(
    "-A POSTROUTING -j MASQUERADE"
    "-A POSTROUTING -s 192.168.50.0/24 ! -d 192.168.50.0/24 -j MASQUERADE"
    )
    for rule in "${rules[@]}"; do
        if grep -- "$rule" $rulesv4 > /dev/null; then
            rule=$(sed -e 's/^\(-A POSTROUTING\)/-t nat -D POSTROUTING/' <<< $rule)
            echo "Removing rule: ${rule}"
            sudo iptables $rule || _install_error "Unable to execute iptables"
            removed=true
        fi
    done
    # Persist rules if removed
    if [ "$removed" = true ]; then
        echo "Removing persistent iptables rules"
        sudo iptables-save | sudo tee $rulesv4 > /dev/null || _install_error "Unable to execute iptables-save"
    fi
    echo "Done."
    # Remove dnsmasq and bridge configs
    echo "Removing 090_raspap.conf from dnsmasq"
    if [ -f $raspap_default ]; then
        sudo rm "$raspap_default" || _install_error "Unable to remove $raspap_default"
    fi
    echo "Removing 090_wlan0.conf from dnsmasq"
    if [ -f $raspap_wlan0 ]; then
        sudo rm "$raspap_wlan0" || _install_error "Unable to remove $raspap_wlan0"
    fi
    echo "Removing raspap bridge configurations"
    sudo rm "$raspap_network"/raspap* || _install_error "Unable to remove bridge config"
    if [ -f $raspap_adblock ]; then
        echo "Removing raspap adblock configuration"
        sudo rm "$raspap_adblock" || _install_error "Unable to remove adblock config"
    fi
}

# Removes installed packages
function _remove_installed_packages() {
    _install_log "Removing installed packages"
    _set_php_package
    if [ ${OS,,} = "debian" ] || [ ${OS,,} = "ubuntu" ]; then
        dhcpcd_package="dhcpcd5"
    else
        dhcpcd_package="dnsmasq"
    fi
    echo -n "Remove the following installed packages? lighttpd hostapd iptables-persistent $php_package $dhcpcd_package vnstat qrencode [y/N]: "
    read answer
    if [ "$answer" != 'n' ] && [ "$answer" != 'N' ]; then
        echo "Removing packages."
        sudo apt-get remove lighttpd hostapd iptables-persistent $php_package $dhcpcd_package vnstat qrencode || _install_error "Unable to remove installed packages"
        sudo apt-get autoremove || _install_error "Unable to run apt autoremove"
    else
        echo "Leaving packages installed."
    fi
}

# Removes www-data from sudoers
function _remove_sudoers() {
    _install_log "Removing sudoers permissions"
    echo "Removing ${raspap_sudoers}" 
    sudo rm "$raspap_sudoers" || _install_error "Unable to remove $raspap_sudoers"
    echo "Done."
}

function _uninstall_complete() {
    _install_log "Uninstall completed"
    echo "It is recommended that you reboot your system as a final step."
}

function _remove_raspap() {
    _config_uninstallation
    _check_for_backups
    _remove_raspap_service
    _restore_networking
    _remove_raspap_directories
    _remove_installed_packages
    _remove_sudoers
    _uninstall_complete
}

_remove_raspap
