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
readonly blocklist_hosts="https://raw.githubusercontent.com/StevenBlack/hosts/master/hosts"
readonly blocklist_domains="https://big.oisd.nl/dnsmasq"

if [ "$insiders" == 1 ]; then
    repo="RaspAP/raspap-insiders"
    branch=${RASPAP_INSIDERS_LATEST}
fi
git_source_url="https://github.com/$repo"
webroot_dir="/var/www/html"

# NOTE: all the below functions are overloadable for system-specific installs
function _install_raspap() {
    _display_welcome
    _config_installation
    _update_system_packages
    _manage_systemd_services
    _install_dependencies
    _enable_php_lighttpd
    _create_raspap_directories
    _check_for_old_configs
    _optimize_php
    _download_latest_files
    _change_file_ownership
    _create_hostapd_scripts
    _install_raspap_hostapd
    _create_plugin_scripts
    _create_lighttpd_scripts
    _install_lighttpd_configs
    _default_configuration
    _configure_networking
    _prompt_configure_tcp_bbr
    _prompt_install_features
    _install_extra_features
    _patch_system_files
    _install_complete
}

# Performs a minimal update of an existing installation to the latest release version.
# The user is not prompted to install new RaspAP components.
# The -y, --yes and -p, --path switches may be used for an unattended update.
function _update_raspap() {
    _display_welcome
    _config_installation
    _update_system_packages
    _install_dependencies
    _check_for_old_configs
    _download_latest_files
    _change_file_ownership
    _patch_system_files
    _enable_network_activity_monitor
    _create_plugin_scripts
    _install_complete
}

# Prompts user to set installation options
function _config_installation() {
    if [ "$upgrade" == 1 ]; then
        opt=(Upgrade Upgrading upgrade)
    elif [ "$update" == 1 ]; then
        opt=(Update Updating update)
    else
        opt=(Install Installing installation)
    fi
    _install_log "Configure ${opt[2]}"
    _get_linux_distro
    echo "Detected OS: ${DESC} ${LONG_BIT}-bit"
    echo "Using GitHub repository: ${repo} ${branch} branch"
    echo "Configuration directory: ${raspap_dir}"

    if [ -n "$path" ]; then
        echo "Setting install path to ${path}"
        webroot_dir=$path
    fi
    echo -n "Installation directory: ${webroot_dir}? [Y/n]: "
    if [ "$assume_yes" == 0 ]; then
        read answer < /dev/tty
        if [ "$answer" != "${answer#[Nn]}" ]; then
            read -e -p < /dev/tty "Enter alternate install directory: " -i "/var/www/html" webroot_dir
        fi
    else
        echo -e
    fi
    echo "${opt[1]} lighttpd directory: ${webroot_dir}"
    if [ "$upgrade" == 1 ] || [ "$update" == 1 ]; then
        echo "This will ${opt[2]} your existing install to version ${RASPAP_RELEASE}"
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
        LONG_BIT=$(getconf LONG_BIT)
    elif [ -f /etc/os-release ]; then # freedesktop.org
        . /etc/os-release
        OS=$ID
        RELEASE=$VERSION_ID
        CODENAME=$VERSION_CODENAME
        DESC=$PRETTY_NAME
    else
        _install_status 1 "Unsupported Linux distribution"
        exit 0
    fi
}

# Sets php package option based on Linux version, abort if unsupported distro
function _set_php_package() {
    case $RELEASE in
        23.05|12*) # Debian 12 & Armbian 23.05
            php_package="php8.2-cgi"
            phpcgiconf="/etc/php/8.2/cgi/php.ini" ;;
        23.04) # Ubuntu Server 23.04
            php_package="php8.1-cgi"
            phpcgiconf="/etc/php/8.1/cgi/php.ini" ;;
        22.04|20.04|18.04|19.10|11*) # Previous Ubuntu Server, Debian & Armbian distros
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

