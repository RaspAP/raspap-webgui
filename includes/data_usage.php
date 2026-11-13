<?php

/**
 * Generate html for displaying data usage.
 */
function DisplayDataUsage(&$extraFooterScripts)
{
    // add the interfaces that RaspAP configures to vnstat
    exec('sudo /usr/local/sbin/raspap-vnstat.sh sync');
    exec("ip -o link show | awk -F ': ' '{print $2}' | grep -v lo ", $interfacesWlo);
    // the interfaces with statistics first, also when they do not exist now
    // (for example a VPN tunnel that is down)
    exec('vnstat --dbiflist 1 2>/dev/null', $interfacesDb);
    $interfaces = array_values(array_unique(array_merge($interfacesDb, $interfacesWlo)));
    echo renderTemplate("data_usage", [ "interfaces" => $interfaces ]);

    $extraFooterScripts[] = array('src'=>'dist/datatables/jquery.dataTables.min.js', 'defer'=>false);
    $extraFooterScripts[] = array('src'=>'app/js/vendor/bandwidthcharts.js?v=' . filemtime('app/js/vendor/bandwidthcharts.js'), 'defer'=>false);
}
