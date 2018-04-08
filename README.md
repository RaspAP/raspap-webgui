![](http://i.imgur.com/xeKD93p.png)
# `$ raspap-webgui` [![Release 1.3.1](https://img.shields.io/badge/Release-1.3.1-green.svg)](https://github.com/billz/raspap-webgui/releases)
A simple, responsive web interface to control wifi, hostapd and related services on the Raspberry Pi.

This project was inspired by a [**blog post**](http://sirlagz.net/2013/02/06/script-web-configuration-page-for-raspberry-pi/) by SirLagz about using a web page rather than ssh to configure wifi and hostapd settings on the Raspberry Pi. I mostly just prettified the UI by wrapping it in [**SB Admin 2**](https://github.com/BlackrockDigital/startbootstrap-sb-admin-2), a Bootstrap based admin theme. Since then, the project has evolved to include greater control over many aspects of a networked RPi, better security, authentication, support for themes and more. 

We'd be curious to hear about how you use this with your own Pi-powered access points. Ping us on Twitter ([**@billzimmerman**](https://twitter.com/billzimmerman), [**@jrmhaig**](https://twitter.com/jrmhaig) and [**@SirLagz**](https://twitter.com/SirLagz)). Until then, here are some screenshots:

![](https://i.imgur.com/0f27nen.png)
![](https://i.imgur.com/jFDMEy6.png)
![](https://i.imgur.com/ck0XS8P.png)
![](https://i.imgur.com/Vaej8Xv.png)
![](https://i.imgur.com/iNuMMip.png)
## Contents

 - [Prerequisites](#prerequisites)
 - [Quick installer](#quick-installer)
 - [Manual installation](#manual-installation)
 - [Optional reset button](#optional-reset-button)
 - [Optional services](#optional-services)
 - [How to contribute](#how-to-contribute)
 - [License](#license)

## Prerequisites
You need to install some extra software in order for the Raspberry Pi to act as a WiFi router and access point. If all you're interested in is configuring your RPi as a client on an existing WiFi network, you can skip this step. 

There are many guides available to help you select a WiFi adapter, install a compatible driver, configure HostAPD and so on. The details are outside the scope of this project, although I've had consistently good results with the [**Edimax Wireless 802.11b/g/n nano USB adapter**](http://www.edimax.com/edimax/merchandise/merchandise_detail/data/edimax/global/wireless_adapters_n150/ew-7811un) – it's small, cheap and easy to work with.

To configure your RPi as a WiFi router, either of these resources will start you on the right track: 
* [**How-To: Use The Raspberry Pi As A Wireless Access Point/Router Part 1**](http://sirlagz.net/2012/08/09/how-to-use-the-raspberry-pi-as-a-wireless-access-pointrouter-part-1/)
* [**How-To: Turn a Raspberry Pi into a WiFi router**](http://raspberrypihq.com/how-to-turn-a-raspberry-pi-into-a-wifi-router/) (uses isc-dhcp-server instead of dnsmasq)

After you complete the intial setup, you'll be able to administer these services using the web UI.

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

## Manual installation
These steps apply to the latest release of Raspbian (currently [Stretch](https://www.raspberrypi.org/downloads/raspbian/)). Notes for previously released versions are provided, where applicable. Start off by installing git, lighttpd, php7, hostapd and dnsmasq. 
```sh
$ sudo apt-get install git lighttpd php7.0-cgi hostapd dnsmasq
```
**Note:** for Raspbian Jessie and Wheezy, replace `php7.0-cgi` with `php5-cgi`. After that, enable PHP for lighttpd and restart it for the settings to take effect.
```sh
sudo lighttpd-enable-mod fastcgi-php
sudo service lighttpd restart
```
Now comes the fun part. For security reasons, the `www-data` user which lighttpd runs under is not allowed to start or stop daemons, or run commands like ifdown and ifup, all of which we want our page to do.
So what I have done is added the `www-data` user to the sudoers file, but with restrictions on what commands the user can run.
Add the following to the end of  `/etc/sudoers`: 

```sh
www-data ALL=(ALL) NOPASSWD:/sbin/ifdown wlan0
www-data ALL=(ALL) NOPASSWD:/sbin/ifup wlan0
www-data ALL=(ALL) NOPASSWD:/bin/cat /etc/wpa_supplicant/wpa_supplicant.conf
www-data ALL=(ALL) NOPASSWD:/bin/cp /tmp/wifidata /etc/wpa_supplicant/wpa_supplicant.conf
www-data ALL=(ALL) NOPASSWD:/sbin/wpa_cli scan_results
www-data ALL=(ALL) NOPASSWD:/sbin/wpa_cli scan
www-data ALL=(ALL) NOPASSWD:/sbin/wpa_cli reconfigure
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

## Optional reset button

#### About
The RaspAP software monitors a GPIO pin which can be used to activate several reset functions. When held for different lengths of time, this one pin can activate reboot, power down and factory reset functions.  You can connect a push button to this GPIO pin to make these functions available to the user. RaspAP also drives a GPIO output pin to indicate which function will be activated.  You can wire an LED to this pin so that you can clearly see which function will be activated.  The LED is not necessary to use the reset functions but it is nice to have.

This functionality is included in RaspAP.  All you need to do is add the button and the LED.

#### Reset functions

The following functions can all be accessed by using the reset button

- Reboot.  The Pi will power down and power back up again.
- Shutdown.  The Pi will shut down.  You need to cycle power to re-start the Pi.
- Factory reset. See [below](#factory-reset-function) for details about how this works.

#### Function selection

To activate the reset functions, hold the reset button down.  Different hold times select the reset function that will be performed.
If you have connected an LED, the LED flash timing shows which of the reset functions will be activated when the button is released.  

The reset function hold times and the LED flash codes are:

Reset function | Button held for | Recommended hold time | LED flash rate
-------------- | --------------- | --------------------- | ---------------
Device reboot | Between 1sec and 6 sec | 3 seconds | 1 per second (slow)
Device shutdown | Between 6 sec and 15 sec | 8 seconds | 2 per second (medium)
Factory reset | Longer than 15 seconds | 20 seconds | 5 per second (fast)

If the button has not been pressed, the LED will simply be ON.  This indicates that RaspAP is operating normally.

Video showing how to use the button to activate the reset modes
https://youtu.be/7N_r_Cffa58

#### Factory reset function

The factory reset function restores RaspAP settings to a pre-defined state.  Without changing any settings, the factory reset restores the default RaspAP settings, as would be used for a new installation.  

You can configure the factory reset function to use settings that you have saved.  The System -> Defaults menu has functions to set this up.  There is a control that will save the current RaspAP configuration.  You can also select whether the user saved settings or the RaspAP default settings will be used upon factory reset.

#### Adding the button and the LED

The button must be connected so that it shorts the pin GPIO 21 to ground.  There is a ground pin directly adjacent to GPIO21 that will be convenient for most applications. Any normally open button will work.  
The LED is connected from pin GPIO 20 to ground via a resistor. Using a resistor is **critical**.  Leaving it out will definitely fry the GPIO pin and if you're unlucky it will take out the whole processor too. 330 ohms is a safe value to choose for the resistor.

Fritzing diagram showing which Pi pins to use and which way around the LED needs to be.
![](https://i.imgur.com/m7pT25C.png)

Photo of a button and LED installed on a Raspberry Pi Zero W.
![](https://i.imgur.com/X95yb6S.png)

Reference GPIO pinouts
![](https://pinout.xyz/resources/raspberry-pi-pinout.png)
Image courtesy of [pinout.xyz](https://pinout.xyz/)


## Optional services
OpenVPN and TOR are two additional services that run perfectly well on the RPi, and are a nice way to extend the usefulness of your WiFi router. I've started on interfaces to administer these services. Not everyone will need them, so for the moment they are disabled by default. You can enable them by changing these options in `index.php`:

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

