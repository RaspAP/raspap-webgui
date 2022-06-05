#!/bin/bash
#
# RaspAP installation functions
# Author: @billz <billzimmerman@gmail.com>
# Author URI: https://github.com/billz/
# License: GNU General Public License v3.0
# License URI: https://github.com/raspap/raspap-webgui/blob/master/LICENSE

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
readonly raspap_adblock="/etc/dnsmasq.d/090_adblock.conf"
readonly raspap_sysctl="/etc/sysctl.d/90_raspap.conf"
readonly raspap_network="$raspap_dir/networking/"
readonly raspap_router="/etc/lighttpd/conf-available/50-raspap-router.conf"
readonly rulesv4="/etc/iptables/rules.v4"
readonly notracking_url="https://raw.githubusercontent.com/notracking/hosts-blocklists/master/"
webroot_dir="/var/www/html"

if [ "$insiders" == 1 ]; then
    repo="RaspAP/raspap-insiders"
    branch=${RASPAP_INSIDERS_LATEST}
fi
git_source_url="https://github.com/$repo"

# NOTE: all the below functions are overloadable for system-specific installs
function _install_raspap() {
    _display_welcome
    _config_installation
    _update_system_packages
    _install_dependencies
    _enable_php_lighttpd
    _create_raspap_directories
    _check_for_old_configs
    _optimize_php
    _download_latest_files
    _change_file_ownership
    _create_hostapd_scripts
    _create_lighttpd_scripts
    _install_lighttpd_configs
    _move_config_file
    _default_configuration
    _configure_networking
    _prompt_install_adblock
    _prompt_install_openvpn
    _install_mobile_clients
    _prompt_install_wireguard
    _patch_system_files
    _install_complete
}

# search for optional installation files names install_feature_*.sh
function _install_mobile_clients() {
    if [ "$insiders" == 1 ]; then
        _install_log "Installing support for mobile data clients"
        for feature in $(ls $webroot_dir/installers/install_feature_*.sh) ; do
           source $feature
           f=$(basename $feature)
           func="_${f%.*}"
           if declare -f -F $func > /dev/null; then
                echo "Installing $func"
                $func || _install_status 1 "Unable to install feature ($func)"
            else
                _install_status 1 "Install file $f is missing install function $func"
           fi
       done
    fi
}

# Prompts user to set installation options
function _config_installation() {
    if [ "$upgrade" == 1 ]; then
        opt=(Upgrade Upgrading upgrade)
    else
        opt=(Install Installing installation)
    fi
    _install_log "Configure ${opt[2]}"
    _get_linux_distro
    echo "Detected OS: ${DESC}"
    echo "Using GitHub repository: ${repo} ${branch} branch"
    echo "Configuration directory: ${raspap_dir}"
    echo -n "lighttpd root: ${webroot_dir}? [Y/n]: "
    if [ "$assume_yes" == 0 ]; then
        read answer < /dev/tty
        if [ "$answer" != "${answer#[Nn]}" ]; then
            read -e -p < /dev/tty "Enter alternate lighttpd directory: " -i "/var/www/html" webroot_dir
        fi
    else
        echo -e
    fi
    echo "${opt[1]} lighttpd directory: ${webroot_dir}"
    if [ "$upgrade" == 1 ]; then
        echo "This will upgrade your existing install to version ${RASPAP_RELEASE}"
        echo "Your configuration will NOT be changed"
    fi
    echo -n "Complete ${opt[2]} with these values? [Y/n]: "
    if [ "$assume_yes" == 0 ]; then
        read answer < /dev/tty
        if [ "$answer" != "${answer#[Nn]}" ]; then
            echo "Installation aborted."
            exit 0
        fi
    else
        echo -e
    fi
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

# Sets php package option based on Linux version, abort if unsupported distro
function _set_php_package() {
    case $RELEASE in
        20.04|18.04|19.10|11*) # Ubuntu Server, Debian 11 & Armbian 22.05
            php_package="php7.4-cgi"
            phpcgiconf="/etc/php/7.4/cgi/php.ini" ;;
        10*|11*)
            php_package="php7.3-cgi"
            phpcgiconf="/etc/php/7.3/cgi/php.ini" ;;
        9*)
            php_package="php7.0-cgi"
            phpcgiconf="/etc/php/7.0/cgi/php.ini" ;;
        8)
            _install_status 1 "${DESC} and php5 are not supported. Please upgrade."
            exit 1 ;;
        *)
            _install_status 1 "${DESC} is unsupported. Please install on a supported distro."
            exit 1 ;;
    esac
}

