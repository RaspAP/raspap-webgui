<?php

require '../../includes/csrf.php';
require_once '../../includes/config.php';
require_once '../../src/RaspAP/Auth/HTTPAuth.php';
require_once '../../includes/authenticate.php';

if (isset($_POST['blocklist_id'])) {
    $blocklist_id = escapeshellcmd($_POST['blocklist_id']);

    switch ($blocklist_id) {
    case "StevenBlack/hosts \(default\)":
        $list_url = "https://raw.githubusercontent.com/StevenBlack/hosts/master/hosts";
        $dest_file = "hostnames.txt";
        break;
    case "badmojr/1Hosts \(Mini\)":
        $list_url = "https://badmojr.github.io/1Hosts/mini/hosts.txt";
        $dest_file = "hostnames.txt";
        break;
    case "badmojr/1Hosts \(Lite\)":
        $list_url = "https://badmojr.github.io/1Hosts/Lite/hosts.txt";
        $dest_file = "hostnames.txt";
        break;
    case "badmojr/1Hosts \(Pro\)":
        $list_url = "https://badmojr.github.io/1Hosts/Pro/hosts.txt";
        $dest_file = "hostnames.txt";
        break;
    case "badmojr/1Hosts \(Xtra\)":
        $list_url = "https://badmojr.github.io/1Hosts/Xtra/hosts.txt";
        $dest_file = "hostnames.txt";
        break;
    case "oisd/big \(default\)":
        $list_url = "https://big.oisd.nl/dnsmasq";
        $dest_file = "domains.txt";
        break;
    case "oisd/small":
        $list_url = "https://small.oisd.nl/dnsmasq";
        $dest_file = "domains.txt";
        break;
    case "oisd/nsfw":
        $list_url = "https://nsfw.oisd.nl/dnsmasq";
        $dest_file = "domains.txt";
        break;
    }
    $blocklist = $list_url . $dest_file;
    $dest = substr($dest_file, 0, strrpos($dest_file, "."));

    exec("sudo /etc/raspap/adblock/update_blocklist.sh $list_url $dest_file " .RASPI_ADBLOCK_LISTPATH, $return);
    $jsonData = ['return'=>$return,'list'=>$dest];
    echo json_encode($jsonData);
} else {
    $jsonData = ['return'=>2,'output'=>['Error getting data']];
    echo json_encode($jsonData);
}
