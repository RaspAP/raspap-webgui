<?php

require_once 'includes/status_messages.php';
require_once 'includes/functions.php';

define('RASPAP_IPTABLES_SCRIPT', "/tmp/iptables_raspap.sh");
define('RASPAP_IP6TABLES_SCRIPT', "/tmp/ip6tables_raspap.sh");

/**
 *
 * @param  array $rule
 * @param  array $conf
 * @return array $don
 */
function getDependson(&$rule, &$conf)
{
    if (isset($rule["dependson"][0]) ) {
        $don = &$rule["dependson"];
        if (!empty($don[0]) && isset($conf[$don[0]["var"]]) ) {
            if (!isset($don[0]["type"]) ) { $don[0]["type"]="bool";
            }
            return $don;
        }
    }
    return false;
}

/**
 *
 * @param  array $sect
 * @param  array $conf
 * @return boolean $active
 */
function isRuleEnabled(&$sect, &$conf)
{
    $fw_on = isset($conf["firewall-enable"]) && $conf["firewall-enable"];
    $active = isset($sect["fw-state"]) && $sect["fw-state"]==1;
    $active = $fw_on ? $active : !$active;
    $active = $active || !isset($sect["fw-state"]);
    if (($don = getDependson($sect, $conf)) !== false 
        && $don[0]["type"] == "bool" && !$conf[$don[0]["var"]] 
    ) {  $active = false;
    }
    return $active;
}

/**
 *
 * @param  array $sect
 * @param  array $conf
 * @return string $str
 */
function createRuleStr(&$sect, &$conf)
{
    if (!is_array($sect["rules"]) ) { return "";
    }
    $rules = $sect["rules"];
    $depon = getDependson($sect, $conf);
    $rs = array();
    foreach ( $rules as $rule ) {
        if (preg_match('/\$[a-z0-9]*\$/i', $rule) ) {
            $r = array($rule);
            foreach ( $depon as $dep ) {
                $rr = array();
                $repl=$val="";
                switch ( $dep["type"] ) {
                case "list":
                    if (isset($dep["var"]) && !empty($conf[$dep["var"]]) ) { $val = explode(' ', $conf[$dep["var"]]);
                    }
                    if (!empty($val) && isset($dep["replace"]) ) { $repl=$dep["replace"];
                    }
                    break;
                case "string":
                    if (isset($dep["var"]) ) { $val=$conf[$dep["var"]];
                    }
                    if (!empty($val) && isset($dep["replace"]) ) { $repl=$dep["replace"];
                    }
                    break;
                default:
                    break;
                }
                if (!empty($repl) && !empty($val) ) {
                    if (is_array($val) ) {
                        foreach ( $val as $v ) { $rr = array_merge($rr, str_replace($repl, $v, $r));
                        }
                    }
                    else { $rr = array_merge($rr, str_replace($repl, $val, $r));
                    }
                }
                $r = !empty($rr) ? $rr : $r;
            }
            $rs = array_merge($rs, $rr);
        } else {
            $rs[] = $rule;
        }
    }
    $str="";
    foreach ( $rs as $r ) {
        if (!preg_match('/\$[a-z0-9]*\$/i', $r) ) { $str .= '$IPT '.$r."\n";
        }
    }
    return $str;
}


/**
 *
 * @param  array $rule
 * @return boolean
 */
function isIPv4(&$rule)
{
    return !isset($rule["ip-version"]) || strstr($rule["ip-version"], "4") !== false; 
}

/**
 *
 * @param  array $rule
 * @return boolean
 */
function isIPv6(&$rule)
{
    return !isset($rule["ip-version"]) || strstr($rule["ip-version"], "6") !== false; 
}

/**
 *
 * @return boolean 
 */