# Runs a system software update to make sure we're using all fresh packages
function _install_dependencies() {
    _install_log "Installing required packages"
    _set_php_package
    if [ "$php_package" = "php7.4-cgi" ] && [ ${OS,,} = "ubuntu" ] && [[ ${RELEASE} =~ ^(18.04|19.10|11) ]]; then
        echo "Adding apt-repository ppa:ondrej/php"
        sudo apt-get install $apt_option software-properties-common || _install_status 1 "Unable to install dependency"
        sudo add-apt-repository $apt_option ppa:ondrej/php || _install_status 1 "Unable to add-apt-repository ppa:ondrej/php"
    else
        echo "PHP will be installed from the main deb sources list"
    fi
    if [ ${OS,,} = "debian" ] || [ ${OS,,} = "ubuntu" ]; then
        dhcpcd_package="dhcpcd5"
    fi
    # Set dconf-set-selections
    echo iptables-persistent iptables-persistent/autosave_v4 boolean true | sudo debconf-set-selections
    echo iptables-persistent iptables-persistent/autosave_v6 boolean true | sudo debconf-set-selections
    sudo apt-get install $apt_option lighttpd git hostapd dnsmasq iptables-persistent $php_package $dhcpcd_package vnstat qrencode || _install_status 1 "Unable to install dependencies"
    _install_status 0
}

# Enables PHP for lighttpd and restarts service for settings to take effect
function _enable_php_lighttpd() {
    _install_log "Enabling PHP for lighttpd"
    sudo lighttpd-enable-mod fastcgi-php    
    sudo service lighttpd force-reload
    sudo systemctl restart lighttpd.service || _install_status 1 "Unable to restart lighttpd"
}

# Verifies existence and permissions of RaspAP directory
function _create_raspap_directories() {
    if [ "$upgrade" == 1 ]; then
       if [ -f $raspap_dir/raspap.auth ]; then
            _install_log "Moving existing raspap.auth file to /tmp"
            sudo mv $raspap_dir/raspap.auth /tmp || _install_status 1 "Unable to backup raspap.auth to /tmp"
        fi
    fi

    _install_log "Creating RaspAP directories"
    if [ -d "$raspap_dir" ]; then
        sudo mv $raspap_dir "$raspap_dir.`date +%F-%R`" || _install_status 1 "Unable to move old '$raspap_dir' out of the way"
    fi
    sudo mkdir -p "$raspap_dir" || _install_status 1 "Unable to create directory '$raspap_dir'"

    # Create a directory for existing file backups.
    sudo mkdir -p "$raspap_dir/backups"

    # Create a directory to store networking configs
    echo "Creating $raspap_dir/networking"
    sudo mkdir -p "$raspap_dir/networking"
    # Copy existing dhcpcd.conf to use as base config
    echo "Adding /etc/dhcpcd.conf as base configuration"
    cat /etc/dhcpcd.conf | sudo tee -a /etc/raspap/networking/defaults > /dev/null
    echo "Changing file ownership of $raspap_dir"
    sudo chown -R $raspap_user:$raspap_user "$raspap_dir" || _install_status 1 "Unable to change file ownership for '$raspap_dir'"
}

