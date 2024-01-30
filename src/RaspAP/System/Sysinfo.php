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

    public function usedMemory()
    {
        $used = shell_exec("free -m | awk 'NR==2{ total=$2 ; used=$3 } END { print used/total*100}'");
        return floor(intval($used));
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
     * @see http://www.raspberrypi-spy.co.uk/2012/09/checking-your-raspberry-pi-board-version/
     */
    public function rpiRevision()
    {
        $revisions = array(
        '0002' => 'Model B Revision 1.0',
        '0003' => 'Model B Revision 1.0 + ECN0001',
        '0004' => 'Model B Revision 2.0 (256 MB)',
        '0005' => 'Model B Revision 2.0 (256 MB)',
        '0006' => 'Model B Revision 2.0 (256 MB)',
        '0007' => 'Model A',
        '0008' => 'Model A',
        '0009' => 'Model A',
        '000d' => 'Model B Revision 2.0 (512 MB)',
        '000e' => 'Model B Revision 2.0 (512 MB)',
        '000f' => 'Model B Revision 2.0 (512 MB)',
        '0010' => 'Model B+',
        '0013' => 'Model B+',
        '0011' => 'Compute Module',
        '0012' => 'Model A+',
        'a01041' => 'a01041',
        'a21041' => 'a21041',
        '900092' => 'PiZero 1.2',
        '900093' => 'PiZero 1.3',
        '9000c1' => 'PiZero W',
        'a02082' => 'Pi 3 Model B',
        'a22082' => 'Pi 3 Model B',
        'a32082' => 'Pi 3 Model B',
        'a52082' => 'Pi 3 Model B',
        'a020d3' => 'Pi 3 Model B+',
        'a220a0' => 'Compute Module 3',
        'a020a0' => 'Compute Module 3',
        'a02100' => 'Compute Module 3+',
        'a03111' => 'Model 4B Revision 1.1 (1 GB)',
        'b03111' => 'Model 4B Revision 1.1 (2 GB)',
        'c03111' => 'Model 4B Revision 1.1 (4 GB)',
        'a03140' => 'Compute Module 4 (1 GB)',
        'b03140' => 'Compute Module 4 (2 GB)',
        'c03140' => 'Compute Module 4 (4 GB)',
        'd03140' => 'Compute Module 4 (8 GB)',
        'c04170' => 'Pi 5 (4 GB)',
        'd04170' => 'Pi 5 (8 GB)'
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
}