function configureFirewall()
{
    $json = file_get_contents(RASPI_IPTABLES_CONF);
    $ipt  = json_decode($json, true);
    $conf = ReadFirewallConf();
    $txt = "#!/bin/bash\n";
    file_put_contents(RASPAP_IPTABLES_SCRIPT, $txt);
    file_put_contents(RASPAP_IP6TABLES_SCRIPT, $txt);
    file_put_contents(RASPAP_IPTABLES_SCRIPT, 'IPT="iptables"'."\n", FILE_APPEND);
    file_put_contents(RASPAP_IP6TABLES_SCRIPT, 'IPT="ip6tables"'."\n", FILE_APPEND);
    $txt = "\$IPT -F\n";
    $txt .= "\$IPT -X\n";
    $txt .= "\$IPT -t nat -F\n";
    file_put_contents(RASPAP_IPTABLES_SCRIPT, $txt, FILE_APPEND);
    file_put_contents(RASPAP_IP6TABLES_SCRIPT, $txt, FILE_APPEND);
    if (empty($conf) || empty($ipt) ) { return false;
    }
    $count=0;
    foreach ( $ipt["order"] as $idx ) {
        if (isset($ipt[$idx]) ) {
            foreach ( $ipt[$idx] as $i => $sect ) {
                if (isRuleEnabled($sect, $conf) ) {
                    $str_rules= createRuleStr($sect, $conf);
                    if (!empty($str_rules) ) {
                        if (isIPv4($sect) ) { file_put_contents(RASPAP_IPTABLES_SCRIPT, $str_rules, FILE_APPEND);
                        }
                        if (isIPv6($sect) ) { file_put_contents(RASPAP_IP6TABLES_SCRIPT, $str_rules, FILE_APPEND);
                        }
                        ++$count;
                    }
                }
            }
        }
    }
    if ($count > 0 ) {
        exec("chmod +x ".RASPAP_IPTABLES_SCRIPT);
        exec("sudo ".RASPAP_IPTABLES_SCRIPT);
        exec("sudo iptables-save | sudo tee /etc/iptables/rules.v4");
        unlink(RASPAP_IPTABLES_SCRIPT);
        exec("chmod +x ".RASPAP_IP6TABLES_SCRIPT);
        exec("sudo ".RASPAP_IP6TABLES_SCRIPT);
        exec("sudo ip6tables-save | sudo tee /etc/iptables/rules.v6");
        unlink(RASPAP_IP6TABLES_SCRIPT);
    }
    return ($count > 0);
}

/**
 *
 * @param array $conf
 * @return string $ret
 */
function WriteFirewallConf($conf)
{
    $ret = false;
    if (is_array($conf) ) { write_php_ini($conf, RASPI_FIREWALL_CONF);
    }
    return $ret;
}

/**
 *
 * @return array $conf
 */
function ReadFirewallConf()
{
    $conf = array();
    if (file_exists(RASPI_FIREWALL_CONF) ) {
        $conf = parse_ini_file(RASPI_FIREWALL_CONF);
    }
    if ( !isset($conf["firewall-enable"]) ) {
        $conf["firewall-enable"] = false;
        $conf["ssh-enable"] = false;
        $conf["http-enable"] = false;
        $conf["excl-devices"] = "";
        $conf["excluded-ips"] = "";
        $conf["ap-device"] = "";
        $conf["client-device"] = "";
        $conf["restricted-ips"] = "";
    }
    exec('ifconfig | grep -E -i "^tun[0-9]"', $ret);
    $conf["openvpn-enable"] = !empty($ret);
    unset($ret);
    exec('ifconfig | grep -E -i "^wg[0-9]"', $ret);
    $conf["wireguard-enable"] = !empty($ret);
    return $conf;
}

/**
 *
 * @return string $ips
 */
function getVPN_IPs()
{
    $ips = "";
    // get openvpn and wireguard server IPs
    if (RASPI_OPENVPN_ENABLED && ($fconf = glob(RASPI_OPENVPN_CLIENT_PATH ."/*.conf")) !== false && !empty($fconf) ) {
        foreach ( $fconf as $f ) {
            unset($result);
            exec('cat '.$f.' |  sed -rn "s/^remote\s*([a-z0-9\.\-\_:]*)\s*([0-9]*)\s*$/\1 \2/ip" ', $result);
            if (!empty($result) ) {
                $result = explode(" ", $result[0]);
                $ip = (isset($result[0])) ? $result[0] : "";
                $port = (isset($result[1])) ? $result[1] : "";
                if (!empty($ip) ) {
                    $ip = gethostbyname($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP) && strpos($ips, $ip) === false ) { $ips .= " $ip";
                    }
                }
            }
        }
    }
    // get wireguard server IPs
    if (RASPI_WIREGUARD_ENABLED && ($fconf = glob(RASPI_WIREGUARD_PATH ."/*.conf")) !== false && !empty($fconf) ) {
        foreach ( $fconf as $f ) {
            unset($result);
            exec('sudo /bin/cat '.$f.' |  sed -rn "s/^endpoint\s*=\s*\[?([a-z0-9\.\-\_:]*)\]?:([0-9]*)\s*$/\1 \2/ip" ', $result);
            if (!empty($result) ) {
                $result = explode(" ", $result[0]);
                $ip = (isset($result[0])) ? $result[0] : "";
                $port = (isset($result[1])) ? $result[1] : "";
                if (!empty($ip) ) {
                     $ip = gethostbyname($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP) && strpos($ips, $ip) === false ) { $ips .= " $ip";
                    }
                }
            }
        }
    }
    return trim($ips);
}

