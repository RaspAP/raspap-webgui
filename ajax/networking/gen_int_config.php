<?php

require '../../includes/csrf.php';

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

if (isset($_POST['generate'])) {
    $cnfNetworking = array_diff(scandir(RASPI_CONFIG_NETWORKING, 1), array('..','.','dhcpcd.conf','defaults'));
    $cnfNetworking = array_combine($cnfNetworking, $cnfNetworking);
    $strConfFile = file_get_contents(RASPI_CONFIG_NETWORKING.'/defaults')."\n";
    foreach ($cnfNetworking as $index => $file) {
        if ($index != "defaults") {
            $cnfFile = parse_ini_file(RASPI_CONFIG_NETWORKING.'/'.$file, false, INI_SCANNER_RAW);
            if ($cnfFile['static'] === 'true') {
                $strConfFile .= "#Static IP configured for ".$cnfFile['interface']."\n";
                $strConfFile .= "interface ".$cnfFile['interface']."\n";
                if (isset($cnfFile['metric'])) {
                    $strConfFile .= "metric ".$cnfFile['metric']."\n";
                }
                $strConfFile .= "static ip_address=".$cnfFile['ip_address']."\n";
                $strConfFile .= "static routers=".$cnfFile['routers']."\n";
                $strConfFile .= "static domain_name_servers=".$cnfFile['domain_name_server']."\n\n";
            } elseif ($cnfFile['static'] === 'false' && $cnfFile['failover'] === 'true') {
                $strConfFile .= "#Failover static IP configured for ".$cnfFile['interface']."\n";
                $strConfFile .= "profile static_".$cnfFile['interface']."\n";
                $strConfFile .= "static ip_address=".$cnfFile['ip_address']."\n";
                $strConfFile .= "static routers=".$cnfFile['routers']."\n";
                $strConfFile .= "static domain_name_servers=".$cnfFile['domain_name_server']."\n\n";
                $strConfFile .= "interface ".$cnfFile['interface']."\n";
                if (isset($cnfFile['metric'])) {
                    $strConfFile .= "metric ".$cnfFile['metric']."\n";
                }
                $strConfFile .= "fallback static_".$cnfFile['interface']."\n\n";
            } else {
                $strConfFile .= "#DHCP configured for ".pathinfo($file, PATHINFO_FILENAME)."\n\n";
            }
        }
    }

    if (file_put_contents(RASPI_CONFIG_NETWORKING.'/dhcpcd.conf', $strConfFile)) {
        exec('sudo /bin/cp '.RASPI_CONFIG_NETWORKING.'/dhcpcd.conf '.RASPI_DHCPCD_CONFIG);
        $output = ['return'=>0,'output'=>'Settings successfully applied'];
    } else {
        $output = ['return'=>2,'output'=>'Unable to write to apply settings'];
    }
    echo json_encode($output);
}