# Prompts the user to stop & disable Debian's systemd-networkd services.
# It isn't possible to mix Debian networking with dhcpcd.
# On Ubuntu 20.04 / Armbian 22, the systemd-resolved service uses port 53
# by default which prevents dnsmasq from starting.
function _manage_systemd_services() { 
    _install_log "Checking for systemd network services"

    _check_notify_ubuntu

    services=( "systemd-networkd" "systemd-resolved" )
    for svc in "${services[@]}"; do
        # Prompt to disable systemd service
        if systemctl is-active --quiet "$svc".service; then
            echo -n "Stop and disable ${svc} service? [Y/n]: "
            if [ "$assume_yes" == 0 ]; then
                read answer < /dev/tty
                if [ "$answer" != "${answer#[Nn]}" ]; then
                    echo -e
                else
                    sudo systemctl stop "$svc".service || _install_status 1 "Unable to stop ${svc}.service"
                    sudo systemctl disable "$svc".service || _install_status 1 "Unable to disable ${svc}.service"
                fi
            else
                sudo systemctl stop "$svc".service || _install_status 1 "Unable to stop ${svc}.service"
                sudo systemctl disable "$svc".service || _install_status 1 "Unable to disable ${svc}.service"
            fi
        else
            echo "${svc}.service is not running (ok)"
        fi
    done
    _install_status 0
}

# Notifies Ubuntu users of pre-install requirements
function _check_notify_ubuntu() {
    if [ ${OS,,} = "ubuntu" ]; then
        _install_status 2 "Ubuntu Server requires manual pre- and post-install steps. See https://docs.raspap.com/manual/"
        echo -n "Proceed with installation? [Y/n]: "
        read answer < /dev/tty
        if [ "$answer" != "${answer#[Nn]}" ]; then
            echo "Installation aborted."
            exit 0
        else
            _install_status 0
        fi
    fi
}

# Runs a system software update to make sure we're using all fresh packages
function _install_dependencies() {
    _install_log "Installing required packages"
    _set_php_package

    # OS-specific packages
    if [ "$php_package" = "php7.4-cgi" ] && [ ${OS,,} = "ubuntu" ] && [[ ${RELEASE} =~ ^(22.04|20.04|18.04|19.10|11) ]]; then
        echo "Adding apt-repository ppa:ondrej/php"
        sudo apt-get install -y software-properties-common || _install_status 1 "Unable to install dependency"
        sudo add-apt-repository $apt_option ppa:ondrej/php || _install_status 1 "Unable to add-apt-repository ppa:ondrej/php"
    else
        echo "${php_package} will be installed from the main deb sources list"
    fi
    if [ ${OS,,} = "debian" ] || [ ${OS,,} = "ubuntu" ]; then
        dhcpcd_package="dhcpcd5"
        iw_package="iw"
        rsync_package="rsync"
        echo "${dhcpcd_package}, ${iw_package} and ${rsync_package} will be installed from the main deb sources list"
    fi
    if [ ${OS,,} = "raspbian" ] && [[ ${RELEASE} =~ ^(12) ]]; then
        dhcpcd_package="dhcpcd dhcpcd-base"
        echo "${dhcpcd_package} will be installed from the main deb sources list"
    fi
    if [ ${OS,,} = "armbian" ]; then
        ifconfig_package="net-tools"
        echo "${ifconfig_package} will be installed from the main deb sources list"

        # Manually install isoquery
        _install_log "Installing isoquery from the Debian package repository"
        isoquery_deb="https://ftp.debian.org/debian/pool/main/i/isoquery"
        if [ "$LONG_BIT" = "64" ]; then
            isoquery_pkg="isoquery_3.3.4-1+b1_arm64.deb"
        else
            isoquery_pkg="isoquery_3.3.4-1_armhf.deb"
        fi
        echo "isoquery ARM ${LONG_BIT}-bit package selected"
        wget $isoquery_deb/$isoquery_pkg -q --show-progress --progress=bar:force -P /tmp || _install_status 1 "Failed to download isoquery"
        sudo dpkg -x /tmp/$isoquery_pkg /tmp/isoquery/ || _install_status 1 "Failed to extract isoquery"
        sudo cp /tmp/isoquery/usr/bin/isoquery /usr/local/bin/ || _install_status 1 "Failed to copy isoquery binary"
        sudo chmod +x /usr/local/bin/isoquery || _install_status 1 "Failed to set executable permissions on isoquery"
    fi

    if [ "$insiders" == 1 ]; then
        network_tools="curl dnsutils nmap"
        echo "${network_tools} will be installed from the main deb sources list"
    fi

    # Set dconf-set-selections
    echo iptables-persistent iptables-persistent/autosave_v4 boolean true | sudo debconf-set-selections
    echo iptables-persistent iptables-persistent/autosave_v6 boolean true | sudo debconf-set-selections
    sudo apt-get install -y lighttpd git hostapd dnsmasq iptables-persistent $php_package $dhcpcd_package $iw_package $rsync_package $network_tools $ifconfig_package vnstat qrencode jq isoquery || _install_status 1 "Unable to install dependencies"
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
    echo "Changing file ownership of $raspap_dir"
    sudo chown -R $raspap_user:$raspap_user "$raspap_dir" || _install_status 1 "Unable to change file ownership for '$raspap_dir'"
}

