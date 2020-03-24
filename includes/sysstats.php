<?php

require_once 'app/lib/system.php';

$system = new System();

$hostname = $system->hostname();
$uptime   = $system->uptime();
$cores    = $system->processorCount();

// mem used
$memused  = $system->usedMemory();
$memused_status = "primary";
if ($memused > 90) {
    $memused_status = "danger";
    $memused_led = "service-status-down";
} elseif ($memused > 75) {
    $memused_status = "warning";
    $memused_led = "service-status-warn";
} elseif ($memused >  0) {
    $memused_status = "success";
    $memused_led = "service-status-up";
}

// cpu load
$cpuload = $system->systemLoadPercentage();
if ($cpuload > 90) {
    $cpuload_status = "danger";
} elseif ($cpuload > 75) {
    $cpuload_status = "warning";
} elseif ($cpuload >=  0) {
    $cpuload_status = "success";
}

// cpu temp
$cputemp = $system->systemTemperature();
if ($cputemp > 70) {
    $cputemp_status = "danger";
    $cputemp_led = "service-status-down";
} elseif ($cputemp > 50) {
    $cputemp_status = "warning";
    $cputemp_led = "service-status-warn";
} else {
    $cputemp_status = "success";
    $cputemp_led = "service-status-up";
}

// hostapd status
$hostapd = $system->hostapdStatus();
if ($hostapd[0] ==1) {
    $hostapd_status = "active";
    $hostapd_led = "service-status-up";
} else {
    $hostapd_status = "inactive";
    $hostapd_led = "service-status-down";
}

