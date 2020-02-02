<?php

include_once('app/lib/system.php');

$system = new System();

$hostname = $system->hostname();
$uptime   = $system->uptime();
$cores    = $system->processorCount();

// mem used
$memused  = $system->usedMemory();
$memused_status = "primary";
if ($memused > 90) {
	$memused_status = "danger";
} elseif ($memused > 75) {
	$memused_status = "warning";
} elseif ($memused >  0) {
	$memused_status = "success";
}

// cpu load
$cpuload = $system->systemLoadPercentage();
if ($cpuload > 90) {
	$cpuload_status = "danger";
} elseif ($cpuload > 75) {
	$cpuload_status = "warning";
} elseif ($cpuload >  0) {
	$cpuload_status = "success";
}

// cpu temp
$cputemp = $system->systemTemperature();
if ($cputemp > 70) {
	$cputemp_status = "danger";
} elseif ($cputemp > 50) {
	$cputemp_status = "warning";
} else {
	$cputemp_status = "success";
}

// hostapd status
$hostapd = $system->hostapdStatus();
if ($hostapd) {
	$hostapd_status = "success";
} else {
	$hostapd_status = "danger";
}

