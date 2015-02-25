raspapwebgui
=============
Started and modified from here http://sirlagz.net/2013/02/08/raspap-webgui/

Found this article very helpful for setting up DHCP (https://learn.adafruit.com/setting-up-a-raspberry-pi-as-a-wifi-access-point/install-software)

Requirements
============
A raspberry pi with raspbian running on it. You will need to ssh into it to set this up.

The Packages required for the WebGUI are:
* lighttpd
* php5-cgi
* isc-dhcp-server
* git

Steps
=====
1. Install required packages

  `sudo apt-get install lighttpd php5-cgi git isc-dhcp-server`
2. Enable php in lighttpd

  ```
  sudo lighty-enable-mod fastcgi-php
  sudo service lighttpd restart
  ```
3. Edit `/etc/sudoers` to allow the *www-data* user to call the necessary commands. Add the following line to the end of the file.


  ```
  www-data ALL=(ALL) NOPASSWD:/sbin/ifdown wlan0,/sbin/ifup wlan0,/bin/cat /etc/wpa_supplicant/wpa_supplicant.conf,/bin/cp /tmp/wifidata /etc/wpa_supplicant/wpa_supplicant.conf,/sbin/wpa_cli scan_results,/sbin/wpa_cli scan,/bin/cp /tmp/hostapddata /etc/hostapd/hostapd.conf,/etc/init.d/hostapd start,/etc/init.d/hostapd stop,/etc/init.d/dnsmasq start,/etc/init.d/dnsmasq stop,/bin/cp /tmp/dhcpddata /etc/dnsmasq.conf
  ```
4. Once those modifications are done, git clone the files to `/var/www`. Make sure that there are no files in the `/var/www` directory. There was a default lighttpd index file that I had to delete.

  `sudo git clone https://github.com/rjpcomputing/raspap-webgui.git /var/www`
5. Set the files ownership to `www-data` user.

  `sudo chown -R www-data:www-data /var/www`
6. Configure a static IP for the Pi
  * Edit `/etc/network/interfaces`
  
  `sudo nano /etc/network/interfaces`
  * Find and remove dhcp entry
  `iface eth0 inet dhcp`
  * Append new network settings
  ```
  auto eth0
  allow-hotplug eth0
  iface eth0 inet static
  address 10.0.0.1
  netmask 255.255.255.0
  dns-nameservers 8.8.8.8 8.8.4.4
  ```
  * Find and change `iface wlan0 inet manual` to `iface wlan0 inet dhcp`. This is under the wlan0 setup.
  * Comment out `iface default inet dhcp` and `allow-hotplug wlan0` using the '#' character at the beginning of the line(s).
  * Example of `/etc/network/interfaces` after all changes are made:
  ```
  auto lo
  iface lo inet loopback
  
  #iface eth0 inet dhcp
  auto eth0
  allow-hotplug eth0
  iface eth0 inet static
  address 10.0.0.1
  netmask 255.255.255.0
  dns-nameservers 8.8.8.8 8.8.4.4
  
  auto wlan0
  #allow-hotplug wlan0
  iface wlan0 inet dhcp
  wpa-conf /etc/wpa_supplicant/wpa_supplicant.conf
  #iface default inet dhcp

  ```
7. Configure `dhcpd` by editing `/etc/dhcp/dhcpd.conf`
  * `sudo nano /etc/dhcp/dhcpd.conf`
  * Add the following to the end of the file
  ```
  subnet 10.0.0.0 netmask 255.255.255.0 
  {
    range 10.0.0.50 10.0.0.99;
    option routers 10.0.0.254;
    option domain-name "local";
    option domain-name-servers 8.8.8.8, 8.8.4.4;
  }
  ```
8. Open `/etc/default/isc-dhcp-server` and change the `INTERFACES=""` to `INTERFACES="eth0"`

  `sudo nano /etc/default/isc-dhcp-server`
9. Install and configure the auto-reconnect WiFi script (wifi-check).
  * Copy 'wifi-check' to `/usr/local/bin/wifi-check`
    * `sudo cp /var/www/wifi-check /usr/local/bin/wifi-check`
    * `sudo chmod +x /usr/local/bin/wifi-check`
  * Add it to cron so it runs every 5 minutes
    * `sudo crontab -e`
    * Append the line `*/5 * * * * /usr/local/bin/wifi-check > /tmp/wificheck.log 2>&1` to the end of the file
    * Save the file
* Reboot and it should be up and running!
  
  `sudo reboot`