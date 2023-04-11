#!/bin/bash
#
# RaspAP SSL certificate installation functions
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
certname=$HOSTNAME."local"
lighttpd_ssl="/etc/lighttpd/ssl"
lighttpd_conf="/etc/lighttpd/lighttpd.conf"
webroot_dir="/var/www/html"
readonly mkcert_version="1.4.1"
readonly git_source_url="https://github.com/FiloSottile/mkcert/releases/download/v${mkcert_version}"

### NOTE: all the below functions are overloadable for system-specific installs

function _config_installation() {
    _install_log "Configure a new SSL certificate"
    echo "Current system hostname is $HOSTNAME"
    echo -n "Create an SSL certificate for ${certname}? (Recommended) [y/N]"
    if [ $assume_yes == 0 ]; then
        read answer < /dev/tty
        if [[ $answer != "y" ]]; then
            read -e -p < /dev/tty "Enter an alternate certificate name: " -i "${certname}" certname
        fi
    else
        echo -e
    fi

    echo -n "Install to lighttpd SSL directory: ${lighttpd_ssl}? [y/N]: "
    if [ $assume_yes == 0 ]; then
        read answer < /dev/tty
        if [[ $answer != "y" ]]; then
            read -e -p < /dev/tty "Enter alternate lighttpd SSL directory: " -i "${lighttpd_ssl}" lighttpd_ssl
        fi
    else
        echo -e
    fi

    _install_divider
    echo "A new SSL certificate for: ${certname}"
    echo "will be installed to lighttpd SSL directory: ${lighttpd_ssl}"
    _install_divider
    echo -n "Complete installation with these values? [y/N]: "
    if [ $assume_yes == 0 ]; then
        read answer < /dev/tty
        if [[ $answer != "y" ]]; then
            echo "Installation aborted."
            exit 0
        fi
    else
        echo -e
    fi
}

# Installs pre-built mkcert binary for Arch Linux ARM
function _install_mkcert() {
    _install_log "Fetching mkcert binary"
    sudo wget -q ${git_source_url}/mkcert-v${mkcert_version}-linux-arm -O /usr/local/bin/mkcert || _install_error "Unable to download mkcert"
    sudo chmod +x /usr/local/bin/mkcert

    _install_log "Installing mkcert"
    mkcert -install || _install_error "Failed to install mkcert"
}

# Generate a certificate for host
function _generate_certificate() {
    _install_log "Generating a new certificate for $certname"
    cd $HOME
    mkcert $certname "*.${certname}.local" $certname || _install_error "Failed to generate certificate for $certname"

    _install_log "Combining private key and certificate"
    cat $certname+2-key.pem $certname+2.pem > $certname.pem || _install_error "Failed to combine key and certificate"
    echo "OK"
}

# Create a directory for the combined .pem file in lighttpd
function _create_lighttpd_dir() {
    _install_log "Creating SLL directory for lighttpd"
    if [ ! -d "$lighttpd_ssl" ]; then
	sudo mkdir -p "$lighttpd_ssl" || _install_error "Failed to create lighttpd directory"
    fi
    echo "OK"

    _install_log "Setting permissions and moving .pem file"
    chmod 400 "$HOME/$certname".pem || _install_error "Unable to set permissions for .pem file"
    sudo mv "$HOME/$certname".pem /etc/lighttpd/ssl || _install_error "Unable to move .pem file"
    echo "OK"
}

# Generate config to enable SSL in lighttpd
function _configure_lighttpd() {
    _install_log "Configuring lighttpd for SSL"
    lines=(
        'server.modules += ("mod_openssl")'
        '$SERVER["socket"] == ":443" {'
        'ssl.engine = "enable"'
        'ssl.pemfile = "'$lighttpd_ssl/$certname'.pem"'
        'ssl.ca-file = "'$HOME'/.local/share/mkcert/rootCA.pem"'
        'server.name = "'$certname'"'
        'server.document-root = "'${webroot_dir}'"}'
    )
    for line in "${lines[@]}"; do
        if grep -Fxq "${line}" "${lighttpd_conf}" > /dev/null; then
            echo "$line: Line already added"
        else
            sudo sed -i "$ a $line" $lighttpd_conf
            echo "Adding line $line"
        fi
    done
    echo "OK"
}

# Copy rootCA.pem to RaspAP web root
function _copy_rootca() {
    _install_log "Copying rootCA.pem to RaspAP web root"
    sudo cp ${HOME}/.local/share/mkcert/rootCA.pem ${webroot_dir} || _install_error "Unable to copy rootCA.pem to ${webroot_dir}"
    echo "OK"
}

# Restart lighttpd service
function _restart_lighttpd() {
    _install_log "Restarting lighttpd service"
    sudo systemctl restart lighttpd.service || _install_error "Unable to restart lighttpd service"
    sudo systemctl status lighttpd.service
}

function _install_complete() {
    _install_log "SSL certificate install completed!"
    _install_divider
    printf '%s\n' \
    "Open a browser and enter the address: http://$certname/rootCA.pem" \
    "Download the root certificate to your client and add it to your system keychain." \
    "Note: Be sure to set this certificate to 'Always trust' to avoid browser warnings." \
    "Finally, enter the address https://$certname in your browser." \
    "Enjoy an encrypted SSL connection to RaspAP 🔒" \
    "For advanced options, run mkcert -help"
    _install_divider
}

function _install_certificate() {
    _display_welcome
    _config_installation
    _install_mkcert
    _generate_certificate
    _create_lighttpd_dir
    _configure_lighttpd
    _copy_rootca
    _restart_lighttpd
    _install_complete
}

