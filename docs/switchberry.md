# Switchberry support
RaspAP provides first-class support for the [Switchberry](https://github.com/Time-Appliances-Project/Switchberry) timing carrier. It verifies the KSZ9567 through a bound kernel driver or its SPI chip ID before identifying the board or exposing any Switchberry UI, services, packages or sudo permissions. Software markers alone are deliberately insufficient, so a plain Compute Module 4 or Compute Module 5 continues to use the regular RaspAP interface and model identity.

When the hardware is present, RaspAP adds a dedicated **Switchberry** management page with:

* a live Dashboard timing summary for PTP role and plane, ClockMatrix channels, GNSS acquisition, PPS/PHC health and active references;
* live state for all five KSZ9567 front-panel Ethernet ports;
* a dedicated Renesas 8A34004 ClockMatrix console with live channel state, phase, loop dynamics, assigned references, combo-bus topology and guarded operating-state controls;
* a GNSS console with receiver health, fix quality, coordinates, DOP, PPS/data-path status, constellation-aware satellite sky view, signal table and safe u-blox acquisition/profile controls;
* PTP role, clock plane, transport, per-port state, PHC synchronization, NTP and GNSS status;
* hardware transparent-clock configuration for E2E/P2P, one/two-step behavior, Layer 2/IPv4/IPv6 recognition, domain filtering, message priority and per-port ingress/egress/asymmetry calibration;
* a five-port P2P one-step hardware-timestamped boundary clock with IEEE 1588 BMCA policy, per-port client/server policy, intervals, latency, asymmetry and unicast master tables;
* validated GM, client, SyncE, GNSS, CM4 PPS and Ethernet management configuration;
* visual, preset-driven routing for all four rear SMA connectors, including input type, priority, exact/custom frequency, realized Q9 frequency, DPLL channel state and last successful hardware apply;
* Switchberry systemd health, recent logs, device nodes, M.2/PCI and USB diagnostics; and
* controlled port enable/disable and ordered timing-stack restart operations.

![Live Switchberry timing summary on the RaspAP Dashboard](images/switchberry/dashboard-timing.jpg)

## Live interface

These screenshots were captured from a working Switchberry V6. The status badges reflect real hardware state: the GNSS receiver is online with a healthy powered antenna and PPS device, but is waiting for a clear satellite view, so the ClockMatrix correctly reports freerun rather than a false lock.

| PTP operating mode | ClockMatrix DPLL control |
| --- | --- |
| ![PTP grandmaster configuration](images/switchberry/ptp-clock.jpg) | ![Renesas ClockMatrix channel monitoring and control](images/switchberry/clockmatrix.jpg) |

| Rear-panel SMA routing | GNSS receiver and satellite sky view |
| --- | --- |
| ![Switchberry SMA signal routing presets](images/switchberry/sma-routing.jpg) | ![GNSS receiver monitoring and satellite sky view](images/switchberry/gnss-sky-view.jpg) |

Privileged hardware access is isolated in `/usr/local/sbin/raspap-switchberryctl`. The helper accepts only fixed, validated actions; configuration is limited to 64 KiB, normalized before use, backed up under `/var/lib/raspap/switchberry-backups`, atomically written to `/etc/startup-dpll.json`, and applied through the existing Switchberry service chain.

## Installation on Switchberry

> The standard RaspAP installer enables timing support only after it verifies the board's KSZ9567. Use the dedicated installer below when the existing management network must remain untouched.

Start from a Switchberry image containing the official timing utilities and the installed `*-DSA-SwitchberryV6+` kernel, then install RaspAP from a local checkout:

```sh
git clone https://github.com/RaspAP/raspap-webgui.git
cd raspap-webgui
sudo ./installers/switchberry.sh
```

The installer verifies both the KSZ9567 hardware identity and the Switchberry software markers, installs the RaspAP UI and audited root controller, selects the protected Switchberry kernel image, builds the V6 boundary-clock overlay, installs the PTP service orchestration and configures lighttpd/PHP-FPM. It intentionally leaves NetworkManager, the existing management link, hotspot, DHCP and DNS services unchanged.

A compatible Wi-Fi interface—either onboard the CM4 or installed in the M.2 slot—and its Linux driver are required for RaspAP access-point features. On a unit managed only through `wlan0`, verify the Switchberry page before changing hotspot settings; enabling an AP on the sole Wi-Fi interface will disconnect its current client connection.

## ClockMatrix control

The **ClockMatrix** tab presents channel 5 as the frequency DPLL and channel 6 as the time/1PPS DPLL. It reads the active state, phase offset, loop bandwidth, phase-slope limit, damping factor and combo-bus relationship directly from the Renesas 8A34004, and shows which enabled GNSS, SyncE, CM4 or SMA reference is assigned to each channel.

Automatic tuning remains the default and lets the Switchberry timing utility choose source-appropriate loop dynamics and combo-bus direction. Advanced users can persist an override per channel for loop bandwidth (`uHz`, `mHz`, `Hz` or `kHz`), phase-slope limit, damping factor, and whether the channel is independent or follows the other DPLL. Mutual follow configurations are rejected. **Reacquire**, **Normal**, **Holdover** and **Freerun** are immediate, confirmed actions; they do not silently alter the saved tuning profile. Every successful timing apply records a configuration fingerprint so the page distinguishes applied hardware state from pending changes.

## GNSS receiver and sky view

The **GNSS** tab monitors gpsd even when the M.2 receiver is not selected as a ClockMatrix timing source. It reports receiver identity and firmware, serial link, PPS and bridge health, fix mode, receiver time validity, position and accuracy, dilution of precision, visible/used satellite counts, and per-satellite constellation, azimuth, elevation and carrier-to-noise signal strength. An azimuth/elevation sky plot uses distinct colors for GPS, Galileo, GLONASS, BeiDou, SBAS, QZSS and NavIC and highlights satellites used in the navigation solution. A receiver that is online but has no antenna view is deliberately shown separately from an offline receiver or a valid fix.

Reference routing, role and DPLL priority are configurable in the same tab. An optional managed u-blox profile exposes the timing-relevant navigation model, measurement interval, elevation mask and constellation selection; at least one primary constellation must remain enabled. The normalized profile is reapplied to receiver RAM during every timing-stack start, avoiding repeated flash writes. Guarded actions restart gpsd or request a hot, warm or cold acquisition start. Timepulse/PPS state is monitored, while the critical 1PPS waveform remains under the proven Switchberry timing path instead of exposing raw receiver registers in the web process.

## PTP clock planes

| Mode | Implementation | Reboot |
| --- | --- | --- |
| Transparent clock | KSZ9567 residence-time correction configured through direct SPI register access | Only when leaving boundary-clock mode |
| Boundary clock | Five DSA interfaces (`lan1`–`lan5`) sharing the KSZ9567 hardware PHC and one multi-port `ptp4l` instance | Required when entering or leaving this mode |
| Grandmaster / client | Existing Switchberry DPLL, PHC and `ptp4l` orchestration on the direct-switch plane | Only when leaving boundary-clock mode |
| Disabled | PTP processing off; timing routing and diagnostics remain available | Only when leaving boundary-clock mode |

The installer compiles and installs a V6-specific `switchberrybc-v6` device-tree overlay. It also selects the installed `*-DSA-SwitchberryV6+` PTP-enabled kernel through a dedicated `kernel8-switchberry.img` filename, so a later Raspberry Pi kernel package cannot silently replace the boundary-clock kernel. The controller updates only the managed overlay lines in `/boot/firmware/config.txt`, saves timestamped boot and timing backups, and presents an explicit reboot action in the UI. The V6 overlay retains the TCA6424 (whose Linux I2C bus number is detected dynamically), DPLL bit-banged SPI at `/dev/spidev7.0`, and all timing paths while binding the KSZ9567 to the kernel DSA/PTP driver. The ordinary Switchberry network, switch initialization, PHY fixup, and DHCP watchdog services are automatically skipped in the DSA plane so they cannot contend with the kernel switch driver. When returning to the direct-switch plane, the PHY fixup verifies that `eth0` has an attached MDIO PHY (rather than relying on one historical kernel-log string) and can perform up to four safe-mux recovery reboots.

Boundary-clock ports use linuxptp's normal BMCA by default. `UPSTREAM` forces a client-only port, `DOWNSTREAM` forces a server-only port, and `DISABLED` keeps that front interface down. The upstream Linux KSZ9567 driver exposes hardware transmission as P2P one-step only, so the controller normalizes this mode to `time_stamping p2p1step`; E2E, two-step, G.8275.1 and G.8275.2 are explicitly unavailable rather than mislabeled as hardware operation. The transparent-clock engine still supports E2E/P2P and one/two-step operation directly in the switch. Boundary-clock mode reuses the board utility's client-safe CM4/DPLL routing while keeping `BC` as the authoritative role, while transparent-clock mode uses its neutral routing. The system-clock discipline option is off by default and, when enabled, uses slew-only `phc2sys` operation so activating it cannot step the CM4 clock.

## SMA timing I/O

The ECAD uses hardware SMA names in the opposite order from the rear-panel labels: hardware `SMA4..SMA1` correspond to rear-panel `SMA1..SMA4`. The GUI presents connector cards in physical rear-panel order, retains the ECAD label for troubleshooting, and offers one-click presets for 1 PPS, 10 MHz, 25 MHz and custom signals. Advanced input controls expose time/frequency role and reference priority without requiring the user to know DPLL input numbers.

| Rear connector | Input path | Output path | Hardware constraint |
| --- | --- | --- | --- |
| SMA1 | DPLL IN4 / CLK1N | — | Dedicated input-only connector |
| SMA2 | DPLL IN3 / CLK1P | DPLL Q9 / channel 5 | Input shares the GNSS PPS mux; Q9 uses an integer divider and is not a phase-aligned 1 PPS source |
| SMA3 | DPLL IN2 / CLK0N | DPLL Q10 | Input shares the CM4 PPS mux |
| SMA4 | DPLL IN1 / CLK0P | DPLL Q11 / channel 6 | Input shares the SyncE mux; output shares the CM4 PPS path and is unavailable in grandmaster mode |

The controller rejects mux-contention combinations rather than letting the legacy board utility silently prefer one source. After a successful DPLL/mux apply, it records a fingerprint of every routing-affecting setting. The SMA page reports **Applied** only when that fingerprint matches the saved configuration; otherwise it remains **Pending apply**. For Q9 outputs the displayed realized frequency and error are calculated from the actual 500 MHz integer-divider model used by the board utility. Q10 and Q11 use the DPLL fractional output divider.

After a second supported Wi-Fi interface is fitted and verified, the standard RaspAP networking packages can be installed and assigned to that interface without taking over the management link.
