![](https://i.imgur.com/xeKD93p.png)
# `$ raspap` [![Release 2.0](https://img.shields.io/badge/Release-2.0-green.svg)](https://github.com/billz/raspap-webgui/releases) [![Awesome](https://awesome.re/badge.svg)](https://github.com/thibmaek/awesome-raspberry-pi) [![Sponsor](https://img.shields.io/badge/sponsor-%F0%9F%92%96-green)](https://github.com/sponsors/billz)

A simple, responsive web interface to control wifi, hostapd and related services on the Raspberry Pi.

This project was inspired by a [blog post](http://sirlagz.net/2013/02/06/script-web-configuration-page-for-raspberry-pi/) by SirLagz about using a web page rather than ssh to configure wifi and hostapd settings on the Raspberry Pi. I began by prettifying the UI by wrapping it in [SB Admin 2](https://github.com/BlackrockDigital/startbootstrap-sb-admin-2), a Bootstrap based admin theme. Since then, the project has evolved to include greater control over many aspects of a networked RPi, better security, authentication, a Quick Installer, support for themes and more. RaspAP has been featured on sites such as [Instructables](http://www.instructables.com/id/Raspberry-Pi-As-Completely-Wireless-Router/), [Adafruit](https://blog.adafruit.com/2016/06/24/raspap-wifi-configuration-portal-piday-raspberrypi-raspberry_pi/), [Raspberry Pi Weekly](https://www.raspberrypi.org/weekly/commander/) and [Awesome Raspberry Pi](https://project-awesome.org/thibmaek/awesome-raspberry-pi) and implemented in countless projects.

We'd be curious to hear about how you use this with [your own RPi-powered projects](https://github.com/billz/raspap-awesome). Until then, here are some screenshots:

![](https://i.imgur.com/nEX6J8D.gif)
![](https://i.imgur.com/EiIpdOS.gif)
![](https://i.imgur.com/eCjUS1H.gif)
![](https://i.imgur.com/5FT2BcS.gif)
![](https://i.imgur.com/RKaBFrZ.gif)
## Contents

 - [Prerequisites](#prerequisites)
 - [Quick installer](#quick-installer)
 - [Simultaneous AP and Wifi client](#simultaneous-ap-and-wifi-client)
 - [Support us](#support-us)
 - [Manual installation](#manual-installation)
 - [Multilingual support](#multilingual-support)
 - [HTTPS support](#https-support)
 - [Optional services](#optional-services)
 - [How to contribute](#how-to-contribute)
 - [Reporting issues](#reporting-issues)
 - [License](#license)

## Prerequisites
Start with a clean install of the [latest release of Raspbian](https://www.raspberrypi.org/downloads/raspbian/) (currently Buster). Raspbian Buster Lite is recommended.

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
wget -q https://git.io/voEUQ -O /tmp/raspap && bash /tmp/raspap
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

**Note:** As the name suggests, the Quick Installer is a great way to quickly setup a new AP. However, it does not automagically detect the unique configuration of your RPi. Best results are obtained by connecting an RPi to ethernet (`eth0`) or as a WiFi client, also known as managed mode, with `wlan0`. For the latter, refer to [this FAQ](https://github.com/billz/raspap-webgui/wiki/FAQs#how-do-i-prepare-the-sd-card-to-connect-to-wifi-in-headless-mode). Please [read this](https://github.com/billz/raspap-webgui/wiki/Reporting-issues) before reporting an issue.

## Simultaneous AP and Wifi client
RaspAP lets you easily create an AP with a Wifi client configuration. With your RPi configured in managed mode, enable the AP from the **Advanced** tab of **Configure hotspot** by sliding the **Wifi client AP mode** toggle. Save settings and start the hotspot. The managed mode AP is functional without restart.

![](https://i.imgur.com/4JkK2a5.png)

**Note:** For a Raspberry Pi operating in [managed mode](https://github.com/billz/raspap-webgui/wiki/FAQs#how-do-i-prepare-the-sd-card-to-connect-to-wifi-in-headless-mode) without an `eth0` connection, this configuration must be enabled _before_ a reboot. 

## Support us

RaspAP is free software, but powered by your support. If you find RaspAP useful for your personal or commercial projects, please [become a sponsor](https://github.com/sponsors/billz) or make a one-time donation with [Beerpay](https://beerpay.io/billz/raspap-webgui). Either option makes a big difference!

[![Beerpay](https://beerpay.io/billz/raspap-webgui/badge.svg)](https://beerpay.io/billz/raspap-webgui)

## Manual installation
These steps apply to the latest release of Raspbian (currently [Buster](https://www.raspberrypi.org/downloads/raspbian/)). Notes for previously released versions are provided, where applicable. Start off by installing git, lighttpd, php7, hostapd and dnsmasq. 
```sh
sudo apt-get install git lighttpd php7.1-cgi hostapd dnsmasq vnstat
```
**Note:** for Raspbian Stretch, replace `php7.1-cgi` with `php7.0-cgi`. For Raspbian Jessie and older versions, use `php5-cgi`. After that, enable PHP for lighttpd and restart it for the settings to take effect.
```sh
sudo lighttpd-enable-mod fastcgi-php
sudo service lighttpd restart
```
Now comes the fun part. For security reasons, the `www-data` user which lighttpd runs under is not allowed to start or stop daemons, or run commands like ifdown and ifup, all of which we want our page to do.
So what I have done is added the `www-data` user to the sudoers file, but with restrictions on what commands the user can run. Add the following to the end of `/etc/sudoers`: 

```sh
www-data ALL=(ALL) NOPASSWD:/sbin/ifdown
www-data ALL=(ALL) NOPASSWD:/sbin/ifup
www-data ALL=(ALL) NOPASSWD:/bin/cat /etc/wpa_supplicant/wpa_supplicant.conf
www-data ALL=(ALL) NOPASSWD:/bin/cat /etc/wpa_supplicant/wpa_supplicant-wlan[0-9].conf
www-data ALL=(ALL) NOPASSWD:/bin/cp /tmp/wifidata /etc/wpa_supplicant/wpa_supplicant.conf
www-data ALL=(ALL) NOPASSWD:/bin/cp /tmp/wifidata /etc/wpa_supplicant/wpa_supplicant-wlan[0-9].conf
www-data ALL=(ALL) NOPASSWD:/sbin/wpa_cli -i wlan[0-9] scan_results
www-data ALL=(ALL) NOPASSWD:/sbin/wpa_cli -i wlan[0-9] scan
www-data ALL=(ALL) NOPASSWD:/sbin/wpa_cli -i wlan[0-9] reconfigure
www-data ALL=(ALL) NOPASSWD:/bin/cp /tmp/hostapddata /etc/hostapd/hostapd.conf
www-data ALL=(ALL) NOPASSWD:/etc/init.d/hostapd start
www-data ALL=(ALL) NOPASSWD:/etc/init.d/hostapd stop
www-data ALL=(ALL) NOPASSWD:/etc/init.d/dnsmasq start
www-data ALL=(ALL) NOPASSWD:/etc/init.d/dnsmasq stop
www-data ALL=(ALL) NOPASSWD:/bin/cp /tmp/dhcpddata /etc/dnsmasq.conf
www-data ALL=(ALL) NOPASSWD:/sbin/shutdown -h now
www-data ALL=(ALL) NOPASSWD:/sbin/reboot
www-data ALL=(ALL) NOPASSWD:/sbin/ip link set wlan[0-9] down
www-data ALL=(ALL) NOPASSWD:/sbin/ip link set wlan[0-9] up
www-data ALL=(ALL) NOPASSWD:/sbin/ip -s a f label wlan[0-9]
www-data ALL=(ALL) NOPASSWD:/bin/cp /etc/raspap/networking/dhcpcd.conf /etc/dhcpcd.conf
www-data ALL=(ALL) NOPASSWD:/etc/raspap/hostapd/enablelog.sh
www-data ALL=(ALL) NOPASSWD:/etc/raspap/hostapd/disablelog.sh
www-data ALL=(ALL) NOPASSWD:/etc/raspap/hostapd/servicestart.sh
```

Once those modifications are done, git clone the files to `/var/www/html`.
**Note:** for older versions of Raspbian (before Jessie, May 2016) use
`/var/www` instead.
```sh
sudo rm -rf /var/www/html
sudo git clone https://github.com/billz/raspap-webgui /var/www/html
```
Move the high-res favicons to the web root.
```
sudo mv /var/www/html/app/icons/* /var/www/html
```
Set the files ownership to `www-data` user.
```sh
sudo chown -R www-data:www-data /var/www/html
```
Move the RaspAP configuration file to the correct location.
```sh
sudo mkdir /etc/raspap
sudo mv /var/www/html/raspap.php /etc/raspap/
sudo chown -R www-data:www-data /etc/raspap
```
Move the HostAPD logging and service control shell scripts to the correct location.
```sh
sudo mkdir /etc/raspap/hostapd
sudo mv /var/www/html/installers/*log.sh /etc/raspap/hostapd 
sudo mv /var/www/html/installers/service*.sh /etc/raspap/hostapd
```
Set ownership and permissions for logging and service control scripts.
```sh
sudo chown -c root:www-data /etc/raspap/hostapd/*.sh 
sudo chmod 750 /etc/raspap/hostapd/*.sh 
```
Add the following lines to `/etc/rc.local` before `exit 0`.
```sh
echo 1 > /proc/sys/net/ipv4/ip_forward #RASPAP
iptables -t nat -A POSTROUTING -j MASQUERADE #RASPAP 
iptables -t nat -A POSTROUTING -s 192.168.50.0/24 ! -d 192.168.50.0/24 -j MASQUERADE #RASPAP
```
Force a reload of new settings in `/etc/rc.local`.
```sh
sudo systemctl restart rc-local.service
sudo systemctl daemon-reload
```
Unmask and enable the hostapd service.
```sh
sudo systemctl unmask hostapd.service
sudo systemctl enable hostapd.service
```
Move the raspap service to the correct location and enable it.
```
sudo mv /var/www/html/installers/raspap.service /lib/systemd/system
sudo systemctl enable raspap.service
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
- Indonesian
- 한국어 (Korean)
- 日本語 (Japanese)
- Tiếng Việt (Vietnamese)
- Čeština
- Русский
- Español
- Finnish
- Sinhala
- Türkçe

If your language is not in the list above, why not [contribute a translation](https://github.com/billz/raspap-webgui/wiki/Translations#contributing-a-translation)? Contributors will receive credit as the original translators.

## HTTPS support
A detailed how-to for enabling HTTPS is available [on our wiki](https://github.com/billz/raspap-webgui/wiki/HTTPS--support). 

## Optional services
OpenVPN and TOR are two additional services that run perfectly well on the RPi, and are a nice way to extend the usefulness of your WiFi router. I've started on interfaces to administer these services. Not everyone will need them, so for the moment they are disabled by default. You can enable them by changing these options in `/var/www/html/includes/config.php`:

```sh
// Optional services, set to true to enable.
define('RASPI_OPENVPN_ENABLED', false );
define('RASPI_TORPROXY_ENABLED', false );
```
Please note that these are only UI's for now. If there's enough interest I'll complete the funtionality for these optional admin screens.

## How to contribute

1. File an issue in the repository describing the contribution you'd like to make. This will help us get you started on the
   right foot.
2. Fork the project in your account and create a new branch: `your-great-feature`.
3. Commit your changes in that branch.
4. Open a pull request, and reference the initial issue in the pull request message.

This project follows the [PSR-2](http://www.php-fig.org/psr/psr-2/) coding style guidelines. There are many ways to check your code for PSR-2. An excellent tool is [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer). The command line tool `phpcs` can be run against any single file. [Phing](https://www.phing.info/), a PHP build tool, integrates nicely with `phpcs` to automate PSR-2 checks across all source files in a project.

## Reporting issues
Please [read this](https://github.com/billz/raspap-webgui/wiki/Reporting-issues) before reporting a bug.

## License
See the [LICENSE](./LICENSE) file.

