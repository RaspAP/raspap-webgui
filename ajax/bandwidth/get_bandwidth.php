<?php

require '../../includes/csrf.php';

require_once '../../includes/config.php';
require_once RASPI_CONFIG.'/raspap.php';

header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'none'; connect-src 'self'");
require_once '../../includes/authenticate.php';


$interface = filter_input(INPUT_GET, 'inet', FILTER_SANITIZE_SPECIAL_CHARS);
if (empty($interface)) {
    // Use first interface if inet parameter not provided.
    exec("ip -o link show | awk -F ': ' '{print $2}' | grep -v lo ", $interfacesWlo);
    if (count($interfacesWlo) > 0) {
        $interface = $interfacesWlo[0];
    } else {
        exit('No network interfaces found.');
    }
} 

define('IFNAMSIZ', 16);
if (strlen($interface) > IFNAMSIZ) {
    exit('Interface name too long.');
} elseif(!preg_match('/^[a-zA-Z0-9]+$/', $interface)) {
    exit('Invalid interface name.');
}

require_once './get_bandwidth_hourly.php';

exec(
    sprintf('vnstat -i %s --json ', escapeshellarg($interface)), $jsonstdoutvnstat,
    $exitcodedaily
);
if ($exitcodedaily !== 0) {
    exit('vnstat error');
}

$jsonobj = json_decode($jsonstdoutvnstat[0], true);
$timeunits = filter_input(INPUT_GET, 'tu');
if ($timeunits === 'm') {
    // months
    $jsonData = $jsonobj['interfaces'][0]['traffic']['month'];
} else {
    // default: days
    $jsonData = $jsonobj['interfaces'][0]['traffic']['day'];
}

$datasizeunits = filter_input(INPUT_GET, 'dsu');
$dsu_factor = $datasizeunits == "mb" ? 1024 * 1024 : 1024;
header('X-Content-Type-Options: nosniff');
header('Content-Type: application/json');
echo '[ ';
$firstelm = true;
for ($i = count($jsonData) - 1; $i >= 0; --$i) {
    if ($timeunits === 'm') {
        $dt = DateTime::createFromFormat(
            'Y n', $jsonData[$i]['date']['year'].' '.
            $jsonData[$i]['date']['month']
        );
    } else {
        $dt = DateTime::createFromFormat(
            'Y n j', $jsonData[$i]['date']['year'].' '.
                                                      $jsonData[$i]['date']['month'].' '.
            $jsonData[$i]['date']['day']
        );
    }

    if ($firstelm) {
        $firstelm = false;
    } else {
        echo ',';
    }

    $datasend = round($jsonData[$i]['tx'] / $dsu_factor, 0);
    $datareceived = round($jsonData[$i]['rx'] / $dsu_factor, 0);

    if ($timeunits === 'm') {
        echo '{ "date": "' , $dt->format('Y-m') , '", "rx": "' , $datareceived , 
        '", "tx": "' , $datasend , '" }';
    } else {
        echo '{ "date": "' , $dt->format('Y-m-d') , '", "rx": "' , $datareceived , 
        '", "tx": "' , $datasend , '" }';
    }
}

echo ' ]';


