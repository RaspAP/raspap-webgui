<?php

/**
 * Generate html for displaying data usage.
 */
function DisplayDataUsage(&$extraFooterScripts)
{
    //Original - If you've more than 2 interfaces this show all interfaces (activated and deactivated)
    //exec("ip -o link show | awk -F ': ' '{print $2}' | grep -v lo ", $interfacesWlo);
    //Mod - This show only activated interfaces
    exec("ifconfig | cut -d ' ' -f1 | awk 'NF==1{print $1}'| sed 's/.$//' | grep -v lo", $interfacesWlo);
    echo renderTemplate("data_usage", [ "interfaces" => $interfacesWlo ]);

    $extraFooterScripts[] = array('src'=>'dist/datatables/jquery.dataTables.min.js', 'defer'=>false);
    $extraFooterScripts[] = array('src'=>'app/js/bandwidthcharts.js', 'defer'=>false);
}
