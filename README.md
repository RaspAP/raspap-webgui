![](https://i.imgur.com/xeKD93p.png)
[![Release 2.6](https://img.shields.io/badge/release-v2.6-green)](https://github.com/billz/raspap-webgui/releases) [![Awesome](https://awesome.re/badge.svg)](https://github.com/thibmaek/awesome-raspberry-pi) [![Financial Contributors on Open Collective](https://opencollective.com/raspap/all/badge.svg?label=financial+contributors)](https://opencollective.com/raspap) ![https://travis-ci.com/billz/raspap-webgui/](https://img.shields.io/travis/com/billz/raspap-webgui/master) [![Crowdin](https://badges.crowdin.net/raspap/localized.svg)](https://crowdin.com/project/raspap) [![Twitter URL](https://img.shields.io/twitter/url?label=%40RaspAP&logoColor=%23d8224c&url=https%3A%2F%2Ftwitter.com%2Frasp_ap)](https://twitter.com/rasp_ap) [![Subreddit subscribers](https://img.shields.io/reddit/subreddit-subscribers/RaspAP?style=social)](https://www.reddit.com/r/RaspAP/)

RaspAP lets you quickly get a WiFi access point up and running to share the connectivity of many popular [Debian-based devices](#supported-operating-systems), including the Raspberry Pi. Our popular [Quick installer](#quick-installer) creates a known-good default configuration that "just works" on all current Raspberry Pis with onboard wireless. A responsive interface gives you control over the relevant services and networking options. Advanced DHCP settings, OpenVPN client support, SSL, security audits, themes and multilingual options are included.

RaspAP has been featured on sites such as [Instructables](http://www.instructables.com/id/Raspberry-Pi-As-Completely-Wireless-Router/), [Adafruit](https://blog.adafruit.com/2016/06/24/raspap-wifi-configuration-portal-piday-raspberrypi-raspberry_pi/), [Raspberry Pi Weekly](https://www.raspberrypi.org/weekly/commander/) and [Awesome Raspberry Pi](https://project-awesome.org/thibmaek/awesome-raspberry-pi) and implemented in countless projects.

We hope you enjoy using RaspAP as much as we do creating it. Tell us how you use this with [your own projects](https://github.com/billz/raspap-awesome).

![](https://i.imgur.com/ikWvsMG.gif)
![](https://i.imgur.com/EiIpdOS.gif)
![](https://i.imgur.com/eCjUS1H.gif)
![](https://i.imgur.com/5FT2BcS.gif)
![](https://i.imgur.com/RKaBFrZ.gif)
## Contents

 - [Prerequisites](#prerequisites)
 - [Quick installer](#quick-installer)
 - [Ad Blocking](#ad-blocking)
 - [Bridged AP](#bridged-ap)
 - [Simultaneous AP and Wifi client](#simultaneous-ap-and-wifi-client)
 - [Support us](#support-us)
 - [Manual installation](#manual-installation)
 - [802.11ac 5GHz support](#80211ac-5ghz-support)
 - [Supported operating systems](#supported-operating-systems)
 - [Multilingual support](#multilingual-support)
 - [HTTPS support](#https-support)
 - [OpenVPN support](#openvpn-support)
 - [How to contribute](#how-to-contribute)
 - [Reporting issues](#reporting-issues)
 - [License](#license)

## Prerequisites
Start with a clean install of the [latest release of Raspberry Pi OS (32-bit) Lite](https://www.raspberrypi.org/downloads/raspbian/). The Raspberry Pi OS desktop and 64-bit beta distros are unsupported.

1. Update Raspbian, including the kernel and firmware, followed by a reboot:
```
sudo apt-get update
sudo apt-get dist-upgrade
sudo reboot
```
2. Set the WiFi country in raspi-config's **Localisation Options**: `sudo raspi-config`

3. If you have a device without an onboard WiFi chipset, the [**Edimax Wireless 802.11b/g/n nano USB adapter**](https://www.edimax.com/edimax/merchandise/merchandise_detail/data/edimax/global/wireless_adapters_n150/ew-7811un) is an excellent option – it's small, cheap and has good driver support.

With the prerequisites done, you can proceed with either the Quick installer or Manual installation steps below.

## Quick installer
Install RaspAP from your device's shell prompt:
```sh
curl -sL https://install.raspap.com | bash
```
The [installer](https://docs.raspap.com/quick/) will complete the steps in the manual installation (below) for you.

After the reboot at the end of the installation the wireless network will be
configured as an access point as follows:
* IP address: 10.3.141.1
  * Username: admin
  * Password: secret
* DHCP range: 10.3.141.50 to 10.3.141.255
* SSID: `raspi-webgui`
* Password: ChangeMe

**Note:** As the name suggests, the Quick Installer is a great way to quickly setup a new AP. However, it does not automagically detect the unique configuration of your system. Best results are obtained by connecting to ethernet (`eth0`) or as a WiFi client, also known as managed mode, with `wlan0`. For the latter, refer to [this FAQ](https://docs.raspap.com/faq/#headless). Special instructions for the Pi Zero W are [available here](https://docs.raspap.com/ap-sta/).

Please [read this](https://docs.raspap.com/issues/) before reporting an issue.

## Ad Blocking
This feature uses DNS blacklisting to block requests for ads, trackers and other undesirable hosts. To enable ad blocking, simply respond to the prompt during the installation. As a beta release, we encourage testing and feedback from users of RaspAP.

Details are [provided here](https://docs.raspap.com/adblock/).

## Bridged AP
By default RaspAP configures a routed AP for your clients to connect to. A bridged AP configuration is also possible. Slide the **Bridged AP mode** toggle under the **Advanced** tab of **Configure hotspot**, then save and restart the hotspot.

![](https://i.imgur.com/J5VKSay.png)

**Note:** In bridged mode, all routing capabilities are handled by your upstream router. Because your router assigns IP addresses to your device's hotspot and its clients, you might not be able to reach the RaspAP web interface from the default `10.3.141.1` address. Instead use your RPi's hostname followed by `.local` to access the RaspAP web interface. With Raspbian default settings, this should look like `raspberrypi.local`. Alternate methods are [discussed here](https://www.raspberrypi.org/documentation/remote-access/ip-address.md).

More information on Bridged AP mode is provided [in our documentation](https://docs.raspap.com/bridged/).

## Simultaneous AP and Wifi client
RaspAP lets you create an AP with a Wifi client configuration, often called [AP-STA mode](https://docs.raspap.com/ap-sta/). With your system configured in managed mode, enable the AP from the **Advanced** tab of **Configure hotspot** by sliding the **Wifi client AP mode** toggle. Save settings and start the hotspot. The managed mode AP is functional without restart.

**Note:** This option is disabled until you configure your system as a wireless client. For a device operating in [managed mode](https://docs.raspap.com/faq/#headless) without an `eth0` connection, this configuration must be enabled [_before_ a reboot](https://docs.raspap.com/ap-sta/). 

## Support us
RaspAP is free software, but powered by your support. If you find RaspAP useful for your personal or commercial projects, please [become a GitHub sponsor](https://github.com/sponsors/billz), join the project on [Open Collective](https://opencollective.com/raspap) or make a one-time donation with [PayPal](https://www.paypal.com/paypalme2/billzgithub). Any of these options makes a big difference!

[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg?style=for-the-badge&logo=paypal&link=https://www.paypal.com/paypalme2/billzgithub)](https://www.paypal.com/paypalme2/billzgithub)

## Manual installation
Detailed manual setup instructions are provided [on our documentation site](https://docs.raspap.com/manual/).

## 802.11ac 5GHz support
RaspAP provides an 802.11ac wireless mode option for supported hardware (currently the RPi 3B+/4 and compatible Orange Pi models) and wireless regulatory domains. See [this FAQ](https://docs.raspap.com/faq/#80211ac) for more information. 

## Supported operating systems
RaspAP was originally made for Raspbian, but now also installs on the following Debian-based distros.

| Distribution | Release  | Architecture | Support |
|---|:---:|:---:|:---:|
| Raspberry Pi OS | (32-bit) Lite Buster | ARM | Official |
| Armbian | Buster | [ARM](https://docs.armbian.com/#supported-chips) | Official |
| Debian  |  Buster | ARM / x86_64  | Beta |
| Ubuntu  |  18.04 LTS / 19.10 | ARM / x86_64  | Beta |

![](https://i.imgur.com/luiyYNw.png)

We find Armbian particularly well-suited for this project. Please note that "supported" is not a guarantee. If you are able to improve support for your preferred distro, we encourage you to [actively contribute](#how-to-contribute) to the project.

## Multilingual support
RaspAP uses [GNU Gettext](https://www.gnu.org/software/gettext/) to manage multilingual messages. In order to use RaspAP with one of our supported translations, you must configure a corresponding language package on your RPi. To list languages currently installed on your system, use `locale -a` at the shell prompt. To generate new locales, run `sudo dpkg-reconfigure locales` and select any other desired locales. Details are provided on our [documentation site](https://docs.raspap.com/translations/) 

The following translations are currently maintained by the project:

- Čeština
- 正體中文 (Chinese traditional)
- 简体中文 (Chinese Simplified)
- Dansk
- Deutsch
- Español
- Finnish
- Français
- Ελληνικά (Greek)
- Indonesian
- Italiano
- 日本語 (Japanese)
- 한국어 (Korean)
- Nederlands
- Polskie
- Português
- Русский
- Svenska
- Türkçe
- Tiếng Việt (Vietnamese)

If your language is not in the list above, why not [contribute a translation](https://docs.raspap.com/translations/#contributing-to-a-translation)? Contributors will receive credit as the original translators.

## HTTPS support
The Quick Installer may be used to [generate SSL certificates](https://docs.raspap.com/ssl-quick/) with `mkcert`. The installer automates the manual steps [described here](https://docs.raspap.com/ssl-manual/), including configuring lighttpd with SSL support. 

Simply append the `-c` or `--cert` option to the Quick Installer, like so:

```sh
curl -sL https://install.raspap.com | bash -s -- --cert
```

**Note**: this only installs mkcert and generates an SSL certificate with the input you provide. It does *not* (re)install RaspAP.

More information on SSL certificates and HTTPS support is available [in our documentation](https://docs.raspap.com/ssl-quick/). 

## OpenVPN support
OpenVPN may be optionally installed by the Quick Installer. Once this is done, you can manage client configuration and the `openvpn-client` service with RaspAP.

To configure an OpenVPN client, upload a valid .ovpn file and, optionally, specify your login credentials. RaspAP will store your client configuration and add firewall rules to forward traffic from OpenVPN's `tun0` interface to your configured wireless interface. 

**Note**: this feature is currently in beta. Please [read this](https://docs.raspap.com/faq/#openvpn-fails) before reporting an issue.

## How to contribute
1. Fork the project in your account and create a new branch: `your-great-feature`.
2. Open an issue in the repository describing the feature contribution you'd like to make.
3. Commit changes in your feature branch.
4. Open a pull request and reference the initial issue in the pull request message.

Find out more about our [coding style guidelines and recommended tools](CONTRIBUTING.md). 

## Reporting issues
Please [read this](https://docs.raspap.com/issues/) before reporting a bug.

## Contributors

### Code Contributors
This project exists thanks to all the awesome people who [contribute](CONTRIBUTING.md) their time and expertise.

<a href="https://github.com/billz/raspap-webgui/graphs/contributors"><img src="https://opencollective.com/raspap/contributors.svg?width=890&button=false" /></a>

### Financial Contributors
Become a [financial contributor](https://opencollective.com/raspap/contribute) and help us sustain our community. 

#### Individuals
<a href="https://opencollective.com/raspap"><img src="https://opencollective.com/raspap/individuals.svg?width=890"></a>

#### Organizations

[Support this project](https://opencollective.com/raspap/contribute) with your organization. Your logo will show up here with a link to your website. 

<a href="https://opencollective.com/raspap/organization/0/website"><img src="https://opencollective.com/raspap/organization/0/avatar.svg"></a>
<a href="https://opencollective.com/raspap/organization/1/website"><img src="https://opencollective.com/raspap/organization/1/avatar.svg"></a>
<a href="https://opencollective.com/raspap/organization/2/website"><img src="https://opencollective.com/raspap/organization/2/avatar.svg"></a>
<a href="https://opencollective.com/raspap/organization/3/website"><img src="https://opencollective.com/raspap/organization/3/avatar.svg"></a>
<a href="https://opencollective.com/raspap/organization/4/website"><img src="https://opencollective.com/raspap/organization/4/avatar.svg"></a>
<a href="https://opencollective.com/raspap/organization/5/website"><img src="https://opencollective.com/raspap/organization/5/avatar.svg"></a>
<a href="https://opencollective.com/raspap/organization/6/website"><img src="https://opencollective.com/raspap/organization/6/avatar.svg"></a>
<a href="https://opencollective.com/raspap/organization/7/website"><img src="https://opencollective.com/raspap/organization/7/avatar.svg"></a>
<a href="https://opencollective.com/raspap/organization/8/website"><img src="https://opencollective.com/raspap/organization/8/avatar.svg"></a>
<a href="https://opencollective.com/raspap/organization/9/website"><img src="https://opencollective.com/raspap/organization/9/avatar.svg"></a>

## License
See the [LICENSE](./LICENSE) file.