/**
 *
 * @return array $fw_conf
 */
function getFirewallConfiguration() 
{
    $fw_conf = ReadFirewallConf();
    
    $json = file_get_contents(RASPI_IPTABLES_CONF);
    getWifiInterface();
    $ap_device = $_SESSION['ap_interface'];
    $clients = getClients();
    $str_clients = "";
    foreach( $clients["device"] as $dev ) {
        if (!$dev["isAP"] ) {
            if (!empty($str_clients) ) { $str_clients .= ", ";
            }
            $str_clients .= $dev["name"];
        }
    }
    $fw_conf["ap-device"] = $ap_device;
    $fw_conf["client-list"] = $str_clients;
    $id=findCurrentClientIndex($clients);
    if ($id >= 0 ) { $fw_conf["client-device"] = $clients["device"][$id]["name"];
    }
    return $fw_conf;
}

/**
 *
 */
function updateFirewall() 
{
    $fw_conf = getFirewallConfiguration();
    if ( isset($fw_conf["firewall-enable"]) ) {
        WriteFirewallConf($fw_conf);
        configureFirewall();
    }
    return;
}

/**
 *
 */
function DisplayFirewallConfig()
{
    $status = new StatusMessages();

    $fw_conf = getFirewallConfiguration();
    $ap_device = $fw_conf["ap-device"];
    $str_clients = $fw_conf["client-list"];

    if (!empty($_POST)) {
        $fw_conf["ssh-enable"] = isset($_POST['ssh-enable']);
        $fw_conf["http-enable"] = isset($_POST['http-enable']);
        $fw_conf["firewall-enable"] = isset($_POST['firewall-enable']) || isset($_POST['apply-firewall']);
        if (isset($_POST['firewall-enable']) ) { $status->addMessage(_('Firewall is now enabled'), 'success');
        }
        if (isset($_POST['apply-firewall']) ) {  $status->addMessage(_('Firewall settings changed'), 'success');
        }
        if (isset($_POST['firewall-disable']) ) { $status->addMessage(_('Firewall is now disabled'), 'warning');
        }
        if (isset($_POST['save-firewall']) ) {  $status->addMessage(_('Firewall settings saved. Firewall is still disabled.'), 'success');
        }
        if (isset($_POST['excl-devices'])  ) {
            $excl = filter_var($_POST['excl-devices'], FILTER_SANITIZE_STRING);
            $excl = str_replace(',', ' ', $excl);
            $excl = trim(preg_replace('/\s+/', ' ', $excl));
            if ($fw_conf["excl-devices"] != $excl ) {
                $status->addMessage(_('Exclude devices '. $excl), 'success');
                $fw_conf["excl-devices"] = $excl;
            }
        }
        if (isset($_POST['excluded-ips'])  ) {
            $excl = filter_var($_POST['excluded-ips'], FILTER_SANITIZE_STRING);
            $excl = str_replace(',', ' ', $excl);
            $excl = trim(preg_replace('/\s+/', ' ', $excl));
            if (!empty($excl) ) {
                $excl = explode(' ', $excl);
                $str_excl = "";
                foreach ( $excl as $ip ) {
                    if (filter_var($ip, FILTER_VALIDATE_IP) ) { $str_excl .= "$ip ";
                    } else { $status->addMessage(_('Exclude IP address '. $ip . ' failed - not a valid IP address'), 'warning');
                    }
                }
            }
            $str_excl = trim($str_excl);
            if ($fw_conf["excluded-ips"] != $str_excl ) {
                 $status->addMessage(_('Exclude IP address(es) '. $str_excl), 'success');
                 $fw_conf["excluded-ips"] = $str_excl;
            }
        }
        WriteFirewallConf($fw_conf);
        configureFirewall();
    }
    $vpn_ips = getVPN_IPs();
    echo renderTemplate(
        "firewall", compact(
            "status",
            "ap_device",
            "str_clients",
            "fw_conf",
            "vpn_ips"
        )
    );
}

