<?php

/*
 * Fetches details of the kernel routing table
 *
 * @param boolean $checkAccesss Perform connectivity test
 * @return string
 */
function getRouteInfo($checkAccess)
{
    $rInfo = array();
    // get all default routes
    exec('ip route list |  sed -rn "s/default via (([0-9]{1,3}\.){3}[0-9]{1,3}).*dev (\w*).*src (([0-9]{1,3}\.){3}[0-9]{1,3}).*/\3 \4 \1/p"', $routes);
    $devpat = array("tun", "ppp");  // routing in case of VPN and PPP connection are different
    foreach ($devpat as $pat) {
       exec('ip route list |  grep -oP "'.$pat.'[0-9]" | sort -u', $devs);
    }
    if (!empty($devs)) {
        foreach ($devs as $dev) {
            unset($gateway);
            unset($ipadd);
            exec('ip route list |  sed -rn "s/^.*via (([0-9]{1,3}\.){3}[0-9]{1,3}) dev "' . $dev . '".*$/\1/p" | head -n 1', $gateway);
            if (empty($gateway)) {
                exec('ip route list | sed -rn "s/(([0-9]{1,3}\.){3}[0-9]{1,3}).*dev.*"' . $dev . '".*scope link src.*/\1/p"', $gateway);
            }
            exec('ifconfig -a | grep -i ' . $dev . ' -A 1 | grep -oP "(?<=inet )([0-9]{1,3}\.){3}[0-9]{1,3}"', $ipadd);
            if (!empty($gateway) && !empty($ipadd)) {
                $routes[]="$dev $ipadd[0] $gateway[0]";
            }
        }
    }
    if (!empty($routes)) {
        foreach ($routes as $i => $route) {
            $prop = explode(' ', $route);
            $rInfo[$i]["interface"] = $prop[0];
            $rInfo[$i]["ip-address"] = $prop[1];
            $rInfo[$i]["gateway"] = $prop[2];
            // resolve the name of the gateway (if possible)
            unset($host);
            exec('host ' . $prop[2] . ' | sed -rn "s/.*domain name pointer (.*)\./\1/p" | head -n 1', $host);
            $rInfo[$i]["gw-name"] = empty($host) ? "*" : $host[0];
            if (isset($checkAccess) && $checkAccess) {
                // check internet connectivity w/ and w/o DNS resolution
                unset($okip);
                exec('ping -W1 -c 1 -I ' . $prop[0] . ' ' . RASPI_ACCESS_CHECK_IP . ' |  sed -rn "s/.*icmp_seq=1.*time=.*/OK/p"', $okip);
                $rInfo[$i]["access-ip"] = empty($okip) ? false : true;
                unset($okdns);
                exec('ping -W1 -c 1 -I ' . $prop[0] . ' ' . RASPI_ACCESS_CHECK_DNS . ' |  sed -rn "s/.*icmp_seq=1.*time=.*/OK/p"', $okdns);
                $rInfo[$i]["access-dns"] = empty($okdns) ? false : true;
            }
        }
    } else {
        $rInfo = array("error" => "No route to the internet found");
    }
    return $rInfo;
}

