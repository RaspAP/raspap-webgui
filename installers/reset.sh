
function reset_default_configuration() {
    webroot_dir=cat /etc/raspap/hostapd/reset.ini | grep --only-matching --perl-regexp "(?<=webroot_dir = ).*" || echo -e "Unable to read from reset configuration file"
    user_reset_files=cat /etc/raspap/hostapd/reset.ini | grep --only-matching --perl-regexp "(?<=user_reset_files = ).*" || echo -e "Unable to read from reset configuration file"
    if ["$user_reset_files" == "0"];
	    sudo cp $webroot_dir/config/hostapd.conf /etc/hostapd/hostapd.conf || echo -e "Unable to write hostapd configuration file"
	    sudo cp $webroot_dir/config/dnsmasq.conf /etc/dnsmasq.conf || echo -e "Unable to write dnsmasq configuration file"
	    sudo cp $webroot_dir/config/dhcpcd.conf /etc/dhcpcd.conf || echo -e "Unable to write dhcpcd configuration file"
	    sudo rm /etc/wpa_supplicant/wpa_supplicant.conf  || echo -e "Unable to remove WPA supplicant configuration file"
	    sudo rm /etc/wpa_supplicant/wpa_supplicant-wlan0.conf  || echo -e "Unable to remove wlan0 WPA supplicant configuration file"
	    sudo rm /etc/wpa_supplicant/wpa_supplicant-wlan1.conf  || echo -e "Unable to remove wlan1 WPA supplicant configuration file"
	    sudo rm /etc/raspap/raspap.auth  || echo -e "Unable to remove RaspAP authentication configuration file"
	else
	    sudo cp $webroot_dir/config/user_hostapd.conf /etc/hostapd/hostapd.conf || echo -e "Unable to write user hostapd configuration file"
	    sudo cp $webroot_dir/config/user_dnsmasq.conf /etc/dnsmasq.conf || echo -e "Unable to write user dnsmasq configuration file"
	    sudo cp $webroot_dir/config/user_dhcpcd.conf /etc/dhcpcd.conf || echo -e "Unable to write user dhcpcd configuration file"
	    sudo rm /etc/wpa_supplicant/wpa_supplicant.conf  || echo -e "Unable to remove WPA supplicant configuration file"
	    sudo rm /etc/wpa_supplicant/wpa_supplicant-wlan0.conf  || echo -e "Unable to remove wlan0 WPA supplicant configuration file"
	    sudo rm /etc/wpa_supplicant/wpa_supplicant-wlan1.conf  || echo -e "Unable to remove wlan1 WPA supplicant configuration file"
	    sudo cp $webroot_dir/config/user_wpa_supplicant.conf /etc/wpa_supplicant/wpa_supplicant.conf || echo -e "Unable to write WPA supplicant configuration file"
	    sudo cp $webroot_dir/config/user_wpa_supplicant-wlan0.conf /etc/wpa_supplicant/wpa_supplicant-wlan0.conf || echo -e "Unable to write wlan0 WPA supplicant configuration file"
	    sudo cp $webroot_dir/config/user_wpa_supplicant-wlan1.conf /etc/wpa_supplicant/wpa_supplicant-wlan1.conf || echo -e "Unable to write wlan1 WPA supplicant configuration file"
	    sudo rm /etc/raspap/raspap.auth  || echo -e "Unable to remove RaspAP authentication configuration file"
	    sudo cp $webroot_dir/config/user_raspap.auth /etc/raspap/raspap.auth  || echo -e "Unable to remove RaspAP authentication configuration file"
	fi
}

reset_default_configuration