# Generate hostapd logging and service control scripts
function _create_hostapd_scripts() {
    _install_log "Creating hostapd logging & control scripts"
    sudo mkdir $raspap_dir/hostapd || _install_status 1 "Unable to create directory '$raspap_dir/hostapd'"

    # Copy logging shell scripts
    sudo cp "$webroot_dir/installers/"enablelog.sh "$raspap_dir/hostapd" || _install_status 1 "Unable to move logging scripts"
    sudo cp "$webroot_dir/installers/"disablelog.sh "$raspap_dir/hostapd" || _install_status 1 "Unable to move logging scripts"
    # Copy service control shell scripts
    sudo cp "$webroot_dir/installers/"servicestart.sh "$raspap_dir/hostapd" || _install_status 1 "Unable to move service control scripts"
    # Change ownership and permissions of hostapd control scripts
    sudo chown -c root:root "$raspap_dir/hostapd/"*.sh || _install_status 1 "Unable change owner and/or group"
    sudo chmod 750 "$raspap_dir/hostapd/"*.sh || _install_status 1 "Unable to change file permissions"
    _install_status 0
}

# Generate plugin helper scripts
function _create_plugin_scripts() {
    _install_log "Creating plugin helper scripts"
    sudo mkdir -p $raspap_dir/plugins || _install_status 1 "Unable to create directory '$raspap_dir/plugins'"

    # Copy plugin helper script
    sudo cp "$webroot_dir/installers/"plugin_helper.sh "$raspap_dir/plugins" || _install_status 1 "Unable to move plugin script"
    # Change ownership and permissions of plugin script
    sudo chown -c root:root "$raspap_dir/plugins/"*.sh || _install_status 1 "Unable change owner and/or group"
    sudo chmod 750 "$raspap_dir/plugins/"*.sh || _install_status 1 "Unable to change file permissions"
    _install_status 0
}

