<?php
session_start();
include_once('../../includes/config.php');
include_once('../../includes/functions.php');


if(isset($_POST['interface']) && isset($_POST['csrf_token']) && CSRFValidate()) {
    $int = $_POST['interface'];
    if(!file_exists(RASPI_CONFIG_NETWORKING.'/'.$int.'.ini')) {
        touch(RASPI_CONFIG_NETWORKING.'/'.$int.'.ini');
    }

    $intConfig = parse_ini_file(RASPI_CONFIG_NETWORKING.'/'.$int.'.ini');
    $jsonData = ['return'=>1,'output'=>['intConfig'=>$intConfig]];
    echo json_encode($jsonData);

    // Todo - get dhcp lease information from `dhcpcd -U eth0` ? maybe ?

} else {
    $jsonData = ['return'=>2,'output'=>['Error getting data']];
    echo json_encode($jsonData);
}

?>
