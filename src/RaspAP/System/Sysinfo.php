<?php

/**
 * System info class
 *
 * @description System info class for RaspAP
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

namespace RaspAP\System;

class Sysinfo
{
    public function hostname()
    {
        return shell_exec("hostname -f");
    }

    public function uptime()
    {
        $uparray = explode(" ", exec("cat /proc/uptime"));
        $seconds = round($uparray[0], 0);
        $minutes = $seconds / 60;
        $hours   = $minutes / 60;
        $days    = floor($hours / 24);
        $hours   = floor($hours   - ($days * 24));
        $minutes = floor($minutes - ($days * 24 * 60) - ($hours * 60));
        $uptime= '';
        if ($days    != 0) {
            $uptime .= $days . ' day' . (($days    > 1)? 's ':' ');
        }
        if ($hours   != 0) {
            $uptime .= $hours . ' hour' . (($hours   > 1)? 's ':' ');
        }
        if ($minutes != 0) {
            $uptime .= $minutes . ' minute' . (($minutes > 1)? 's ':' ');
        }

        return $uptime;
    }

    public function systime()
    {
        $systime = exec("date");
        return $systime;
    }

    public function usedMemory(): int
    {
        $used = shell_exec("free -m | awk 'NR==2{ total=$2 ; used=$3 } END { print used/total*100}'");
        return floor(intval($used));
    }

    public function usedDisk(): int
    {
        $output = shell_exec("df -h / | awk 'NR==2 {print $5}'");
        return intval(str_replace('%', '', trim($output)));
    }

    public function processorCount()
    {
        $procs = shell_exec("nproc --all");
        return intval($procs);
    }

    public function loadAvg1Min()
    {
        $load = exec("awk '{print $1}' /proc/loadavg");
        return floatval($load);
    }

    public function systemLoadPercentage()
    {
        return intval(($this->loadAvg1Min() * 100) / $this->processorCount());
    }

    public function systemTemperature()
    {
        $cpuTemp = file_get_contents("/sys/class/thermal/thermal_zone0/temp");
        return number_format((float)$cpuTemp/1000, 1);
    }

    public function hostapdStatus()
    {
        exec('pidof hostapd | wc -l', $status);
        return $status;
    }

    public function operatingSystem()
    {
        $os_desc = shell_exec("cat /etc/os-release | awk -F= '/^PRETTY_NAME/ {print $2}' | sed 's/\"//g'");
        return $os_desc;
    }

    public function kernelVersion()
    {
        $kernel = shell_exec("uname -r");
        return $kernel;
    }

    /*
     * Returns RPi Model and PCB Revision from Pi Revision Code (cpuinfo)
     * @see https://github.com/raspberrypi/documentation/blob/develop/documentation/asciidoc/computers/raspberry-pi/revision-codes.adoc
     */
    public function rpiRevision()
    {
        $revisions = array(
        '0002' => 'Raspberry Pi Model B Rev 1.0',
        '0003' => 'Raspberry Pi Model B Rev 1.0',
        '0004' => 'Raspberry Pi Model B Rev 2.0',
        '0005' => 'Raspberry Pi Model B Rev 2.0',
        '0006' => 'Raspberry Pi Model B Rev 2.0',
        '0007' => 'Raspberry Pi Model A',
        '0008' => 'Raspberry Pi Model A',
        '0009' => 'Raspberry Pi Model A',
        '000d' => 'Raspberry Pi Model B Rev 2.0',
        '000e' => 'Raspberry Pi Model B Rev 2.0',
        '000f' => 'Raspberry Pi Model B Rev 2.0',
        '0010' => 'Raspberry Pi Model B+',
        '0013' => 'Raspberry Pi Model B+',
        '0011' => 'Compute Module 1',
        '0012' => 'Raspberry Pi Model A+',
        'a01041' => 'Raspberry Pi 2 Model B',
        'a21041' => 'Raspberry Pi 2 Model B',
        '900092' => 'Raspberry Pi Zero 1.2',
        '900093' => 'Raspberry Pi Zero 1.3',
        '9000c1' => 'Raspberry Pi Zero W',
        'a02082' => 'Raspberry Pi 3 Model B',
        'a22082' => 'Raspberry Pi 3 Model B',
        'a32082' => 'Raspberry Pi 3 Model B',
        'a52082' => 'Raspberry Pi 3 Model B+',
        '9020e0' => 'Raspberry Pi 3 Model A+',
        'a02100' => 'Compute Module 3+',
        'a03111' => 'Raspberry Pi 4 Model B (1 GB)',
        'b03111' => 'Raspberry Pi 4 Model B (2 GB)',
        'c03111' => 'Raspberry Pi 4 Model B (4 GB)',
        'b03112' => 'Raspberry Pi 4 Model B (2 GB)',
        'c03112' => 'Raspberry Pi 4 Model B (4 GB)',
        'd03114' => 'Raspberry Pi 4 Model B (8 GB)',
        '902120' => 'Raspberry Pi Zero 2 W',
        'a03140' => 'Compute Module 4 (1 GB)',
        'b03140' => 'Compute Module 4 (2 GB)',
        'c03140' => 'Compute Module 4 (4 GB)',
        'd03140' => 'Compute Module 4 (8 GB)',
        'c04170' => 'Raspberry Pi 5 (4 GB)',
        'd04170' => 'Raspberry Pi 5 (8 GB)'
        );

        $cpuinfo_array = '';
        exec('cat /proc/cpuinfo', $cpuinfo_array);
        $info = preg_grep("/^Revision/", $cpuinfo_array);
        $tmp = explode(':', array_pop($info));
        $rev = trim(array_pop($tmp));
        if (array_key_exists($rev, $revisions)) {
            return $revisions[$rev];
        } else {
            exec('cat /proc/device-tree/model', $model);
            if (isset($model[0])) {
                return $model[0];
            } else {
                return 'Unknown Device';
            }
        }
    }

    /**
     * Determines if ad blocking is enabled and active
     *
     * @return bool $status
     */
    public function adBlockStatus(): bool
    {
        exec('cat '. RASPI_ADBLOCK_CONFIG, $return);
        $arrConf = ParseConfig($return);
        $enabled = false;
        if (sizeof($arrConf) > 0) {
            $enabled = true;
        }
        exec('pidof dnsmasq | wc -l', $dnsmasq);
        $dnsmasq_state = ($dnsmasq[0] > 0);

        $status = $dnsmasq_state && $enabled;
        return $status;
    }

    /**
     * Determines if a VPN interface is active
     *
     * @return string $interface
     */
    public function getActiveVpnInterface(): ?string
    {
        $output = shell_exec('ip a 2>/dev/null');
        if (!$output) {
            return null;
        }
        $vpnInterfaces = ['wg0', 'tun0', 'tailscale0'];

        // interface must have an 'UP' status and an IP address
        foreach ($vpnInterfaces as $interface) {
            if (strpos($output, "$interface:") !== false) {
                if (preg_match("/\d+: $interface: .*<.*UP.*>/", $output) &&
                    preg_match("/inet\b.*$interface/", $output)) {
                        return $interface;
                }
            }
        }
        return null;
    }
}

