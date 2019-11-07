#!/bin/bash
#
# RaspAP SSL certificate installation functions
# author: @billz
# license: GNU General Public License v3.0

certname=$HOSTNAME."local"
lighttpd_ssl="/etc/lighttpd/ssl"

### NOTE: all the below functions are overloadable for system-specific installs

function config_installation() {
    install_log "Configure a new SSL certificate"
    echo "Current system hostname is ${certname}"
    echo -n "Create an SSL certificate for ${certname}? (Recommended) [y/N]"
    if [ $assume_yes == 0 ]; then
        read answer
        if [[ $answer != "y" ]]; then
            read -e -p "Enter an alternate certificate name: " -i "${certname}" certname
        fi
    else
        echo -e
    fi

    echo -n "Install to Lighttpd SSL directory: ${lighttpd_ssl}? [y/N]: "
    if [ $assume_yes == 0 ]; then
        read answer
        if [[ $answer != "y" ]]; then
            read -e -p "Enter alternate Lighttpd SSL directory: " -i "${lighttpd_ssl}/" lighttpd_ssl
        fi
    else
        echo -e
    fi
    echo -e "\033[1;32m***************************************************************$*\033[m"
    echo "A new SSL certificate for: ${certname}"
    echo "will be installed to Lighttpd SSL directory: ${lighttpd_ssl}"
    echo -e "\033[1;32m***************************************************************$*\033[m"
    echo -n "Complete installation with these values? [y/N]: "
    if [ $assume_yes == 0 ]; then
        read answer
        if [[ $answer != "y" ]]; then
            echo "Installation aborted."
            exit 0
        fi
    else
        echo -e
    fi
}

# Installs pre-built mkcert binary for Arch Linux ARM
function install_mkcert() {
    install_log "Fetching mkcert binary"
    sudo wget https://github.com/FiloSottile/mkcert/releases/download/v1.3.0/mkcert-v1.3.0-linux-arm -O /usr/local/bin/mkcert || install_error "Unable to download mkcert"
    sudo chmod +x /usr/local/bin/mkcert

    install_log "Installing mkcert"
    mkcert -install || install_error "Failed to install mkcert"
}

# Generate a certificate for host
function generate_certificate() {
    install_log "Generating a new certificate for $certname"
    cd /home/pi
    mkcert $certname "*.${certname}.local" $certname || install_error "Failed to generate certificate for $certname"

    install_log "Combining private key and certificate"
    cat $certname+2-key.pem $certname+2.pem > $certname.pem || install_error "Failed to combine key and certificate"
}

# Create a directory for the combined .pem file in lighttpd
function create_lighttpd_dir() {
    #todo: check for existence
    install_log "Create SLL directory for lighttpd"
    sudo mkdir -p "$lighttpd_ssl" || install_error "Failed to create lighttpd directory"

    install_log "Setting permissions and moving the .pem file"
    chmod 400 /home/pi/"$certname".pem || install_error "Unable to set permissions for .pem file"
    sudo mv /home/pi/"$certname".pem /etc/lighttpd/ssl
}

# Edit the lighttpd configuration
function configure_lighttpd() {
    install_log "Configuring lighttpd for SSL"


}

# Copy rootCA.pem to RaspAP web root
function copy_rootca() {
    install_log "Copying rootCA.pem to RaspAP web root"
    sudo cp /home/pi/.local/share/mkcert/rootCA.pem ${webroot_dir}
}

function install_complete() {
    install_log "Installation completed!"

    if [ "${assume_yes:-}" = 0 ]; then
        # Prompt to reboot if wired ethernet (eth0) is connected.
        # With default_configuration this will create an active AP on restart.
        echo "ok"
    fi
}

function install_certificate() {
    display_welcome
    config_installation
    install_mkcert
    generate_certificate
    create_lighttpd_dir 
    configure_lighttpd
    copy_rootca
    restart_lighttpd
    install_complete
}

