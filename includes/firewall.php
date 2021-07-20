<?php

require_once 'includes/status_messages.php';
require_once 'includes/functions.php';

define('RASPAP_IPTABLES_SCRIPT',"/tmp/iptables_raspap.sh");

function getDependson(&$rule, &$conf) {
   if ( isset($rule["dependson"][0]) ) {
      $don = &$rule["dependson"];
      if ( !empty($don[0]) && isset($conf[$don[0]["var"]]) ) {
         if ( !isset($don[0]["type"]) ) $don[0]["type"]="bool";
         return $don;
      }
   }
   return false;
}

function isRuleEnabled(&$sect, &$conf) {
   $fw_on = isset($conf["firewall-enable"]) && $conf["firewall-enable"];
   $active = isset($sect["fw-state"]) && $sect["fw-state"]==1;
   $active = $fw_on ? $active : !$active;
   $active = $active || !isset($sect["fw-state"]);
   if ( ($don = getDependson($sect, $conf)) !== false &&
         $don[0]["type"] == "bool" && !$conf[$don[0]["var"]] )  $active = false;
   return $active;
}

function createRuleStr(&$sect, &$conf) {
   if ( !is_array($sect["rules"]) ) return "";
   $rules = $sect["rules"];
   $depon = getDependson($sect,$conf);
   $rs = array();
   foreach ( $rules as $rule ) {
      if ( preg_match('/\$[a-z0-9]*\$/i',$rule) ) {
         $r = array($rule);
         foreach ( $depon as $dep ) {
            $rr = array();
            $repl=$val="";
            switch ( $dep["type"] ) {
               case "list":
                   if ( isset($dep["var"]) && !empty($conf[$dep["var"]]) ) $val = explode(',', $conf[$dep["var"]]);
                   if ( !empty($val) && isset($dep["replace"]) ) $repl=$dep["replace"];
                   break;
               case "string":
                   if ( isset($dep["var"]) ) $val=$conf[$dep["var"]];
                   if ( !empty($val) && isset($dep["replace"]) ) $repl=$dep["replace"];
                   break;
               default:
                   break;
            }
            if ( !empty($repl) && !empty($val) ) {
               if ( is_array($val) ) {
                  foreach ( $val as $v ) $rr = array_merge($rr,str_replace($repl, $v, $r));
               }
               else $rr = array_merge($rr, str_replace($repl, $val, $r));
            }
            $r = !empty($rr) ? $rr : $r;
         }
         $rs = array_merge($rs,$rr);
      } else {
         $rs[] = $rule;
      }
   }
   $str="";
   foreach ( $rs as $r ) {
      if ( !preg_match('/\$[a-z0-9]*\$/i',$r) ) $str .= "iptables ".$r."\n";
   }
   return $str;
}

function configureFirewall() {
    $json = file_get_contents(RASPAP_IPTABLES_CONF);
    $ipt  = json_decode($json, true);
    $conf = ReadFirewallConf();
    $txt = "#!/bin/bash\n";
    $txt .= "iptables -F\n";
    $txt .= "iptables -X\n";
    $txt .= "iptables -t nat -F\n";
    file_put_contents(RASPAP_IPTABLES_SCRIPT, $txt);
    if ( empty($conf) || empty($ipt) ) return false;
    $count=0;
    foreach ( $ipt["order"] as $idx ) {
       if ( isset($ipt[$idx]) ) {
          foreach ( $ipt[$idx] as $i => $sect ) {
             if ( isRuleEnabled($sect, $conf) ) {
               $str_rules= createRuleStr($sect, $conf);
               if ( !empty($str_rules) ) {
                  file_put_contents(RASPAP_IPTABLES_SCRIPT, $str_rules, FILE_APPEND);
                  ++$count;
               }
             }
          }
       }
    }
    if ( $count > 0 ) {
       exec("chmod +x ".RASPAP_IPTABLES_SCRIPT);
       exec("sudo ".RASPAP_IPTABLES_SCRIPT);
//       exec("sudo iptables-save > /etc/iptables/rules.v4");
//       unlink(RASPAP_IPTABLES_SCRIPT);
    }
    return ($count > 0);
}

