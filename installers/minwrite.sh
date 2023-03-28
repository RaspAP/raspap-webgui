#!/bin/bash
#
# RaspAP minimal microSD card write operation
# Original author: @zbchristian
# Original source URI: https://github.com/RaspAP/raspap-tools
# Modified by: @billz <billzimmerman@gmail.com>
# License: GNU General Public License v3.0
# License URI: https://github.com/raspap/raspap-webgui/blob/master/LICENSE
#
# Limits microSD card write operation to a minimum by moving temporary and log files to RAM.
# Several packages are removed and the default logging service is replaced.
# The file system is still in read/write mode, so RaspAP settings can be saved.
# Write access can be checked with "iotop -aoP".
# Remaining access originates mainly from the ext4 journal update (process jbd2).

# Exit on error
set -o errexit
# Exit on error inside functions
set -o errtrace

# Set defaults
readonly bootcmd="/boot/cmdline.txt"

function _install_minwrite() {
    _display_welcome
    _begin_install
    _remove_packages
    _disable_services
    _install_logger
    _disable_swap
    _move_directories
    _install_complete
}

function _dirs2tmpfs() {
  for dir in "${dirs[@]}"; do
    echo "Moving $dir to RAM"
    if ! grep -q " $dir " /etc/fstab; then
      echo "tmpfs $dir tmpfs  nosuid,nodev 0 0" | sudo tee -a /etc/fstab
    fi
  done 
}

# Determines host Linux distribution details
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
        _install_status 1 "Unsupported Linux distribution"
    fi
}

function _begin_install() {
    _install_log "Modify the OS to minimize microSD card write operation"
    _get_linux_distro
    echo "Detected OS: ${DESC}"
}

function _remove_packages() {
    _install_log "Removing packages"
    echo -e "The following packages will be removed: ${ANSI_YELLOW}dphys-swapfile logrotate${ANSI_RESET}"
    echo -n "Proceed? [Y/n]: "
    if [ "$assume_yes" == 0 ]; then
        read answer < /dev/tty
        if [ "$answer" != "${answer#[Nn]}" ]; then
            _install_status 0 "(Skipped)"
        else
            sudo apt-get -y remove --purge dphys-swapfile logrotate || _install_status 1 "Unable to remove packages"
            sudo apt-get -y autoremove --purge || _install_status 1 "Unable to autoremove packages"
            _install_status 0
        fi
    else
        echo "(Skipped)"
    fi
}

function _disable_services() {
    _install_log "Disabling services"
    echo -e "The following services will be disabled: ${ANSI_YELLOW}bootlogd.service bootlogs console-setup apt-daily${ANSI_RESET}" 
    echo -n "Proceed? [Y/n]: "
    if [ "$assume_yes" == 0 ]; then
        read answer < /dev/tty
        if [ "$answer" != "${answer#[Nn]}" ]; then
            _install_status 0 "(Skipped)"
        else
            sudo systemctl unmask bootlogd.service || _install_status 2 "Service bootlogd.service does not exist"
            sudo systemctl disable bootlogs || _install_status 2 "Service bootlogs does not exist"
            sudo systemctl disable apt-daily.service apt-daily.timer apt-daily-upgrade.timer apt-daily-upgrade.service || _install_status 2 "Service apt-daily does not exist"
            _install_status 0
        fi
    else
        echo "(Skipped)"
    fi
}

function _install_logger() {
    _install_log "Installing new logger"
    echo -e "The following new logger will be installed: ${ANSI_YELLOW}busybox-syslogd${ANSI_RESET}"
    echo -n "Proceed? [Y/n]: "
    if [ "$assume_yes" == 0 ]; then
        read answer < /dev/tty
        if [ "$answer" != "${answer#[Nn]}" ]; then
            _install_status 0 "(Skipped)"
        else
            sudo apt-get -y install busybox-syslogd || _install_status 1 "Unable to install busybox-syslogd"
            sudo dpkg --purge rsyslog || _install_status 1 "Unable to purge rsyslog"
            _install_status 0
        fi
    else
        echo "(Skipped)"
    fi
}

function _disable_swap() {
    _install_log "Modifying boot options to disable swap and filesystem check"
    echo "The noswap option will be written to ${bootcmd}"
    echo -n "Proceed? [Y/n]: "
    if [ "$assume_yes" == 0 ]; then
        read answer < /dev/tty
        if [ "$answer" != "${answer#[Nn]}" ]; then
            _install_status 0 "(Skipped)"
        else
            if ! grep -q "noswap" $bootcmd; then
                sudo sed -i '1 s/$/ fsck.mode=skip noswap/' $bootcmd || _install_status 1 "Unable to write to ${bootcmd}"
                echo "Modified ${bootcmd} with noswap option"
                _install_status 0
            fi
        fi
    else
        echo "(Skipped)"
    fi
}

function _move_directories() {
    _install_log "Add tmpfs entries to /etc/fstab"
    # move directories to RAM
    dirs=( "/tmp" "/var/log" "/var/tmp" "/var/lib/misc" "/var/cache")
    # special dirs used by vnstat and php
    dirs+=( "/var/lib/vnstat" "/var/php/sessions" )
    echo "The following directories will be moved to RAM:"
    echo -e "${ANSI_YELLOW}${dirs[*]}${ANSI_RESET}"
    echo -n "Proceed? [Y/n]: "
    if [ "$assume_yes" == 0 ]; then
        read answer < /dev/tty
        if [ "$answer" != "${answer#[Nn]}" ]; then
            _install_status 0 "(Skipped)"
        else
            _dirs2tmpfs || _install_status 1 "Unable to call dirs2tmpfs"
            _install_status 0
        fi
    else
        echo "(Skipped)"
    fi
}

function _install_complete() {
    _install_log "Installation completed"
    echo -e "${ANSI_RED}The system needs to be rebooted as a final step.${ANSI_RESET}"
    echo -n "Reboot now? [Y/n]: "
    if [ "$assume_yes" == 0 ]; then
        read answer < /dev/tty
        if [ "$answer" != "${answer#[Nn]}" ]; then
            echo "Installation reboot aborted."
            exit 0
        fi
        echo "Rebooting..."
        sudo reboot || _install_status 1 "Unable to execute reboot"
    fi
}

