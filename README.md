![](http://i.imgur.com/xeKD93p.png)
# `$ raspap-webgui` [![Release 1.4.1](https://img.shields.io/badge/Release-1.4.1-green.svg)](https://github.com/billz/raspap-webgui/releases) [![Awesome](https://awesome.re/badge.svg)](https://github.com/thibmaek/awesome-raspberry-pi) 

A simple, responsive web interface to control wifi, hostapd and related services on the Raspberry Pi.

This project was inspired by a [**blog post**](http://sirlagz.net/2013/02/06/script-web-configuration-page-for-raspberry-pi/) by SirLagz about using a web page rather than ssh to configure wifi and hostapd settings on the Raspberry Pi. I began by prettifying the UI by wrapping it in [**SB Admin 2**](https://github.com/BlackrockDigital/startbootstrap-sb-admin-2), a Bootstrap based admin theme. Since then, the project has evolved to include greater control over many aspects of a networked RPi, better security, authentication, a Quick Installer, support for themes and more. RaspAP has been featured on sites such as [Instructables](http://www.instructables.com/id/Raspberry-Pi-As-Completely-Wireless-Router/), [Adafruit](https://blog.adafruit.com/2016/06/24/raspap-wifi-configuration-portal-piday-raspberrypi-raspberry_pi/), [Raspberry Pi Weekly](https://www.raspberrypi.org/weekly/commander/) and [Awesome Raspberry Pi](https://project-awesome.org/thibmaek/awesome-raspberry-pi) and implemented in countless projects.

We'd be curious to hear about how you use this with your own RPi-powered projects. Until then, here are some screenshots:

![](https://i.imgur.com/lQ57jVg.png)
![](https://i.imgur.com/jFDMEy6.png)
![](https://i.imgur.com/ck0XS8P.png)
![](https://i.imgur.com/Vaej8Xv.png)
![](https://i.imgur.com/iNuMMip.png)
## Contents

 - [Prerequisites](#prerequisites)
 - [Quick installer](#quick-installer)
 - [Support us](#support-us)
 - [Manual installation](#manual-installation)
 - [Multilingual support](#multilingual-support)
 - [Optional services](#optional-services)
 - [How to contribute](#how-to-contribute)
 - [License](#license)

## Prerequisites
Start with a clean install of the [latest release of Raspbian](https://www.raspberrypi.org/downloads/raspbian/) (currently Stretch). Raspbian Stretch Lite is recommended.

1. Update Raspbian, including the kernel and firmware, followed by a reboot:
```
sudo apt-get update
sudo apt-get dist-upgrade
sudo reboot
```
2. Set the WiFi country in raspi-config's **Localisation Options**: `sudo raspi-config`

3. If you have an older Raspberry Pi without an onboard WiFi chipset, the [**Edimax Wireless 802.11b/g/n nano USB adapter**](https://www.edimax.com/edimax/merchandise/merchandise_detail/data/edimax/global/wireless_adapters_n150/ew-7811un) is an excellent option – it's small, cheap and has good driver support.

With the prerequisites done, you can proceed with either the Quick installer or Manual installation steps below.

## Quick installer
Install RaspAP from your RaspberryPi's shell prompt:
```sh
$ wget -q https://git.io/voEUQ -O /tmp/raspap && bash /tmp/raspap
```
The installer will complete the steps in the manual installation (below) for you.

After the reboot at the end of the installation the wireless network will be
configured as an access point as follows:
* IP address: 10.3.141.1
  * Username: admin
  * Password: secret
* DHCP range: 10.3.141.50 to 10.3.141.255
* SSID: `raspi-webgui`
* Password: ChangeMe

## Support us

RaspAP is free software, but powered by your support. If you find RaspAP useful for your personal projects, please consider making a small donation. We feel strongly about creating high quality, easy-to-use software, as well as the importance of keeping it maintained. 

[![Beerpay](https://beerpay.io/billz/raspap-webgui/badge.svg?style=flat)](https://beerpay.io/billz/raspap-webgui)

## Manual installation
These steps apply to the latest release of Raspbian (currently [Stretch](https://www.raspberrypi.org/downloads/raspbian/)). Notes for previously released versions are provided, where applicable. Start off by installing git, lighttpd, php7, hostapd and dnsmasq. 
```sh
$ sudo apt-get install git lighttpd php7.0-cgi hostapd dnsmasq vnstat
```
**Note:** for Raspbian Jessie and Wheezy, replace `php7.0-cgi` with `php5-cgi`. After that, enable PHP for lighttpd and restart it for the settings to take effect.
```sh
sudo lighttpd-enable-mod fastcgi-php
sudo service lighttpd restart
```
Now comes the fun part. For security reasons, the `www-data` user which lighttpd runs under is not allowed to start or stop daemons, or run commands like ifdown and ifup, all of which we want our page to do.
So what I have done is added the `www-data` user to the sudoers file, but with restrictions on what commands the user can run. Add the following to the end of `/etc/sudoers`, substituting your wireless interface for `wlan0` if needed: 

```sh
www-data ALL=(ALL) NOPASSWD:/sbin/ifdown wlan0
www-data ALL=(ALL) NOPASSWD:/sbin/ifup wlan0
www-data ALL=(ALL) NOPASSWD:/bin/cat /etc/wpa_supplicant/wpa_supplicant.conf
www-data ALL=(ALL) NOPASSWD:/bin/cp /tmp/wifidata /etc/wpa_supplicant/wpa_supplicant.conf
www-data ALL=(ALL) NOPASSWD:/sbin/wpa_cli -i wlan0 scan_results
www-data ALL=(ALL) NOPASSWD:/sbin/wpa_cli -i wlan0 scan
www-data ALL=(ALL) NOPASSWD:/sbin/wpa_cli -i wlan0 reconfigure
www-data ALL=(ALL) NOPASSWD:/bin/cp /tmp/hostapddata /etc/hostapd/hostapd.conf
www-data ALL=(ALL) NOPASSWD:/etc/init.d/hostapd start
www-data ALL=(ALL) NOPASSWD:/etc/init.d/hostapd stop
www-data ALL=(ALL) NOPASSWD:/etc/init.d/dnsmasq start
www-data ALL=(ALL) NOPASSWD:/etc/init.d/dnsmasq stop
www-data ALL=(ALL) NOPASSWD:/bin/cp /tmp/dhcpddata /etc/dnsmasq.conf
www-data ALL=(ALL) NOPASSWD:/sbin/shutdown -h now
www-data ALL=(ALL) NOPASSWD:/sbin/reboot
www-data ALL=(ALL) NOPASSWD:/sbin/ip link set wlan0 down
www-data ALL=(ALL) NOPASSWD:/sbin/ip link set wlan0 up
www-data ALL=(ALL) NOPASSWD:/sbin/ip -s a f label wlan0
www-data ALL=(ALL) NOPASSWD:/bin/cp /etc/raspap/networking/dhcpcd.conf /etc/dhcpcd.conf
www-data ALL=(ALL) NOPASSWD:/etc/raspap/hostapd/enablelog.sh
www-data ALL=(ALL) NOPASSWD:/etc/raspap/hostapd/disablelog.sh
```

Once those modifications are done, git clone the files to `/var/www/html`.
**Note:** for older versions of Raspbian (before Jessie, May 2016) use
`/var/www` instead.
```sh
sudo rm -rf /var/www/html
sudo git clone https://github.com/billz/raspap-webgui /var/www/html
```
Set the files ownership to `www-data` user.
```sh
sudo chown -R www-data:www-data /var/www/html
```
Move the RaspAP configuration file to the correct location
```sh
sudo mkdir /etc/raspap
sudo mv /var/www/html/raspap.php /etc/raspap/
sudo chown -R www-data:www-data /etc/raspap
```
Move the HostAPD logging scripts to the correct location
```sh
sudo mkdir /etc/raspap/hostapd
sudo mv /var/www/html/installers/*log.sh /etc/raspap/hostapd 
```
Reboot and it should be up and running!
```sh
sudo reboot
```

The default username is 'admin' and the default password is 'secret'.

## Multilingual support
RaspAP uses [GNU Gettext](https://www.gnu.org/software/gettext/) to manage multilingual messages. In order to use RaspAP with one of our supported translations, you must configure a corresponding language package on your RPi. To list languages currently installed on your system, use `locale -a` at the shell prompt. To generate new locales, run `sudo dpkg-reconfigure locales` and select any other desired locales. Details are provided on our [wiki](https://github.com/billz/raspap-webgui/wiki/Translations#raspap-in-your-language). 

The following translations are currently maintained by the project:

- Deutsch
- Français
- Italiano
- Português
- Svenska
- Nederlands
- 简体中文 (Chinese Simplified)
- Čeština
- Русский
- Español
- Finnish
- Sinhala

If your language is not in the list above, why not [contribute a translation](https://github.com/billz/raspap-webgui/wiki/Translations#contributing-a-translation)? Contributors will receive credit as the original translators.

## Optional services
OpenVPN and TOR are two additional services that run perfectly well on the RPi, and are a nice way to extend the usefulness of your WiFi router. I've started on interfaces to administer these services. Not everyone will need them, so for the moment they are disabled by default. You can enable them by changing these options in `/var/www/html/includes/config.php`:

```sh
// Optional services, set to true to enable.
define('RASPI_OPENVPN_ENABLED', false );
define('RASPI_TORPROXY_ENABLED', false );
```
Please note that these are only UI's for now. If there's enough interest I'll complete the funtionality for these optional admin screens.

## How to contribute

1. File an issue in the repository, using the bug tracker, describing the
   contribution you'd like to make. This will help us to get you started on the
   right foot.
2. Fork the project in your account and create a new branch:
   `your-great-feature`.
3. Commit your changes in that branch.
4. Open a pull request, and reference the initial issue in the pull request
   message.

## License
See the [LICENSE](./LICENSE) file.

