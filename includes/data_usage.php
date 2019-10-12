<?php

/**
 * Generate html for displaying data usage.
 */
function DisplayDataUsage(&$extraFooterScripts)
{
    exec("ip -o link show | awk -F ': ' '{print $2}' | grep -v lo ", $interfacesWlo);
    echo renderTemplate("data_usage", [ "interfaces" => $interfacesWlo ]);

    $extraFooterScripts[] = array('src'=>'dist/datatables/jquery.dataTables.min.js', 'defer'=>false);
    $extraFooterScripts[] = array('src'=>'app/js/bandwidthcharts.js', 'defer'=>false);
}
