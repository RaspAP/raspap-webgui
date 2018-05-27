#!/bin/bash

function reset_default_configuration() {

	webroot_dir=$(sudo cat /etc/raspap/hostapd/reset.ini | grep --only-matching --perl-regexp "(?<=webroot_dir = \")\S+(?=\")")
    user_reset_files=$(sudo cat /etc/raspap/hostapd/reset.ini | grep --only-matching --perl-regexp "(?<=user_reset_files = ).")

    if [ "$user_reset_files" == "0" ]; then
    	echo Restoring RaspAP defaults
	    sudo cp $webroot_dir/config/hostapd.conf /etc/hostapd/hostapd.conf
	    sudo cp $webroot_dir/config/dnsmasq.conf /etc/dnsmasq.conf
	    sudo cp $webroot_dir/config/dhcpcd.conf /etc/dhcpcd.conf
	    sudo cp $webroot_dir/config/wpa_supplicant.conf /etc/wpa_supplicant/wpa_supplicant.conf
	    sudo cp $webroot_dir/config/wpa_supplicant_wlan0.conf /etc/wpa_supplicant/wpa_supplicant_wlan0.conf
	    sudo cp $webroot_dir/config/wpa_supplicant_wlan1.conf /etc/wpa_supplicant/wpa_supplicant_wlan1.conf
	    sudo rm /etc/raspap/raspap.auth
	else
		echo Restoring user defaults
	    sudo cp $webroot_dir/config/user_hostapd.conf /etc/hostapd/hostapd.conf
	    sudo cp $webroot_dir/config/user_dnsmasq.conf /etc/dnsmasq.conf
	    sudo cp $webroot_dir/config/user_dhcpcd.conf /etc/dhcpcd.conf
	    sudo cp $webroot_dir/config/user_wpa_supplicant.conf /etc/wpa_supplicant/wpa_supplicant.conf
	    sudo cp $webroot_dir/config/user_wpa_supplicant_wlan0.conf /etc/wpa_supplicant/wpa_supplicant_wlan0.conf
	    sudo cp $webroot_dir/config/user_wpa_supplicant_wlan1.conf /etc/wpa_supplicant/wpa_supplicant_wlan1.conf
		sudo rm /etc/raspap/raspap.auth
	    sudo cp $webroot_dir/config/user_raspap.auth /etc/raspap/raspap.auth
	fi
}

reset_default_configuration