function WriteFirewallConf($conf) {
	$ret = false;
     	if ( is_array($conf) ) write_php_ini($conf,RASPAP_FIREWALL_CONF);
	return $ret;
}


function ReadFirewallConf() {
    if ( file_exists(RASPAP_FIREWALL_CONF) ) {
       $conf = parse_ini_file(RASPAP_FIREWALL_CONF);
    } else {
       $conf = array();
       $conf["firewall-enable"] = false;
       $conf["openvpn-enable"] = false;
       $conf["openvpn-serverip"] = "";
       $conf["wireguard-enable"] = false;
       $conf["wireguard-serverip"] = "";
       $conf["ssh-enable"] = false;
       $conf["http-enable"] = false;
       $conf["excl-devices"] = "";
       $conf["excluded-ips"] = "";
       $conf["ap-device"] = "";
       $conf["client-device"] = "";
       $conf["restricted-ips"] = "";
    }

# get openvpn server IP (if existing)
    if ( RASPI_OPENVPN_ENABLED && file_exists(RASPI_OPENVPN_CLIENT_CONFIG) ) {
      exec('cat '.RASPI_OPENVPN_CLIENT_CONFIG.' |  sed -rn "s/^remote\s*([a-z0-9\.\-\_]*)\s*([0-9]*).*$/\1/ip" ', $ret);
      if ( !empty($ret) ) {
          $ip = $ret[0];
          $ip = ( filter_var($ip, FILTER_VALIDATE_IP) !== false  ) ? $ip : gethostbyname($ip);
          if ( !empty($ip) ) {
              $conf["openvpn-serverip"] = "$ip";
              $conf["openvpn-enable"] = true;
          }
      }
    }
# get wireguard server IP (if existing)
    if ( RASPI_WIREGUARD_ENABLED && file_exists(RASPI_WIREGUARD_CONFIG) ) {
# search for endpoint
    }
    return $conf;
}

function DisplayFirewallConfig()
{

    $status = new StatusMessages();

    $json = file_get_contents(RASPAP_IPTABLES_CONF);
    $ipt_rules = json_decode($json, true);

    getWifiInterface();
    $ap_device = $_SESSION['ap_interface'];
    $clients = getClients();
    $str_clients = "";
    foreach( $clients["device"] as $dev ) {
       if ( !$dev["isAP"] ) {
          if ( !empty($str_clients) ) $str_clients .= ", ";
          $str_clients .= $dev["name"];
       }
    }
    $fw_conf = ReadFirewallConf();
    $fw_conf["ap-device"] = $ap_device;
    $id=findCurrentClientIndex($clients);
    if ( $id >= 0 ) $fw_conf["client-device"] = $clients["device"][$id]["name"];
    if (!empty($_POST)) {
        $fw_conf["ssh-enable"] = isset($_POST['ssh-enable']);
        $fw_conf["http-enable"] = isset($_POST['http-enable']);
        $fw_conf["firewall-enable"] = isset($_POST['firewall-enable']) || isset($_POST['apply-firewall']);
        if ( isset($_POST['firewall-enable']) ) $status->addMessage(_('Firewall is now enabled'), 'success');
        if ( isset($_POST['apply-firewall']) )  $status->addMessage(_('Firewall settings changed'), 'success');
        if ( isset($_POST['firewall-disable']) ) $status->addMessage(_('Firewall is now disabled'), 'warning');
        if ( isset($_POST['save-firewall']) )  $status->addMessage(_('Firewall settings saved. Firewall is still disabled.'), 'success');
        if ( isset($_POST['excl-devices'])  ) {
           $excl = filter_var($_POST['excl-devices'], FILTER_SANITIZE_STRING);
           $excl = str_replace(' ', '', $excl);
           if ( !empty($excl) && $fw_conf["excl-devices"] != $excl ) {
               $status->addMessage(_('Exclude devices '. $excl), 'success');
               $fw_conf["excl-devices"] = $excl;
           }
        }
        WriteFirewallConf($fw_conf);
        configureFirewall();
    }
    echo renderTemplate("firewall", compact(
                "status",
                "ap_device",
                "str_clients",
                "fw_conf",
                "ipt_rules")
    );
}