# Generate hostapd logging and service control scripts
function _create_hostapd_scripts() {
    _install_log "Creating hostapd logging & control scripts"
    sudo mkdir $raspap_dir/hostapd || _install_status 1 "Unable to create directory '$raspap_dir/hostapd'"

    # Move logging shell scripts 
    sudo cp "$webroot_dir/installers/"*log.sh "$raspap_dir/hostapd" || _install_status 1 "Unable to move logging scripts"
    # Move service control shell scripts
    sudo cp "$webroot_dir/installers/"service*.sh "$raspap_dir/hostapd" || _install_status 1 "Unable to move service control scripts"
    # Make enablelog.sh and disablelog.sh not writable by www-data group.
    sudo chown -c root:root "$raspap_dir/hostapd/"*.sh || _install_status 1 "Unable change owner and/or group"
    sudo chmod 750 "$raspap_dir/hostapd/"*.sh || _install_status 1 "Unable to change file permissions"
    _install_status 0
}

# Generate lighttpd service control scripts
function _create_lighttpd_scripts() {
    _install_log "Creating lighttpd control scripts"
    sudo mkdir $raspap_dir/lighttpd || _install_status 1 "Unable to create directory '$raspap_dir/lighttpd"

    # Move service control shell scripts
    echo "Copying configport.sh to $raspap_dir/lighttpd"
    sudo cp "$webroot_dir/installers/"configport.sh "$raspap_dir/lighttpd" || _install_status 1 "Unable to move service control scripts"
    # Make configport.sh writable by www-data group
    echo "Changing file ownership"
    sudo chown -c root:root "$raspap_dir/lighttpd/"*.sh || _install_status 1 "Unable change owner and/or group"
    sudo chmod 750 "$raspap_dir/lighttpd/"*.sh || _install_status 1 "Unable to change file permissions"
    _install_status 0
}

# Copy extra config files required to configure lighttpd
function _install_lighttpd_configs() {
    _install_log "Copying lighttpd extra config files"

    # Copy config files
    echo "Copying 50-raspap-router.conf to /etc/lighttpd/conf-available"

    CONFSRC="$webroot_dir/config/50-raspap-router.conf"
    LTROOT=$(grep "server.document-root" /etc/lighttpd/lighttpd.conf | awk -F '=' '{print $2}' | tr -d " \"")

    # Compare values and get difference
    HTROOT=${webroot_dir/$LTROOT}

    # Remove trailing slash if present
    HTROOT=$(echo "$HTROOT" | sed -e 's/\/$//')

    # Substitute values
    awk "{gsub(\"/REPLACE_ME\",\"$HTROOT\")}1" $CONFSRC > /tmp/50-raspap-router.conf

    # Copy into place
    sudo cp /tmp/50-raspap-router.conf /etc/lighttpd/conf-available/ || _install_status 1 "Unable to copy lighttpd config file into place."

    # Link into conf-enabled
    echo "Creating link to /etc/lighttpd/conf-enabled"
    if ! [ -L $raspap_router ]; then
        echo "Existing 50-raspap-router.conf found. Unlinking."
        sudo unlink "/etc/lighttpd/conf-enabled/50-raspap-router.conf"
    fi
    echo "Linking 50-raspap-router.conf to /etc/lighttpd/conf-enabled/"
    sudo ln -s "/etc/lighttpd/conf-available/50-raspap-router.conf" "/etc/lighttpd/conf-enabled/50-raspap-router.conf" || _install_status 1 "Unable to symlink lighttpd config file (this is normal if the link already exists)."
    sudo systemctl restart lighttpd.service || _install_status 1 "Unable to restart lighttpd"
    _install_status 0
}

# Prompt to install ad blocking
function _prompt_install_adblock() {
    _install_log "Configure ad blocking (Beta)"
    echo -n "Install ad blocking and enable list management? [Y/n]: "
    if [ "$assume_yes" == 0 ]; then
        read answer < /dev/tty
        if [ "$answer" != "${answer#[Nn]}" ]; then
            _install_status 0 "(Skipped)"
        else
            _install_adblock
        fi
    elif [ "$adblock_option" == 1 ]; then
        _install_adblock
    else
        echo "(Skipped)"
    fi
}

