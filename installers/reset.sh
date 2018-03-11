
function reset_default_configuration() {
    if [ -f /etc/default/hostapd ]; then
        sudo mv /etc/default/hostapd /tmp/default_hostapd.old || echo -e "Unable to remove old /etc/default/hostapd file"
    fi
    sudo cp $webroot_dir/config/default_hostapd /etc/default/hostapd || echo -e "Unable to move hostapd defaults file"
    sudo cp $webroot_dir/config/hostapd.conf /etc/hostapd/hostapd.conf || echo -e "Unable to move hostapd configuration file"
    sudo cp $webroot_dir/config/dnsmasq.conf /etc/dnsmasq.conf || echo -e "Unable to move dnsmasq configuration file"
    sudo cp $webroot_dir/config/dhcpcd.conf /etc/dhcpcd.conf || echo -e "Unable to move dhcpcd configuration file"
}

reset_default_configuration
