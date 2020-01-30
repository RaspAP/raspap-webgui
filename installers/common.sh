#!/bin/bash
#
# RaspAP installation functions.
# author: @billz
# license: GNU General Public License v3.0

raspap_dir="/etc/raspap"
raspap_user="www-data"
webroot_dir="/var/www/html"
version=`sed 's/\..*//' /etc/debian_version`

# Determine Raspbian version, set default home location for lighttpd and 
# php package to install 
if [ "$version" -eq "10" ]; then
    version_msg="Raspbian 10.0 (Buster)"
    php_package="php7.1-cgi"
elif [ "$version" -eq "9" ]; then
    version_msg="Raspbian 9.0 (Stretch)" 
    php_package="php7.0-cgi" 
elif [ "$version" -eq "8" ]; then
    install_error "Raspbian 8.0 (Jessie) and php5 are deprecated. Please upgrade."
elif [ "$version" -lt "8" ]; then
    install_error "Raspbian ${version} is unsupported. Please upgrade."
fi

phpcgiconf=""
if [ "$php_package" = "php7.1-cgi" ]; then
    phpcgiconf="/etc/php/7.1/cgi/php.ini"
elif [ "$php_package" = "php7.0-cgi" ]; then
    phpcgiconf="/etc/php/7.0/cgi/php.ini"
fi

### NOTE: all the below functions are overloadable for system-specific installs