# Download notracking adblock lists and enable option
function _install_adblock() {
    _install_log "Creating ad blocking base configuration (Beta)"
    if [ ! -d "$raspap_dir/adblock" ]; then
        echo "Creating $raspap_dir/adblock"
        sudo mkdir -p "$raspap_dir/adblock"
    fi
    if [ ! -f /tmp/hostnames.txt ]; then
        echo "Fetching latest hostnames list"
        wget ${notracking_url}hostnames.txt -q --show-progress --progress=bar:force -O /tmp/hostnames.txt 2>&1 \
            || _install_status 1 "Unable to download notracking hostnames"
    fi
    if [ ! -f /tmp/domains.txt ]; then
        echo "Fetching latest domains list"
        wget ${notracking_url}domains.txt -q --show-progress --progress=bar:force -O /tmp/domains.txt 2>&1 \
            || _install_status 1 "Unable to download notracking domains"
    fi
    echo "Adding blocklists to $raspap_dir/adblock"
    sudo cp /tmp/hostnames.txt $raspap_dir/adblock || _install_status 1 "Unable to move notracking hostnames"
    sudo cp /tmp/domains.txt $raspap_dir/adblock || _install_status 1 "Unable to move notracking domains"

    echo "Moving and setting permissions for blocklist update script"
    sudo cp "$webroot_dir/installers/"update_blocklist.sh "$raspap_dir/adblock" || _install_status 1 "Unable to move blocklist update script"

    # Make blocklists writable by www-data group, restrict update scripts to root
    sudo chown -c root:"$raspap_user" "$raspap_dir/adblock/"*.txt || _install_status 1 "Unable to change owner/group"
    sudo chown -c root:root "$raspap_dir/adblock/"*.sh || _install_status 1 "Unable to change owner/group"
    sudo chmod 750 "$raspap_dir/adblock/"*.sh || install_error "Unable to change file permissions"

    # Create 090_adblock.conf and write values to /etc/dnsmasq.d
    if [ ! -f "$raspap_adblock" ]; then
        echo "Adding 090_addblock.conf to /etc/dnsmasq.d"
        sudo touch "$raspap_adblock"
        echo "conf-file=$raspap_dir/adblock/domains.txt" | sudo tee -a "$raspap_adblock" > /dev/null || _install_status 1 "Unable to write to $raspap_adblock"
        echo "addn-hosts=$raspap_dir/adblock/hostnames.txt" | sudo tee -a "$raspap_adblock" > /dev/null || _install_status 1 "Unable to write to $raspap_adblock"
    fi

    # Remove dhcp-option=6 in dnsmasq.d/090_wlan0.conf to force local DNS resolution for DHCP clients
    echo "Enabling local DNS name resolution for DHCP clients"
    sudo sed -i '/dhcp-option=6/d' $raspap_wlan0 || _install_status 1 "Unable to modify $raspap_dnsmasq"

    echo "Enabling ad blocking management option"
    sudo sed -i "s/\('RASPI_ADBLOCK_ENABLED', \)false/\1true/g" "$webroot_dir/includes/config.php" || _install_status 1 "Unable to modify config.php"
    _install_status 0
}

# Prompt to install openvpn
function _prompt_install_openvpn() {
    _install_log "Configure OpenVPN support"
    echo -n "Install OpenVPN and enable client configuration? [Y/n]: "
    if [ "$assume_yes" == 0 ]; then
        read answer < /dev/tty
        if [ "$answer" != "${answer#[Nn]}" ]; then
            _install_status 0 "(Skipped)"
        else
            _install_openvpn
        fi
    elif [ "$ovpn_option" == 1 ]; then
        _install_openvpn
    else
        echo "(Skipped)"
    fi
}

# Prompt to install WireGuard
function _prompt_install_wireguard() {
    _install_log "Configure WireGuard support"
    echo -n "Install WireGuard and enable VPN tunnel configuration? [Y/n]: "
    if [ "$assume_yes" == 0 ]; then
        read answer < /dev/tty
        if [ "$answer" != "${answer#[Nn]}" ]; then
            _install_status 0 "(Skipped)"
        else
            _install_wireguard
        fi
    elif [ "$wg_option" == 1 ]; then
        _install_wireguard
    else
        echo "(Skipped)"
    fi
}

