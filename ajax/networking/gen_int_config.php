<?php

require('../../includes/csrf.php');

include_once('../../includes/config.php');
include_once('../../includes/functions.php');

if (isset($_POST['generate'])) {
    $cnfNetworking = array_diff(scandir(RASPI_CONFIG_NETWORKING, 1), array('..','.','dhcpcd.conf'));
    $cnfNetworking = array_combine($cnfNetworking, $cnfNetworking);
    $strConfFile = "";
    foreach ($cnfNetworking as $index => $file) {
        if ($index != "defaults") {
            $cnfFile = parse_ini_file(RASPI_CONFIG_NETWORKING.'/'.$file, false, INI_SCANNER_RAW);
            if ($cnfFile['static'] === 'true') {
                $strConfFile .= "interface ".$cnfFile['interface']."\n";
                $strConfFile .= "static ip_address=".$cnfFile['ip_address']."\n";
                $strConfFile .= "static routers=".$cnfFile['routers']."\n";
                $strConfFile .= "static domain_name_servers=".$cnfFile['domain_name_server']."\n";
            } elseif ($cnfFile['static'] === 'false' && $cnfFile['failover'] === 'true') {
                $strConfFile .= "profile static_".$cnfFile['interface']."\n";
                $strConfFile .= "static ip_address=".$cnfFile['ip_address']."\n";
                $strConfFile .= "static routers=".$cnfFile['routers']."\n";
                $strConfFile .= "static domain_name_servers=".$cnfFile['domain_name_server']."\n\n";
                $strConfFile .= "interface ".$cnfFile['interface']."\n";
                $strConfFile .= "fallback static_".$cnfFile['interface']."\n\n";
            } else {
                $strConfFile .= "#DHCP configured for ".$cnfFile['interface']."\n\n";
            }
        } else {
            $strConfFile .= file_get_contents(RASPI_CONFIG_NETWORKING.'/'.$index)."\n\n";
        }
    }

    if (file_put_contents(RASPI_CONFIG_NETWORKING.'/dhcpcd.conf', $strConfFile)) {
        exec('sudo /bin/cp /etc/raspap/networking/dhcpcd.conf /etc/dhcpcd.conf');
        $output = ['return'=>0,'output'=>'Settings successfully applied'];
    } else {
        $output = ['return'=>2,'output'=>'Unable to write to apply settings'];
    }
    echo json_encode($output);
}
