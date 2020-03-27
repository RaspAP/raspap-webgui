<?php

require '../../includes/csrf.php';
require_once '../../includes/config.php';

if (isset($_POST['blocklist_id'])) {
    $blocklist_id = $_POST['blocklist_id'];
    $notracking_url = "https://raw.githubusercontent.com/notracking/hosts-blocklists/master/";

    switch ($blocklist_id) {
        case "notracking-hostnames":
            $file = "hostnames.txt";
            break;
        case "notracking-domains":
            $file = "domains.txt";
            break;
    }
    $blocklist = $notracking_url . $file;

    exec("sudo /etc/raspap/adblock/update_blocklist.sh $blocklist $file " .RASPI_ADBLOCK_LISTPATH, $return);
    $jsonData = ['return'=>$return];
    echo json_encode($jsonData);
}