# Install Wireguard from the Debian unstable distro
function _install_wireguard() {
    _install_log "Configure WireGuard support"
    if [ "$OS" == "Debian" ]; then
        echo 'deb http://ftp.debian.org/debian buster-backports main' | sudo tee /etc/apt/sources.list.d/buster-backports.list || _install_status 1 "Unable to add Debian backports repo"
    fi
    echo "Installing wireguard from apt"
    sudo apt-get install -y wireguard || _install_status 1 "Unable to install wireguard"
    echo "Enabling wg-quick@wg0"
    sudo systemctl enable wg-quick@wg0 || _install_status 1 "Failed to enable wg-quick service"
    echo "Enabling WireGuard management option"
    sudo sed -i "s/\('RASPI_WIREGUARD_ENABLED', \)false/\1true/g" "$webroot_dir/includes/config.php" || _install_status 1 "Unable to modify config.php"
    _install_status 0
}

# Install openvpn and enable client configuration option
function _install_openvpn() {
    _install_log "Installing OpenVPN and enabling client configuration"
    echo "Adding packages via apt-get"
    sudo apt-get install -y openvpn || _install_status 1 "Unable to install openvpn"
    sudo sed -i "s/\('RASPI_OPENVPN_ENABLED', \)false/\1true/g" "$webroot_dir/includes/config.php" || _install_status 1 "Unable to modify config.php"
    echo "Enabling openvpn-client service on boot"
    sudo systemctl enable openvpn-client@client || _install_status 1 "Unable to enable openvpn-client daemon"
    _create_openvpn_scripts || _install_status 1 "Unable to create openvpn control scripts"
}

# Generate openvpn logging and auth control scripts
function _create_openvpn_scripts() {
    _install_log "Creating OpenVPN control scripts"
    sudo mkdir $raspap_dir/openvpn || _install_status 1 "Unable to create directory '$raspap_dir/openvpn'"

    # Move service auth control & logging shell scripts
    sudo cp "$webroot_dir/installers/"configauth.sh "$raspap_dir/openvpn" || _install_status 1 "Unable to move auth control script"
    sudo cp "$webroot_dir/installers/"openvpnlog.sh "$raspap_dir/openvpn" || _install_status 1 "Unable to move logging script"
    # Restrict script execution to root user
    sudo chown -c root:root "$raspap_dir/openvpn/"*.sh || _install_status 1 "Unable change owner and/or group"
    sudo chmod 750 "$raspap_dir/openvpn/"*.sh || _install_status 1 "Unable to change file permissions"
    _install_status 0
}

# Fetches latest files from github to webroot
function _download_latest_files() {
    if [ ! -d "$webroot_dir" ]; then
        sudo mkdir -p $webroot_dir || _install_status 1 "Unable to create new webroot directory"
    fi

    if [ -d "$webroot_dir" ]; then
        sudo mv $webroot_dir "$webroot_dir.`date +%F-%R`" || _install_status 1 "Unable to remove old webroot directory"
    fi

    _install_log "Cloning latest files from github"
    git clone --branch $branch --depth 1 -c advice.detachedHead=false $git_source_url /tmp/raspap-webgui || _install_status 1 "Unable to download files from github"
    sudo mv /tmp/raspap-webgui $webroot_dir || _install_status 1 "Unable to move raspap-webgui to web root"

    if [ "$upgrade" == 1 ]; then
        _install_log "Applying existing configuration to ${webroot_dir}/includes"
        sudo mv /tmp/config.php $webroot_dir/includes  || _install_status 1 "Unable to move config.php to ${webroot_dir}/includes"
        
        if [ -f /tmp/raspap.auth ]; then
            _install_log "Applying existing authentication file to ${raspap_dir}"
            sudo mv /tmp/raspap.auth $raspap_dir || _install_status 1 "Unable to restore authentification credentials file to ${raspap_dir}"
        fi
    fi

    _install_status 0
}

