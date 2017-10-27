<?php
session_start();
include_once('../../includes/config.php');
include_once('../../includes/functions.php');


if(isset($_POST['interface']) && isset($_POST['csrf_token']) && CSRFValidate()) {
    $int = $_POST['interface'];
    if(!file_exists(RASPI_CONFIG_NETWORKING.'/DHCP-'.$int)) {
        touch(RASPI_CONFIG_NETWORKING.'/DHCP-'.$int.'.ini');
    }

    if(!file_exists(RASPI_CONFIG_NETWORKING.'/STATIC-'.$int)) {
        touch(RASPI_CONFIG_NETWORKING.'/STATIC-'.$int.'.ini');
    }

    $intDHCPConfig = parse_ini_file(RASPI_CONFIG_NETWORKING.'/DHCP-'.$int.'.ini');
    $intStaticConfig = parse_ini_file(RASPI_CONFIG_NETWORKING.'/STATIC-'.$int.'.ini');
    $jsonData = ['return'=>1,'output'=>['DHCPConfig'=>$intDHCPConfig,'StaticConfig'=>$intStaticConfig]];
    echo json_encode($jsonData);

} else {
    $jsonData = ['return'=>2,'output'=>['Error getting data']];
    echo json_encode($jsonData);
}

?>
