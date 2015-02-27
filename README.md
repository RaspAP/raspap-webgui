![](http://i.imgur.com/xeKD93p.png)
# `$ raspap-webgui` [![Release 1.0](https://img.shields.io/badge/Release-1.0-green.svg)](https://github.com/billz/raspap-webgui/releases)
A simple, responsive web interface to control wifi, hostapd and related services on the Raspberry Pi.

We'd be curious to hear about how you use this with your own Pi-powered access points. Ping us on Twitter and ([**@billzimmerman**](https://twitter.com/billzimmerman)) and ([**@SirLagz**](https://twitter.com/SirLagz)). Until then, here's a screenshot:

![](http://i.imgur.com/c09ZTQS.png)

## Contents

 - [Installation](#installation)
 - [How to contribute](#how-to-contribute)

## Installation
Start off by installing lighttpd and php5.
```sh
$ apt-get install lighttpd php5-cgi
```
After that, enable PHP for lighttpd and restart it for the settings to take effect.
```sh
sudo lighty-enable-mod fastcgi-php
/etc/init.d/lighttpd restart
```
Now, comes the fun part.
For security reasons, the www-data user which lighttpd runs under is not allowed to start or stop daemons, or run commands like ifdown and ifup, all of which we want our page to do.
So what I have done is added the www-data user to the sudoers file, but with restrictions on what commands the user can run.
Add the following to the end of  `/etc/sudoers`: 

```sh
www-data ALL=(ALL) NOPASSWD:/sbin/ifdown wlan0,/sbin/ifup wlan0,/bin/cat /etc/wpa_supplicant/wpa_supplicant.conf,/bin/cp /tmp/wifidata /etc/wpa_supplicant/wpa_supplicant.conf,/sbin/wpa_cli scan_results,/sbin/wpa_cli scan,/bin/cp /tmp/hostapddata /etc/hostapd/hostapd.conf,/etc/init.d/hostapd start,/etc/init.d/hostapd stop,/etc/init.d/dnsmasq start,/etc/init.d/dnsmasq stop,/bin/cp /tmp/dhcpddata /etc/dnsmasq.conf
```
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