# Sets files ownership in web root directory
function _change_file_ownership() {
    if [ ! -d "$webroot_dir" ]; then
        _install_status 1 "Web root directory doesn't exist"
    fi

    _install_log "Changing file ownership in web root directory"
    sudo chown -R $raspap_user:$raspap_user "$webroot_dir" || _install_status 1 "Unable to change file ownership for '$webroot_dir'"
}

# Check for existing configuration files
function _check_for_old_configs() {
    if [ "$upgrade" == 1 ]; then
        _install_log "Moving existing configuration to /tmp"
        sudo mv $webroot_dir/includes/config.php /tmp || _install_status 1 "Unable to move config.php to /tmp"
    else
        _install_log "Backing up existing configs to ${raspap_dir}/backups"
        if [ -f /etc/network/interfaces ]; then
            sudo cp /etc/network/interfaces "$raspap_dir/backups/interfaces.`date +%F-%R`"
            sudo ln -sf "$raspap_dir/backups/interfaces.`date +%F-%R`" "$raspap_dir/backups/interfaces"
        fi

        if [ -f /etc/hostapd/hostapd.conf ]; then
            sudo cp /etc/hostapd/hostapd.conf "$raspap_dir/backups/hostapd.conf.`date +%F-%R`"
            sudo ln -sf "$raspap_dir/backups/hostapd.conf.`date +%F-%R`" "$raspap_dir/backups/hostapd.conf"
        fi

        if [ -f $raspap_default ]; then
            sudo cp $raspap_default "$raspap_dir/backups/090_raspap.conf.`date +%F-%R`"
            sudo ln -sf "$raspap_dir/backups/090_raspap.conf.`date +%F-%R`" "$raspap_dir/backups/090_raspap.conf"
        fi

        if [ -f $raspap_wlan0 ]; then
            sudo cp $raspap_wlan0 "$raspap_dir/backups/090_wlan0.conf.`date +%F-%R`"
            sudo ln -sf "$raspap_dir/backups/090_wlan0.conf.`date +%F-%R`" "$raspap_dir/backups/090_wlan0.conf"
        fi

        if [ -f /etc/dhcpcd.conf ]; then
            sudo cp /etc/dhcpcd.conf "$raspap_dir/backups/dhcpcd.conf.`date +%F-%R`"
            sudo ln -sf "$raspap_dir/backups/dhcpcd.conf.`date +%F-%R`" "$raspap_dir/backups/dhcpcd.conf"
        fi

        for file in /etc/systemd/network/raspap-*.net*; do
            if [ -f "${file}" ]; then
                filename=$(basename $file)
                sudo cp "$file" "${raspap_dir}/backups/${filename}.`date +%F-%R`"
                sudo ln -sf "${raspap_dir}/backups/${filename}.`date +%F-%R`" "${raspap_dir}/backups/${filename}"
            fi
        done
    fi
    _install_status 0
}

# Move configuration file to the correct location
function _move_config_file() {
    if [ ! -d "$raspap_dir" ]; then
        _install_status 1 "'$raspap_dir' directory doesn't exist"
    fi

    # Copy config file and make writable by www-data group
    _install_log "Moving configuration file to $raspap_dir"
    sudo cp "$webroot_dir"/raspap.php "$raspap_dir" || _install_status 1 "Unable to move files to '$raspap_dir'"
    sudo chown -c $raspap_user:"$raspap_user" "$raspap_dir"/raspap.php || _install_status 1 "Unable change owner and/or group"
}