# Generate lighttpd service control scripts
function _create_lighttpd_scripts() {
    _install_log "Creating lighttpd control scripts"
    sudo mkdir $raspap_dir/lighttpd || _install_status 1 "Unable to create directory '$raspap_dir/lighttpd"

    # Copy service control shell scripts
    echo "Copying configport.sh to $raspap_dir/lighttpd"
    sudo cp "$webroot_dir/installers/"configport.sh "$raspap_dir/lighttpd" || _install_status 1 "Unable to move service control scripts"
    # Change ownership and permissions of lighttpd scripts
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

function _prompt_install_features() {
    readonly features=(
      "Ad blocking:Install Ad blocking and enable list management:adblock_option:_install_adblock"
      "OpenVPN:Install OpenVPN and enable client configuration:ovpn_option:_install_openvpn"
      "RestAPI:Install and enable RestAPI:restapi_option:_install_restapi"
      "WireGuard:Install WireGuard and enable VPN tunnel configuration:wg_option:_install_wireguard"
      "VPN provider:Enable VPN provider client configuration:pv_option:_install_provider"
    )
    for feature in "${features[@]}"; do
      IFS=':' read -r -a feature_details <<< "$feature"
      _prompt_install_feature "${feature_details[@]}"
    done
}

# Prompt to install optional feature
function _prompt_install_feature() {
    local feature="$1"
    local prompt="$2"
    local opt="$3"
    local function="$4"
    _install_log "Configure $feature support"
    echo -n "$prompt? [Y/n]: "
    if [ "$assume_yes" == 0 ]; then
        read answer < /dev/tty
        if [ "$answer" != "${answer#[Nn]}" ]; then
            _install_status 0 "(Skipped)"
        else
            $function
        fi
    elif [ "${!opt}" == 1 ]; then
        $function
    else
        echo "(Skipped)"
    fi
}

# Download adblock lists and enable option
function _install_adblock() {
    _install_log "Creating ad blocking base configuration (Beta)"
    if [ ! -d "$raspap_dir/adblock" ]; then
        echo "Creating $raspap_dir/adblock"
        sudo mkdir -p "$raspap_dir/adblock"
    fi
    if [ ! -f /tmp/hostnames.txt ]; then
        echo "Fetching latest hostnames list"
        wget ${blocklist_hosts} -q --show-progress --progress=bar:force -O /tmp/hostnames.txt 2>&1 \
            || _install_status 1 "Unable to download notracking hostnames"
    fi
    if [ ! -f /tmp/domains.txt ]; then
        echo "Fetching latest domains list"
        wget ${blocklist_domains} -q --show-progress --progress=bar:force -O /tmp/domains.txt 2>&1 \
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

# Install VPN provider client configuration
function _install_provider() {
    _install_log "Installing VPN provider support"
    json="$webroot_dir/config/"vpn-providers.json
    while IFS='|' read -r key value; do
        options["$key"]="$value"
    done< <(jq -r '.providers[] | "\(.id)|\(.name)|\(.bin_path)"' "$json")

    if [ -n "$pv_option" ]; then
        if [[ -n ${options[$pv_option]+abc} ]]; then
            answer="$pv_option"
        else
            echo "Invalid choice. The specified option does not exist."
            return 1
        fi
    else
        echo -e "Select an option from the list:"
        while true; do
            # display provider options
            for key in "${!options[@]}"; do
                echo "  $key) ${options[$key]%%|*}"
            done
            echo "  0) None"
            echo -n "Choose an option: "
            read answer < /dev/tty

            if [ "$answer" != "${answer#[0]}" ]; then
                _install_status 0 "(Skipped)"
                break
            elif [[ "$answer" =~ ^[0-9]+$ ]] && [[ -n ${options[$answer]+abc} ]]; then
                break
            else
                echo "Invalid choice. Select a valid option:"
            fi
        done
    fi

    selected="${options[$answer]}"
    echo "Configuring support for ${selected%%|*}"
    bin_path=${selected#*|}
    if ! grep -q "$bin_path" "$webroot_dir/installers/raspap.sudoers"; then
        echo "Adding $bin_path to raspap.sudoers"
        echo "www-data ALL=(ALL) NOPASSWD:$bin_path *" | sudo tee -a "$webroot_dir/installers/raspap.sudoers" > /dev/null || _install_status 1 "Unable to modify raspap.sudoers"
    fi
    echo "Enabling administration option for ${selected%%|*}"
    sudo sed -i "s/\('RASPI_VPN_PROVIDER_ENABLED', \)false/\1true/g" "$webroot_dir/includes/config.php" || _install_status 1 "Unable to modify config.php"

    echo "Adding VPN provider to $raspap_dir/provider.ini"
    if [ ! -f "$raspap_dir/provider.ini" ]; then
        sudo touch "$raspap_dir/provider.ini"
        echo "providerID = $answer" | sudo tee "$raspap_dir/provider.ini" > /dev/null || _install_status 1 "Unable to create $raspap_dir/provider.ini"
    elif ! grep -q "providerID = $answer" "$raspap_dir/provider.ini"; then
        echo "providerID = $answer" | sudo tee "$raspap_dir/provider.ini" > /dev/null || _install_status 1 "Unable to write to $raspap_dir/provider.ini"
    fi

    _install_status 0
}

# Install Wireguard from the Debian unstable distro
function _install_wireguard() {
    _install_log "Configuring WireGuard support"
    if { [ "$OS" == "Debian" ] && [ "$RELEASE" == 12 ]; } ||
       { [ "$OS" == "Ubuntu" ] && [ "$RELEASE" == "22.04" ]; }; then
        wg_dep="resolvconf"
    fi
    echo "Installing wireguard from apt"
    sudo apt-get install -y wireguard $wg_dep || _install_status 1 "Unable to install wireguard"
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

    # Copy service auth control and logging scripts
    sudo cp "$webroot_dir/installers/"configauth.sh "$raspap_dir/openvpn" || _install_status 1 "Unable to move auth control script"
    sudo cp "$webroot_dir/installers/"openvpnlog.sh "$raspap_dir/openvpn" || _install_status 1 "Unable to move logging script"
    # Restrict script execution to root user
    sudo chown -c root:root "$raspap_dir/openvpn/"*.sh || _install_status 1 "Unable change owner and/or group"
    sudo chmod 750 "$raspap_dir/openvpn/"*.sh || _install_status 1 "Unable to change file permissions"

    _install_status 0
}

# Install and enable RestAPI configuration option
function _install_restapi() {
    _install_log "Installing and enabling RestAPI"
    sudo mv "$webroot_dir/api" "$raspap_dir/api"  || _install_status 1 "Unable to move api folder"

    if ! command -v python3 &> /dev/null; then
        echo "Python is not installed. Installing Python..."
        sudo apt update
        sudo apt install -y python3 python3-pip
        echo "Python installed successfully."
    else
        echo "Python is already installed."
        sudo apt install python3-pip -y
        
    fi
    python3 -m pip install -r "$raspap_dir/api/requirements.txt" --break-system-packages || _install_status 1 " Unable to install pip modules"
   
    echo "Setting permissions on restapi systemd unit control file"
    sudo chown -c root:root $webroot_dir/installers/restapi.service || _install_status 1 "Unable change owner and/or group"
    echo "Moving restapi systemd unit control file to /lib/systemd/system/"
    sudo mv $webroot_dir/installers/restapi.service /lib/systemd/system/ || _install_status 1 "Unable to move restapi.service file"
    sudo systemctl daemon-reload
    sudo systemctl enable restapi.service || _install_status 1 "Failed to enable restapi.service"
    echo "Enabling RestAPI management option"
    sudo sed -i "s/\('RASPI_RESTAPI_ENABLED', \)false/\1true/g" "$webroot_dir/includes/config.php" || _install_status 1 "Unable to modify config.php"

    _install_status 0
}

# Fetches latest files from github to webroot
function _download_latest_files() {
    _install_log "Cloning latest files from GitHub"
    source_dir="/tmp/raspap-webgui"
    if [ -d "$source_dir" ]; then
        echo "Temporary download destination $source_dir exists. Removing..."
        rm -r "$source_dir"
    fi
    if [ "$repo" == "RaspAP/raspap-insiders" ]; then
        if [ -n "$username" ] && [ -n "$acctoken" ]; then
            insiders_source_url="https://${username}:${acctoken}@github.com/$repo"
            git clone --branch $branch --depth 1 --recurse-submodules -c advice.detachedHead=false $insiders_source_url $source_dir || clone=false
            git -C $source_dir submodule update --remote plugins || clone=false
        else
            _install_status 3
            echo "Insiders please read this: https://docs.raspap.com/insiders/#authentication"
        fi
    fi
    if [ -z "$insiders_source_url" ]; then
        git clone --branch $branch --depth 1 --recurse-submodules -c advice.detachedHead=false $git_source_url $source_dir || clone=false
        git -C $source_dir submodule update --remote plugins || clone=false
    fi
    if [ "$clone" = false ]; then
        _install_status 1 "Unable to download files from GitHub"
        echo "The installer cannot continue." >&2
        exit 1
    fi

    if [ -d "$webroot_dir" ] && [ "$update" == 0 ]; then
        sudo mv $webroot_dir "$webroot_dir.`date +%F-%R`" || _install_status 1 "Unable to move existing webroot directory"
    elif [ "$upgrade" == 1 ] || [ "$update" == 1 ]; then
        exclude='--exclude=ajax/system/sys_read_logfile.php'
        shopt -s extglob
        sudo find "$webroot_dir" ! -path "${webroot_dir}/ajax/system/sys_read_logfile.php" -delete 2>/dev/null
    fi

    _install_log "Installing application to $webroot_dir"
    sudo rsync -av $exclude "$source_dir"/ "$webroot_dir"/ >/dev/null 2>&1 || _install_status 1 "Unable to install files to $webroot_dir"

    if [ "$update" == 1 ]; then
        _install_log "Applying existing configuration to ${webroot_dir}/includes"
        sudo mv /tmp/config.php $webroot_dir/includes  || _install_status 1 "Unable to move config.php to ${webroot_dir}/includes"
        
        if [ -f /tmp/raspap.auth ]; then
            _install_log "Applying existing authentication file to ${raspap_dir}"
            sudo mv /tmp/raspap.auth $raspap_dir || _install_status 1 "Unable to restore authentification credentials file to ${raspap_dir}"
        fi
    else
        echo "Copying primary RaspAP config to ${webroot_dir}/includes/config.php"
        if [ ! -f "$webroot_dir/includes/config.php" ]; then
            sudo cp "$webroot_dir/config/config.php" "$webroot_dir/includes/config.php"
        fi
    fi
    echo "Removing source files at ${source_dir}"
    sudo rm -rf $source_dir

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
    if [ "$update" == 1 ]; then
        _install_log "Moving existing configuration to /tmp"
        sudo mv $webroot_dir/includes/config.php /tmp || _install_status 1 "Unable to move config.php to /tmp"
       if [ -f $raspap_dir/raspap.auth ]; then
            _install_log "Moving existing raspap.auth file to /tmp"
            sudo mv $raspap_dir/raspap.auth /tmp || _install_status 1 "Unable to backup raspap.auth to /tmp"
        fi
    else
        _install_log "Checking for existing configs"
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

# Set up default configuration
function _default_configuration() {
    if [ "$upgrade" == 0 ]; then
        _install_log "Applying default configuration to installed services"

        echo "Checking for existence of /etc/dnsmasq.d"
        [ -d /etc/dnsmasq.d ] || sudo mkdir /etc/dnsmasq.d

        echo "Copying config/hostapd.conf to /etc/hostapd/hostapd.conf"
        sudo cp $webroot_dir/config/hostapd.conf /etc/hostapd/hostapd.conf || _install_status 1 "Unable to move hostapd configuration file"

        echo "Copying config/090_raspap.conf to $raspap_default"
        sudo cp $webroot_dir/config/090_raspap.conf $raspap_default || _install_status 1 "Unable to move dnsmasq default configuration file"

        echo "Copying config/090_wlan0.conf to $raspap_wlan0"
        sudo cp $webroot_dir/config/090_wlan0.conf $raspap_wlan0 || _install_status 1 "Unable to move dnsmasq wlan0 configuration file"

        echo "Copying config/dhcpcd.conf to /etc/dhcpcd.conf"
        sudo cp $webroot_dir/config/dhcpcd.conf /etc/dhcpcd.conf || _install_status 1 "Unable to move dhcpcd configuration file"

        echo "Copying config/defaults.json to $raspap_network"
        sudo cp $webroot_dir/config/defaults.json $raspap_network || _install_status 1 "Unable to move defaults.json settings"

        echo "Changing file ownership of ${raspap_network}defaults.json"
        sudo chown $raspap_user:$raspap_user "$raspap_network"defaults.json || _install_status 1 "Unable to change file ownership for defaults.json"

        # Copy OS-specific bridge default config
        if [ ${OS,,} = "ubuntu" ] && [[ ${RELEASE} =~ ^(22.04|20.04|19.10|18.04) ]]; then
            echo "Copying bridged AP config to /etc/netplan"
            sudo cp $webroot_dir/config/raspap-bridge-br0.netplan /etc/netplan/raspap-bridge-br0.netplan || _install_status 1 "Unable to move br0 netplan file"
        else
            echo "Copying bridged AP config to /etc/systemd/network"
            sudo cp $webroot_dir/config/raspap-bridge-br0.netdev /etc/systemd/network/raspap-bridge-br0.netdev || _install_status 1 "Unable to move br0 netdev file"
            sudo cp $webroot_dir/config/raspap-br0-member-eth0.network /etc/systemd/network/raspap-br0-member-eth0.network || _install_status 1 "Unable to move br0 member file"
        fi

        if [ ${OS,,} = "raspbian" ] && [[ ${RELEASE} =~ ^(12) ]]; then
            echo "Moving dhcpcd systemd unit control file to /lib/systemd/system/"
            sudo mv $webroot_dir/installers/dhcpcd.service /lib/systemd/system/ || _install_status 1 "Unable to move dhcpcd.service file"
            sudo systemctl daemon-reload
            sudo systemctl enable dhcpcd.service || _install_status 1 "Failed to enable dhcpcd.service"
        fi

        # Set correct DAEMON_CONF path for hostapd (Ubuntu20 + Armbian22)
        if [ ${OS,,} = "ubuntu" ] && [[ ${RELEASE} =~ ^(22.04|20.04|19.10|18.04) ]]; then
            conf="/etc/default/hostapd"
            key="DAEMON_CONF"
            value="/etc/hostapd/hostapd.conf"
            echo "Setting default ${key} path to ${value}"
            sudo sed -i -E "/^#?$key/ { s/^#//; s%=.*%=\"$value\"%; }" "$conf" || _install_status 1 "Unable to set value in ${conf}"
        fi

        _install_log "Unmasking and enabling hostapd service"
        sudo systemctl unmask hostapd.service
        sudo systemctl enable hostapd.service

        _install_status 0
    else
        _install_log "Copying defaults.json to $raspap_network"
        sudo cp $webroot_dir/config/defaults.json $raspap_network || _install_status 1 "Unable to move defaults.json settings"
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

# Install hostapd@.service
function _install_raspap_hostapd() {
    _install_log "Installing RaspAP hostapd@.service"
    sudo cp $webroot_dir/installers/hostapd@.service /etc/systemd/system/ || _install_status 1 "Unable to copy hostapd@.service file"
    sudo systemctl daemon-reload
    _install_status 0
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

    # Enable RaspAP network activity monitor
    _enable_network_activity_monitor

    _install_status 0
 }

# Install and enable RaspAP network activity monitor
function _enable_network_activity_monitor() {
    _install_log "Enabling RaspAP network activity monitor"
    echo "Compiling raspap-network-monitor.c to /usr/local/bin/"
    if ! command -v gcc >/dev/null 2>&1; then
        echo "gcc not found, installing..."
        sudo apt-get update
        sudo apt-get install -y build-essential || _install_status 1 "Failed to install build tools"
    fi
    sudo gcc -O2 -o /usr/local/bin/raspap-network-monitor $webroot_dir/installers/raspap-network-monitor.c || _install_status 1 "Failed to compile raspap-network-monitor.c"
    echo "Copying raspap-network-activity@.service to /lib/systemd/system/"
    sudo cp $webroot_dir/installers/raspap-network-activity@.service /lib/systemd/system/ || _install_status 1 "Unable to move raspap-network-activity.service file"
    sudo systemctl daemon-reload
    echo "Enabling raspap-network-activity@wlan0.service"
    sudo systemctl enable raspap-network-activity@wlan0.service || _install_status 1 "Failed to enable raspap-network-activity.service"
    echo "Starting raspap-network-activity@wlan0.service"
    sudo systemctl start raspap-network-activity@wlan0.service || _install_status 1 "Failed to start raspap-network-activity.service"
    sleep 0.5
    echo "Symlinking /dev/shm/net_activity to $webroot_dir/app/net_activity"
    sudo ln -sf /dev/shm/net_activity $webroot_dir/app/net_activity || _install_status 1 "Failed to link net_activity to ${webroot_dir}/app"
    echo "Setting ownership for ${raspap_user} on ${webroot_dir}/app/net_activity"
    sudo chown -R $raspap_user:$raspap_user $webroot_dir/app/net_activity || _install_status 1 "Unable to set ownership of ${webroot_dir}/app/net_activity"
    echo "Network activity monitor enabled"
}

# Prompt to configure TCP BBR option
function _prompt_configure_tcp_bbr() {
    _install_log "Configure TCP BBR congestion control"
    echo "Network performance can be improved by changing TCP congestion control to BBR (Bottleneck Bandwidth and RTT)"
    echo -n "Enable TCP BBR congestion control algorithm (Recommended)? [Y/n]: "
    if [ "$assume_yes" == 0 ]; then
        read answer < /dev/tty
        if [ "$answer" != "${answer#[Nn]}" ]; then
            _install_status 0 "(Skipped)"
        else
            _configure_tcp_bbr
        fi
    elif [ "${bbr_option}" == 1 ]; then
        _configure_tcp_bbr
    else
        echo "(Skipped)"
    fi
}

function _configure_tcp_bbr() {
    echo "Checking kernel support for the TCP BBR algorithm..."
    _check_tcp_bbr_available
    if [ $? -eq 0 ]; then
        echo "TCP BBR option found. Enabling configuration"
        # Load the BBR module
        echo "Loading BBR kernel module"
        sudo modprobe tcp_bbr || _install_status 1 "Unable to execute modprobe tcp_bbr"
        # Add BBR configuration to sysctl.conf if not present
        echo "Adding BBR configuration to /etc/sysctl.conf if not present"
        if ! grep -q "net.core.default_qdisc=fq" /etc/sysctl.conf; then
            echo "net.core.default_qdisc=fq" | sudo tee -a /etc/sysctl.conf || _install_status 1 "Unable to modify /etc/sysctl.conf"
        fi
        if ! grep -q "net.ipv4.tcp_congestion_control=bbr" /etc/sysctl.conf; then
            echo "net.ipv4.tcp_congestion_control=bbr" | sudo tee -a /etc/sysctl.conf || _install_status 1 "Unable to modify /etc/sysctl.conf"
        fi
        # Apply the sysctl changes
        echo "Applying changes"
        sudo sysctl -p || _install_status 1 "Unable to execute sysctl"

        # Verify if BBR is enabled
        cc=$(sysctl net.ipv4.tcp_congestion_control | awk '{print $3}')
        if [ "$cc" == "bbr" ]; then
            echo "TCP BBR successfully enabled"
        else
            _install_status 1 "Failed to enable TCP BBR"
        fi
    else
        _install_status 2 "TCP BBR option is not available (Skipped)"
    fi
    _install_status 0
}

function _check_tcp_bbr_available() {
    if [[ "$(modinfo -F intree tcp_bbr)" =~ ^[Yy]$ ]]; then
        return 0
    else
        return 1
    fi
}

# Add sudoers file to /etc/sudoers.d/ and set file permissions
function _patch_system_files() {

    # Create sudoers
    _install_log "Adding raspap.sudoers to ${raspap_sudoers}"
    sudo cp "$webroot_dir/installers/raspap.sudoers" $raspap_sudoers || _install_status 1 "Unable to apply raspap.sudoers to $raspap_sudoers"
    sudo chmod 0440 $raspap_sudoers || _install_status 1 "Unable to change file permissions for $raspap_sudoers"

    if [ ! -d "$raspap_dir/system" ]; then
        sudo mkdir $raspap_dir/system || _install_status 1 "Unable to create directory '$raspap_dir/system'"
    fi

    _install_log "Copying RaspAP debug log control script"
    sudo cp "$webroot_dir/installers/"debuglog.sh "$raspap_dir/system" || _install_status 1 "Unable to move debug logging script"

    _install_log "Copying RaspAP install loader"
    sudo cp "$webroot_dir/installers/"raspbian.sh "$raspap_dir/system" || _install_status 1 "Unable to move application update script"

    # Set ownership and permissions
    sudo chown -c root:root "$raspap_dir/system/"*.sh || _install_status 1 "Unable change owner and/or group"
    sudo chmod 750 "$raspap_dir/system/"*.sh || _install_status 1 "Unable to change file permissions"

    # Add symlink to prevent wpa_cli cmds from breaking with multiple wlan interfaces
    _install_log "Symlinked wpa_supplicant hooks for multiple wlan interfaces"
    if [ ! -f /usr/share/dhcpcd/hooks/10-wpa_supplicant ]; then
        sudo ln -s /usr/share/dhcpcd/hooks/10-wpa_supplicant /etc/dhcp/dhclient-enter-hooks.d/
    fi

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

# search for optional installation files names install_feature_*.sh
function _install_extra_features() {
    if [ "$insiders" == 1 ]; then
        _install_log "Installing additional features (Insiders)"
        for feature in $(ls $webroot_dir/installers/install_feature_*.sh) ; do
           source $feature
           f=$(basename $feature)
           func="_${f%.*}"
           if declare -f -F $func > /dev/null; then
                $func || _install_status 1 "Unable to install feature ($func)"
            else
                _install_status 1 "Install file $f is missing install function $func"
           fi
       done
    fi
}

function _install_complete() {
    _install_log "Installation completed"
    if [ "$repo" == "RaspAP/raspap-insiders" ]; then
        echo -e "${ANSI_RASPBERRY}"
        echo "Thank you for supporting this project as an Insider!"
        echo -e "${ANSI_RESET}"
    else
        echo "Join RaspAP Insiders for early access to exclusive features!"
        echo -e "${ANSI_RASPBERRY}"
        echo "> https://docs.raspap.com/insiders/"
        echo "> https://github.com/sponsors/RaspAP/"
        echo -e "${ANSI_RESET}"
    fi
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

