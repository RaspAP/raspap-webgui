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

    switch ($flags) {
    case NL80211_BAND_24GHZ:
        $msg = _("The selected interface has support for the 2.4 GHz wireless band only.");
       break;
    case NL80211_BAND_5GHZ:
        $msg = _("The selected interface has support for the 2.4 GHz wireless band only.");
        break;
    case NL80211_BAND_24GHZ | NL80211_BAND_5GHZ:
        $msg = _("The selected interface has support for both the 2.4 and 5 GHz wireless bands.");
        break;
    default:
        $msg = _("The selected interface does not support wireless mode operation.");
    }
    echo json_encode($msg);
}

