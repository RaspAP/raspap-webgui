<?php

/**
 * Generate html for displaying data usage.
 */
function DisplayDataUsage()
{
    exec("ip -o link show | awk -F ': ' '{print $2}' | grep -v lo ", $interfacesWlo);
    echo renderTemplate("data_usage", [ "interfaces" => $interfacesWlo ]);

}