# Set up default configuration
function _default_configuration() {
    if [ "$upgrade" == 0 ]; then
        _install_log "Applying default configuration to installed services"

        sudo cp $webroot_dir/config/hostapd.conf /etc/hostapd/hostapd.conf || _install_status 1 "Unable to move hostapd configuration file"
        sudo cp $webroot_dir/config/090_raspap.conf $raspap_default || _install_status 1 "Unable to move dnsmasq default configuration file"
        sudo cp $webroot_dir/config/090_wlan0.conf $raspap_wlan0 || _install_status 1 "Unable to move dnsmasq wlan0 configuration file"
        sudo cp $webroot_dir/config/dhcpcd.conf /etc/dhcpcd.conf || _install_status 1 "Unable to move dhcpcd configuration file"
        sudo cp $webroot_dir/config/defaults.json $raspap_network || _install_status 1 "Unable to move defaults.json settings"

        echo "Changing file ownership of ${raspap_network}/defaults.json"
        sudo chown $raspap_user:$raspap_user "$raspap_network"/defaults.json || _install_status 1 "Unable to change file ownership for defaults.json"

        echo "Checking for existence of /etc/dnsmasq.d"
        [ -d /etc/dnsmasq.d ] || sudo mkdir /etc/dnsmasq.d

        echo "Copying bridged AP config to /etc/systemd/network"
        sudo systemctl stop systemd-networkd
        sudo systemctl disable systemd-networkd
        sudo cp $webroot_dir/config/raspap-bridge-br0.netdev /etc/systemd/network/raspap-bridge-br0.netdev || _install_status 1 "Unable to move br0 netdev file"
        sudo cp $webroot_dir/config/raspap-br0-member-eth0.network /etc/systemd/network/raspap-br0-member-eth0.network || _install_status 1 "Unable to move br0 member file"

        echo "Copying primary RaspAP config to includes/config.php"
        if [ ! -f "$webroot_dir/includes/config.php" ]; then
            sudo cp "$webroot_dir/config/config.php" "$webroot_dir/includes/config.php"
        fi
        _install_status 0
    fi
}

# Install and enable RaspAP daemon
function _enable_raspap_daemon() {
    _install_log "Enabling RaspAP daemon"
    echo "Disable with: sudo systemctl disable raspapd.service"
    sudo cp $webroot_dir/installers/raspapd.service /lib/systemd/system/ || _install_status 1 "Unable to move raspap.service file"
    sudo systemctl daemon-reload
    sudo systemctl enable raspapd.service || _install_status 1 "Failed to enable raspap.service"
}

# Configure IP forwarding, set IP tables rules, prompt to install RaspAP daemon
function _configure_networking() {
    _install_log "Configuring networking"
    echo "Enabling IP forwarding"
    echo "net.ipv4.ip_forward=1" | sudo tee $raspap_sysctl > /dev/null || _install_status 1 "Unable to set IP forwarding"
    sudo sysctl -p $raspap_sysctl || _install_status 1 "Unable to execute sysctl"
    sudo /etc/init.d/procps restart || _install_status 1 "Unable to execute procps"

    echo "Checking iptables rules"
    rules=(
    "-A POSTROUTING -j MASQUERADE"
    "-A POSTROUTING -s 192.168.50.0/24 ! -d 192.168.50.0/24 -j MASQUERADE"
    )
    for rule in "${rules[@]}"; do
        if grep -- "$rule" $rulesv4 > /dev/null; then
            echo "Rule already exits: ${rule}"
        else
            rule=$(sed -e 's/^\(-A POSTROUTING\)/-t nat \1/' <<< $rule)
            echo "Adding rule: ${rule}"
            sudo iptables $rule || _install_status 1 "Unable to execute iptables"
            added=true
        fi
    done
    # Persist rules if added
    if [ "$added" = true ]; then
        echo "Persisting IP tables rules"
        sudo iptables-save | sudo tee $rulesv4 > /dev/null || _install_status 1 "Unable to execute iptables-save"
    fi

    # Prompt to install RaspAP daemon
    echo -n "Enable RaspAP control service (Recommended)? [Y/n]: "
    if [ "$assume_yes" == 0 ]; then
        read answer < /dev/tty
        if [ "$answer" != "${answer#[Nn]}" ]; then
            _install_status 0 "(Skipped)"
        else
            _enable_raspap_daemon
        fi
    else
        echo -e
        _enable_raspap_daemon
    fi
    _install_status 0
 }

