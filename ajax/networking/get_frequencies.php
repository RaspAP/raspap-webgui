<?php

require '../../includes/csrf.php';
require_once '../../includes/config.php';

if (isset($_POST['interface'])) {

    define( 'NL80211_BAND_24GHZ', 0x1 );
    define( 'NL80211_BAND_5GHZ', 0x2 );
    $iface = escapeshellcmd($_POST['interface']);

    // get physical device for selected interface
    exec("iw dev | awk '/$iface/ {print line}{line = $0}'", $return);
    $phy = $return[0];

    // get frequencies supported by device
    exec('iw '.$phy.' info | sed -rn "s/^.*\*\s([0-9]{4})\sMHz.*/\1/p"', $frequencies);
    
    if (count(preg_grep('/^24[0-9]{2}/i', $frequencies)) >0) {
        $flags += NL80211_BAND_24GHZ;
    }
    if (count(preg_grep('/^5[0-9]{3}/i', $frequencies)) >0) {
        $flags += NL80211_BAND_5GHZ;
    }
    echo json_encode($flags);
}

