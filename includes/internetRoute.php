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
    exec('ip route list |  sed -rn "s/default via (\b([0-9]{1,3}\.){3}[0-9]{1,3}).*dev (\w*)(.*((\b([0-9]{1,3}\.){3}[0-9]{1,3})))?/\3 \5 \1/p"', $routes);
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
    // add the interface that internet traffic really uses, if it has no
    // default route in the main table (for example WireGuard with wg-quick)
    $path = getInternetPath();
    $routeDevs = array_map(fn($route) => explode(' ', $route)[0], $routes ?? []);
    if ($path !== null && !in_array($path['interface'], $routeDevs, true)) {
        $routes[] = $path['interface'] . ' ' . $path['ip-address'] . ' -';
    }
    if (!empty($routes)) {
        foreach ($routes as $i => $route) {
            $prop = explode(' ', $route);
            $rInfo[$i]["interface"] = $dev = $prop[0];
            $rInfo[$i]["ip-address"] = $prop[1];
            $rInfo[$i]["gateway"] = $prop[2];
            // resolve the name of the gateway (if possible)
            unset($host);
            if ($prop[2] !== '-') {
                exec('host ' . $prop[2] . ' | sed -rn "s/.*domain name pointer (.*)\./\1/p" | head -n 1', $host);
            }
            $rInfo[$i]["gw-name"] = empty($host) ? "*" : $host[0];
            $rInfo[$i]["isAP"] = $isAP = isAccessPoint($dev);
            $rInfo[$i]["path-interface"] = $path['interface'] ?? null;
            $rInfo[$i]["in-use"] = ($dev === ($path['interface'] ?? null));
            if (isset($checkAccess) && $checkAccess && !$isAP) {
                $access = checkInternetAccess($dev);
                $rInfo[$i]["access-ip"] = $access['ip'];
                $rInfo[$i]["access-dns"] = $access['dns'];
                $rInfo[$i]["access-url"] = $access['http'];
            }
        }
    } else {
        $rInfo = array("error" => "No route to the internet found");
    }
    return $rInfo;
}

/*
 * Finds the interface that traffic to the internet uses
 *
 * ip route get follows the policy routing rules, so this also finds a VPN
 * that has its default route in another table, for example wg-quick.
 *
 * @return array|null interface and source address, or null if no route
 */
function getInternetPath()
{
    exec('ip route get ' . escapeshellarg(RASPI_ACCESS_CHECK_IP) . ' 2>/dev/null', $output);
    $output = implode(' ', $output);
    if (!preg_match('/\bdev (\S+)/', $output, $dev)) {
        return null;
    }
    preg_match('/\bsrc (\S+)/', $output, $src);
    return [
        'interface' => $dev[1],
        'ip-address' => $src[1] ?? '-'
    ];
}

/*
 * Checks internet access through an interface, with and without DNS
 * resolution, and with HTTP
 *
 * @param string $iface
 * @return array booleans for ip, dns and http
 */
function checkInternetAccess($iface)
{
    $ping = 'ping -W1 -c 1 -I ' . escapeshellarg($iface) . ' ';
    $okPattern = ' 2>/dev/null | sed -rn "s/.*icmp_seq=1.*time=.*/OK/p"';
    exec($ping . escapeshellarg(RASPI_ACCESS_CHECK_IP) . $okPattern, $okip);
    exec($ping . escapeshellarg(RASPI_ACCESS_CHECK_DNS) . $okPattern, $okdns);
    return [
        'ip' => !empty($okip),
        'dns' => !empty($okdns),
        'http' => (bool) preg_match('/OK.*/', checkHTTPAccess($iface))
    ];
}

function detectCaptivePortal($iface) {
    $result=checkHTTPAccess($iface, true);
    $checkConnect=array( "state"=>"FAILED", "URL"=>"", "interface"=> $iface, "url" => "" );
    if ( !empty($result) && !preg_match('/FAILED/i',$result) ) {
       $checkConnect["state"]=preg_match('/(PORTAL|OK)/i',$result);
       if ( preg_match('/PORTAL (.*)/i',$result ,$url) && !empty($url) ) {
          $checkConnect["URL"]=$url[1];
       }
    }
    return $checkConnect;
}

function checkHTTPAccess($iface, $detectPortal=false) {

    $ret="FAILED no HTTP access";
    exec('timeout 5 curl -is ' . RASPI_ACCESS_CHECK_URL . ' --interface ' . escapeshellarg($iface), $rcurl);
    if ( !empty($rcurl) && preg_match("/^HTTP\/[0-9\.]+ ([0-9]+)/m",$rcurl=implode("\n",$rcurl),$code) ) {
       $code = $code[1];
       if ( $code == 200 )  {
           if ( preg_match("/<meta\s*http-equiv=\"refresh\".*content=\".*url=([^\s]+)\".*>/", $rcurl, $url) ) {
               $code = 302;
               $rcurl = "Location: " . $url[1];
               unset($url);
           }
       }
       switch($code) {
          case 302:
          case 307:
            if ( $detectPortal ) {
               if ( preg_match("/^Location:\s*(https?:\/\/[^?[:space:]]+)/m", $rcurl, $url) ) {
                  $url=$url[1];
                  if ( preg_match('/^https?:\/\/([^:\/]*).*/i', $url, $srv) && isset($srv[1]) ) {
                     $srv=$srv[1];
                     if ( preg_match('/^(([0-9]{1,3}\.){3}[0-9]{1,3}).*/', $srv, $ip) && isset($ip[1]) ) {
                        $ret="PORTAL " . $url;
                     }
                     else {
                        exec('timeout 7 sudo nmap --script=broadcast-dhcp-discover -e ' . $iface . ' 2> /dev/null | sed -rn "s/.*Domain Name Server:\s*(([0-9]{1,3}\.){3}[0-9]{1,3}).*/\1/pi"', $nameserver);
                        if ( !empty($nameserver) ) {
                           $nameserver=$nameserver[0];
                           exec('host ' . $srv . ' ' . $nameserver . ' | sed -rn "s/.*has address ((([0-9]{1,3}\.){3}[0-9]{1,3})).*/\1/p"', $ip2);
                           if ( !empty($ip2) ) {
                              $ip2=$ip2[0];
                              $url=preg_replace("/" . $srv . "/",$ip2,$url);
                              $ret="PORTAL " . $url;
                           }
                           else $ret="FAILED name " . $srv . " could not be resolved";
                        }
                        else $ret="FAILED no name server";
                     }
                  }
               }
            }
            break;
          case RASPI_ACCESS_CHECK_URL_CODE:
            $ret="OK internet access";
            break;
          default:
            $ret="FAILED unexpected response " . $code[0];
            break;
       }
    }
    return $ret;
}
/*
 * Fetches raw output of ip route
 *
 * @return string
 */
function getRouteInfoRaw()
{
    exec('ip route list', $routes);
    return $routes;
}