# Add sudoers file to /etc/sudoers.d/ and set file permissions
function _patch_system_files() {

    # Create sudoers
    _install_log "Adding raspap.sudoers to ${raspap_sudoers}"
    sudo cp "$webroot_dir/installers/raspap.sudoers" $raspap_sudoers || _install_status 1 "Unable to apply raspap.sudoers to $raspap_sudoers"
    sudo chmod 0440 $raspap_sudoers || _install_status 1 "Unable to change file permissions for $raspap_sudoers"

    # Add symlink to prevent wpa_cli cmds from breaking with multiple wlan interfaces
    _install_log "Symlinked wpa_supplicant hooks for multiple wlan interfaces"
    if [ ! -f /usr/share/dhcpcd/hooks/10-wpa_supplicant ]; then
        sudo ln -s /usr/share/dhcpcd/hooks/10-wpa_supplicant /etc/dhcp/dhclient-enter-hooks.d/
    fi

    # Unmask and enable hostapd.service
    _install_log "Unmasking and enabling hostapd service"
    sudo systemctl unmask hostapd.service
    sudo systemctl enable hostapd.service
    _install_status 0
}


# Optimize configuration of php-cgi.
function _optimize_php() {
    if [ "$upgrade" == 0 ]; then
        _install_log "Optimize PHP configuration"
        if [ ! -f "$phpcgiconf" ]; then
            _install_status 2 "PHP configuration could not be found."
            return
        fi

        # Backup php.ini and create symlink for restoring.
        datetimephpconf=$(date +%F-%R)
        sudo cp "$phpcgiconf" "$raspap_dir/backups/php.ini.$datetimephpconf"
        sudo ln -sf "$raspap_dir/backups/php.ini.$datetimephpconf" "$raspap_dir/backups/php.ini"

        echo -n "Enable HttpOnly for session cookies (Recommended)? [Y/n]: "
        if [ "$assume_yes" == 0 ]; then
            read answer < /dev/tty
            if [ "$answer" != "${answer#[Nn]}" ]; then
                echo -e
            else
                 php_session_cookie=1;
            fi
        fi

        if [ "$assume_yes" == 1 ] || [ "$php_session_cookie" == 1 ]; then
            echo "Php-cgi enabling session.cookie_httponly."
            sudo sed -i -E 's/^session\.cookie_httponly\s*=\s*(0|([O|o]ff)|([F|f]alse)|([N|n]o))\s*$/session.cookie_httponly = 1/' "$phpcgiconf"
        fi

        if [ "$php_package" = "php7.1-cgi" ]; then
            echo -n "Enable PHP OPCache (Recommended)? [Y/n]: "
            if [ "$assume_yes" == 0 ]; then
                read answer < /dev/tty
                if [ "$answer" != "${answer#[Nn]}" ]; then
                    echo -e
                else
                    php_opcache=1;
                fi
            fi

            if [ "$assume_yes" == 1 ] || [ "$phpopcache" == 1 ]; then
                echo -e "Php-cgi enabling opcache.enable."
                sudo sed -i -E 's/^;?opcache\.enable\s*=\s*(0|([O|o]ff)|([F|f]alse)|([N|n]o))\s*$/opcache.enable = 1/' "$phpcgiconf"
                # Make sure opcache extension is turned on.
                if [ -f "/usr/sbin/phpenmod" ]; then
                    sudo phpenmod opcache
                else
                    _install_status 2 "phpenmod not found."
                fi
            fi
        fi
    fi
}

function _install_complete() {
    _install_log "Installation completed"
    echo "Join RaspAP Insiders for early access to exclusive features!"
    echo -e "${ANSI_RASPBERRY}"
    echo "> https://docs.raspap.com/insiders/"
    echo "> https://github.com/sponsors/RaspAP/"
    echo -e "${ANSI_RESET}"
    if [ "$assume_yes" == 0 ]; then
        # Prompt to reboot if wired ethernet (eth0) is connected.
        # With default_configuration this will create an active AP on restart.
        if ip a | grep -q ': eth0:.*state UP'; then
            echo -n "The system needs to be rebooted as a final step. Reboot now? [Y/n]: "
            read answer < /dev/tty
            if [ "$answer" != "${answer#[Nn]}" ]; then
                echo "Installation reboot aborted."
                exit 0
            fi
            sudo shutdown -r now || _install_status 1 "Unable to execute shutdown"
        fi
    fi
}

