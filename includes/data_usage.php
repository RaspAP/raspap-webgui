<?php

/**
 * Generate html for displaying data usage.
 */
function DisplayDataUsage(&$extraFooterScripts)
{
    exec("ip -o link show | awk -F ': ' '{print $2}' | grep -v lo ", $interfacesWlo);
    echo renderTemplate("data_usage", [ "interfaces" => $interfacesWlo ]);

    $extraFooterScripts[] = array('src'=>'vendor/raphael/raphael.min.js',
                                  'defer'=>false);
    $extraFooterScripts[] = array('src'=>'vendor/morrisjs/morris.min.js', 'defer'=>false);
    $extraFooterScripts[] = array('src'=>'vendor/datatables/js/jquery.dataTables.min.js', 'defer'=>false);
    $extraFooterScripts[] = array('src'=>'js/bandwidthcharts.js', 'defer'=>false);
}
