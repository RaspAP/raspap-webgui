<?php

$rInfo=array();
// get all default routes
exec('ip route list |  sed -rn "s/default via (([0-9]{1,3}\.){3}[0-9]{1,3}).*dev (\w*).*src (([0-9]{1,3}\.){3}[0-9]{1,3}).*/\3 \4 \1/p"', $routes);
exec('ip route list |  sed -rn "s/default dev (\w*) scope link/\1/p"',$devs);
if(!empty($devs)) {
   foreach ($devs as $dev)
   exec('ip route list |  sed -rn "s/(([0-9]{1,3}\.){3}[0-9]{1,3}).*dev.*("'.$dev.'").*scope link src (([0-9]{1,3}\.){3}[0-9]{1,3}).*/\3 \4 \1/p"',$routes);
} 
if (!empty($routes) ) {
    foreach ($routes as $i => $route) {
        $prop=explode(' ', $route);
        $rInfo[$i]["interface"]=$prop[0];
        $rInfo[$i]["ip-address"]=$prop[1];
        $rInfo[$i]["gateway"]=$prop[2];
        // resolve the name of the gateway (if possible)
        unset($host);
        exec('host '.$prop[2].' | sed -rn "s/.*domain name pointer (.*)\./\1/p" | head -n 1', $host);
        $rInfo[$i]["gw-name"] = empty($host) ? "*" : $host[0];
        if (isset($checkAccess) && $checkAccess) {
            // check internet connectivity w/ and w/o DNS resolution
            unset($okip);
            exec('ping -W1 -c 1 -I '.$prop[0].' '.RASPI_ACCESS_CHECK_IP.' |  sed -rn "s/.*icmp_seq=1.*time=.*/OK/p"',$okip);
            $rInfo[$i]["access-ip"] = empty($okip) ? false : true;
            unset($okdns);
            exec('ping -W1 -c 1 -I '.$prop[0].' '.RASPI_ACCESS_CHECK_DNS.' |  sed -rn "s/.*icmp_seq=1.*time=.*/OK/p"',$okdns);
            $rInfo[$i]["access-dns"] = empty($okdns) ? false : true;
        }
    }
} else {
    $rInfo = array("error"=>"No route to the internet found");
}
$rInfo_json = json_encode($rInfo);
?>