# Prompts user to set options for installation
function config_installation() {
    install_log "Configure installation"
    echo "Detected ${version_msg}" 
    echo "Install directory: ${raspap_dir}"
    echo -n "Install to Lighttpd root directory: ${webroot_dir}? [Y/n]: "
    if [ "$assume_yes" == 0 ]; then
        read answer < /dev/tty
        if [ "$answer" != "${answer#[Nn]}" ]; then
            read -e -p < /dev/tty "Enter alternate Lighttpd directory: " -i "/var/www/html" webroot_dir
        fi
    else
        echo -e
    fi
    echo "Install to Lighttpd directory: ${webroot_dir}"

    echo -n "Complete installation with these values? [Y/n]: "
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

# Runs a system software update to make sure we're using all fresh packages
function install_dependencies() {
    install_log "Installing required packages"
    sudo apt-get install $apt_option lighttpd $php_package git hostapd dnsmasq vnstat || install_error "Unable to install dependencies"
}

# Enables PHP for lighttpd and restarts service for settings to take effect
function enable_php_lighttpd() {
    install_log "Enabling PHP for lighttpd"

    sudo lighttpd-enable-mod fastcgi-php    
    sudo service lighttpd force-reload
    sudo systemctl restart lighttpd.service || install_error "Unable to restart lighttpd"
}

# Verifies existence and permissions of RaspAP directory
function create_raspap_directories() {
    install_log "Creating RaspAP directories"
    if [ -d "$raspap_dir" ]; then
        sudo mv $raspap_dir "$raspap_dir.`date +%F-%R`" || install_error "Unable to move old '$raspap_dir' out of the way"
    fi
    sudo mkdir -p "$raspap_dir" || install_error "Unable to create directory '$raspap_dir'"

    # Create a directory for existing file backups.
    sudo mkdir -p "$raspap_dir/backups"

    # Create a directory to store networking configs
    sudo mkdir -p "$raspap_dir/networking"
    # Copy existing dhcpcd.conf to use as base config
    cat /etc/dhcpcd.conf | sudo tee -a /etc/raspap/networking/defaults

    sudo chown -R $raspap_user:$raspap_user "$raspap_dir" || install_error "Unable to change file ownership for '$raspap_dir'"
}

# Generate hostapd logging and service control scripts
function create_hostapd_scripts() {
    install_log "Creating hostapd logging & control scripts"
    sudo mkdir $raspap_dir/hostapd || install_error "Unable to create directory '$raspap_dir/hostapd'"

    # Move logging shell scripts 
    sudo cp "$webroot_dir/installers/"*log.sh "$raspap_dir/hostapd" || install_error "Unable to move logging scripts"
    # Move service control shell scripts
    sudo cp "$webroot_dir/installers/"service*.sh "$raspap_dir/hostapd" || install_error "Unable to move service control scripts"
    # Make enablelog.sh and disablelog.sh not writable by www-data group.
    sudo chown -c root:"$raspap_user" "$raspap_dir/hostapd/"*.sh || install_error "Unable change owner and/or group"
    sudo chmod 750 "$raspap_dir/hostapd/"*.sh || install_error "Unable to change file permissions"
}

# Generate lighttpd service control scripts
function create_lighttpd_scripts() {
    install_log "Creating lighttpd control scripts"
    sudo mkdir $raspap_dir/lighttpd || install_error "Unable to create directory '$raspap_dir/lighttpd"

    # Move service control shell scripts
    sudo cp "$webroot_dir/installers/"configport.sh "$raspap_dir/lighttpd" || install_error "Unable to move service control scripts"
    # Make configport.sh writable by www-data group
    sudo chown -c root:"$raspap_user" "$raspap_dir/lighttpd/"*.sh || install_error "Unable change owner and/or group"
    sudo chmod 750 "$raspap_dir/lighttpd/"*.sh || install_error "Unable to change file permissions"
}

# Prompt to install openvpn
function prompt_install_openvpn() {
    install_log "Setting up OpenVPN support (beta)"
    echo -n "Install OpenVPN and enable client configuration? [Y/n]: "
    if [ "$assume_yes" == 0 ]; then
        read answer < /dev/tty
        if [ "$answer" != "${answer#[Nn]}" ]; then
            echo -e
        else
            install_openvpn
        fi
    elif [ "$ovpn_option" == 1 ]; then
        install_openvpn
    fi
}

# Install openvpn and enable client configuration option
function install_openvpn() {
    install_log "Installing OpenVPN and enabling client configuration"
    sudo apt-get install -y openvpn || install_error "Unable to install openvpn"
    sudo sed -i "s/\('RASPI_OPENVPN_ENABLED', \)false/\1true/g" "$webroot_dir/includes/config.php" || install_error "Unable to modify config.php"
    echo "Enabling openvpn-client service on boot"
    sudo systemctl enable openvpn-client@client || install_error "Unable to enable openvpn-client daemon"
    create_openvpn_scripts || install_error "Unable to create openvpn control scripts"
}

# Generate openvpn logging and auth control scripts
function create_openvpn_scripts() {
    install_log "Creating OpenVPN control scripts"
    sudo mkdir $raspap_dir/openvpn || install_error "Unable to create directory '$raspap_dir/openvpn'"

   # Move service auth control shell scripts
    sudo cp "$webroot_dir/installers/"configauth.sh "$raspap_dir/openvpn" || install_error "Unable to move auth control script"
    # Make configauth.sh writable by www-data group
    sudo chown -c root:"$raspap_user" "$raspap_dir/openvpn/"*.sh || install_error "Unable change owner and/or group"
    sudo chmod 750 "$raspap_dir/openvpn/"*.sh || install_error "Unable to change file permissions"
}

# Fetches latest files from github to webroot
function download_latest_files() {
    if [ ! -d "$webroot_dir" ]; then
        sudo mkdir -p $webroot_dir || install_error "Unable to create new webroot directory"
    fi

    if [ -d "$webroot_dir" ]; then
        sudo mv $webroot_dir "$webroot_dir.`date +%F-%R`" || install_error "Unable to remove old webroot directory"
    fi

    install_log "Cloning latest files from github"
    git clone --depth 1 https://github.com/SomasunderAsteroid/raspap-webgui /tmp/raspap-webgui || install_error "Unable to download files from github"
    sudo mv /tmp/raspap-webgui $webroot_dir || install_error "Unable to move raspap-webgui to web root"
    echo "Downloaded files..."
    sleep 30
    sudo mv "$webroot_dir/config/wifistart" "/usr/local/bin/wifistart" || install_error "Unable to move wifistart file"
}

# Sets files ownership in web root directory
function change_file_ownership() {
    if [ ! -d "$webroot_dir" ]; then
        install_error "Web root directory doesn't exist"
    fi

    install_log "Changing file ownership in web root directory"
    sudo chown -R $raspap_user:$raspap_user "$webroot_dir" || install_error "Unable to change file ownership for '$webroot_dir'"
}

# Check for existing /etc/network/interfaces and /etc/hostapd/hostapd.conf files
function check_for_old_configs() {
    if [ -f /etc/network/interfaces ]; then
        sudo cp /etc/network/interfaces "$raspap_dir/backups/interfaces.`date +%F-%R`"
        sudo ln -sf "$raspap_dir/backups/interfaces.`date +%F-%R`" "$raspap_dir/backups/interfaces"
    fi

    if [ -f /etc/hostapd/hostapd.conf ]; then
        sudo cp /etc/hostapd/hostapd.conf "$raspap_dir/backups/hostapd.conf.`date +%F-%R`"
        sudo ln -sf "$raspap_dir/backups/hostapd.conf.`date +%F-%R`" "$raspap_dir/backups/hostapd.conf"
    fi

    if [ -f /etc/dnsmasq.conf ]; then
        sudo cp /etc/dnsmasq.conf "$raspap_dir/backups/dnsmasq.conf.`date +%F-%R`"
        sudo ln -sf "$raspap_dir/backups/dnsmasq.conf.`date +%F-%R`" "$raspap_dir/backups/dnsmasq.conf"
    fi

    if [ -f /etc/dhcpcd.conf ]; then
        sudo cp /etc/dhcpcd.conf "$raspap_dir/backups/dhcpcd.conf.`date +%F-%R`"
        sudo ln -sf "$raspap_dir/backups/dhcpcd.conf.`date +%F-%R`" "$raspap_dir/backups/dhcpcd.conf"
    fi

    if [ -f /etc/rc.local ]; then
        sudo cp /etc/rc.local "$raspap_dir/backups/rc.local.`date +%F-%R`"
        sudo ln -sf "$raspap_dir/backups/rc.local.`date +%F-%R`" "$raspap_dir/backups/rc.local"
    fi
}

# Move configuration file to the correct location
function move_config_file() {
    if [ ! -d "$raspap_dir" ]; then
        install_error "'$raspap_dir' directory doesn't exist"
    fi

    install_log "Moving configuration file to '$raspap_dir'"
    sudo cp "$webroot_dir"/raspap.php "$raspap_dir" || install_error "Unable to move files to '$raspap_dir'"
    sudo chown -R $raspap_user:$raspap_user "$raspap_dir" || install_error "Unable to change file ownership for '$raspap_dir'"
}

# Set up default configuration
function default_configuration() {
    install_log "Setting up hostapd"
    if [ -f /etc/default/hostapd ]; then
        sudo mv /etc/default/hostapd /tmp/default_hostapd.old || install_error "Unable to remove old /etc/default/hostapd file"
    fi
    sudo cp $webroot_dir/config/default_hostapd /etc/default/hostapd || install_error "Unable to move hostapd defaults file"
    sudo cp $webroot_dir/config/hostapd.conf /etc/hostapd/hostapd.conf || install_error "Unable to move hostapd configuration file"
    sudo cp $webroot_dir/config/dnsmasq.conf /etc/dnsmasq.conf || install_error "Unable to move dnsmasq configuration file"
    sudo cp $webroot_dir/config/dhcpcd.conf /etc/dhcpcd.conf || install_error "Unable to move dhcpcd configuration file"

    if [ ! -f "$webroot_dir/includes/config.php" ]; then
        sudo cp "$webroot_dir/config/config.php" "$webroot_dir/includes/config.php"
    fi

    # Generate required lines for Rasp AP to place into rc.local file.
    # #RASPAP is for removal script
    # lines=(
    # '/bin/bash /usr/local/bin/wifistart'
    # )
    
   # for line in "${lines[@]}"; do
   # Add startup script path to rc.local
        if grep "/bin/bash /usr/local/bin/wifistart" /etc/rc.local > /dev/null; then
            echo "/bin/bash /usr/local/bin/wifistart: Line already added"
        else
            sudo sed -i "s/^exit 0$/\/bin\/bash \/usr\/local\/bin\/wifistart\nexit 0/" /etc/rc.local || install_error "Unable to write command rc.local file."
            echo "Adding line /bin/bash /usr/local/bin/wifistart"
        fi
   # done

    # Force a reload of new settings in /etc/rc.local
    sudo systemctl restart rc-local.service
    sudo systemctl daemon-reload

    # Prompt to install RaspAP daemon
    echo -n "Enable RaspAP control service (Recommended)? [Y/n]: "
    if [ "$assume_yes" == 0 ]; then
        read answer < /dev/tty
        if [ "$answer" != "${answer#[Nn]}" ]; then
            echo -e
        else
            enable_raspap_daemon
        fi
    else
        echo -e
        enable_raspap_daemon
    fi
}

# Install and enable RaspAP daemon
function enable_raspap_daemon() {
    install_log "Enabling RaspAP daemon"
    echo "Disable with: sudo systemctl disable raspap.service"
    sudo cp $webroot_dir/installers/raspap.service /lib/systemd/system/ || install_error "Unable to move raspap.service file"
    sudo systemctl enable raspap.service || install_error "Failed to enable raspap.service"
}

# Add a single entry to the sudoers file
function sudo_add() {
    sudo bash -c "echo \"$raspap_user ALL=(ALL) NOPASSWD:$1\" | (EDITOR=\"tee -a\" visudo)" \
        || install_error "Unable to patch /etc/sudoers"
}

# Adds www-data user to the sudoers file with restrictions on what the user can execute
function patch_system_files() {

    # Set commands array
    cmds=(
        "/sbin/ifdown"
        "/sbin/ifup"
        "/bin/cat /etc/wpa_supplicant/wpa_supplicant.conf"
        "/bin/cat /etc/wpa_supplicant/wpa_supplicant-wlan[0-9].conf"
        "/bin/cp /tmp/wifidata /etc/wpa_supplicant/wpa_supplicant.conf"
        "/bin/cp /tmp/wifidata /etc/wpa_supplicant/wpa_supplicant-wlan[0-9].conf"
        "/sbin/wpa_cli -i wlan[0-9] scan_results"
        "/sbin/wpa_cli -i wlan[0-9] scan"
        "/sbin/wpa_cli -i wlan[0-9] reconfigure"
        "/sbin/wpa_cli -i wlan[0-9] select_network"
        "/bin/cp /tmp/hostapddata /etc/hostapd/hostapd.conf"
        "/bin/systemctl start hostapd.service"
        "/bin/systemctl stop hostapd.service"
        "/bin/systemctl start dnsmasq.service"
        "/bin/systemctl stop dnsmasq.service"
        "/bin/systemctl start openvpn-client@client"
        "/bin/systemctl stop openvpn-client@client"
        "/bin/cp /tmp/ovpnclient.ovpn /etc/openvpn/client/client.conf"
        "/bin/cp /tmp/authdata /etc/openvpn/client/login.conf"
        "/bin/cp /tmp/dnsmasqdata /etc/dnsmasq.conf"
        "/bin/cp /tmp/dhcpddata /etc/dhcpcd.conf"
        "/sbin/shutdown -h now"
        "/sbin/reboot"
        "/sbin/ip link set wlan[0-9] down"
        "/sbin/ip link set wlan[0-9] up"
        "/sbin/ip -s a f label wlan[0-9]"
        "/bin/cp /etc/raspap/networking/dhcpcd.conf /etc/dhcpcd.conf"
        "/etc/raspap/hostapd/enablelog.sh"
        "/etc/raspap/hostapd/disablelog.sh"
        "/etc/raspap/hostapd/servicestart.sh"
        "/etc/raspap/lighttpd/configport.sh"
        "/etc/raspap/openvpn/configauth.sh"
    )

    # Check if sudoers needs patching
    if [ $(sudo grep -c $raspap_user /etc/sudoers) -ne ${#cmds[@]} ]
    then
        # Sudoers file has incorrect number of commands. Wiping them out.
        install_log "Cleaning system sudoers file"
        sudo sed -i "/$raspap_user/d" /etc/sudoers
        install_log "Patching system sudoers file"
        # patch /etc/sudoers file
        for cmd in "${cmds[@]}"
        do
            sudo_add $cmd
            IFS=$'\n'
        done
    else
        install_log "Sudoers file already patched"
    fi

    # Add symlink to prevent wpa_cli cmds from breaking with multiple wlan interfaces
    install_log "Symlinked wpa_supplicant hooks for multiple wlan interfaces"
    if [ ! -f /usr/share/dhcpcd/hooks/10-wpa_supplicant ]; then
        sudo ln -s /usr/share/dhcpcd/hooks/10-wpa_supplicant /etc/dhcp/dhclient-enter-hooks.d/
    fi

    # Unmask and enable hostapd.service
    install_log "Unmasking and enabling hostapd service"
    sudo systemctl unmask hostapd.service
    sudo systemctl enable hostapd.service
}


# Optimize configuration of php-cgi.
function optimize_php() {
    install_log "Optimize PHP configuration"
    if [ ! -f "$phpcgiconf" ]; then
        install_warning "PHP configuration could not be found."
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
                install_warning "phpenmod not found."
            fi
        fi
    fi
}

function install_complete() {
    install_log "Installation completed!"

    if [ "$assume_yes" == 0 ]; then
        # Prompt to reboot if wired ethernet (eth0) is connected.
        # With default_configuration this will create an active AP on restart.
        if ip a | grep -q ': eth0:.*state UP'; then
            echo -n "The system needs to be rebooted as a final step. Reboot now? [y/N]: "
            read answer < /dev/tty
            if [ "$answer" != "${answer#[Nn]}" ]; then
                echo "Installation reboot aborted."
                exit 0
            fi
            sudo shutdown -r now || install_error "Unable to execute shutdown"
        fi
    fi
}

function install_raspap() {
    display_welcome
    config_installation
    update_system_packages
    install_dependencies
    enable_php_lighttpd
    create_raspap_directories
    optimize_php
    check_for_old_configs
    download_latest_files
    change_file_ownership
    create_hostapd_scripts
    create_lighttpd_scripts
    move_config_file
    default_configuration
    prompt_install_openvpn
    patch_system_files
    install_complete
}
