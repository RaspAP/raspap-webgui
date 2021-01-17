<?php

require_once 'status_messages.php';
require_once 'app/lib/system.php';
require_once 'includes/wifi_functions.php';
require_once 'includes/config.php';

getWifiInterface();

/**
 * Initialize hostapd values, display interface
 *
 */
function DisplayHostAPDConfig()
{
    $status = new StatusMessages();
    $system = new System();
    $arrConfig = array();
    $arr80211Standard = [
        'a' => '802.11a - 5 GHz',
        'b' => '802.11b - 2.4 GHz',
        'g' => '802.11g - 2.4 GHz',
        'n' => '802.11n - 2.4 GHz',
        'ac' => '802.11.ac - 5 GHz'
    ];
    $arrSecurity = array(1 => 'WPA', 2 => 'WPA2', 3 => 'WPA+WPA2', 'none' => _("None"));
    $arrEncType = array('TKIP' => 'TKIP', 'CCMP' => 'CCMP', 'TKIP CCMP' => 'TKIP+CCMP');
    $managedModeEnabled = false;
    exec("ip -o link show | awk -F': ' '{print $2}'", $interfaces);
    exec("iw reg get | awk '/country / { sub(/:/,\"\",$2); print $2 }'", $country_code);

    if (!RASPI_MONITOR_ENABLED) {
        if (isset($_POST['SaveHostAPDSettings'])) {
            SaveHostAPDConfig($arrSecurity, $arrEncType, $arr80211Standard, $interfaces, $status);
        }
    }
    $arrHostapdConf = parse_ini_file('/etc/raspap/hostapd.ini');

    if (!RASPI_MONITOR_ENABLED) {
         if (isset($_POST['StartHotspot']) || isset($_POST['RestartHotspot'])) {
            $status->addMessage('Attempting to start hotspot', 'info');
            if ($arrHostapdConf['BridgedEnable'] == 1) {
                exec('sudo /etc/raspap/hostapd/servicestart.sh --interface br0 --seconds 3', $return);
            } elseif ($arrHostapdConf['WifiAPEnable'] == 1) {
                exec('sudo /etc/raspap/hostapd/servicestart.sh --interface uap0 --seconds 3', $return);
            } else {
                exec('sudo /etc/raspap/hostapd/servicestart.sh --seconds 3', $return);
            }
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        } elseif (isset($_POST['StopHotspot'])) {
            $status->addMessage('Attempting to stop hotspot', 'info');
            exec('sudo /bin/systemctl stop hostapd.service', $return);
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        }
    }

    exec('cat '. RASPI_HOSTAPD_CONFIG, $hostapdconfig);
    exec('iwgetid '. $_POST['interface']. ' -r', $wifiNetworkID);
    if (!empty($wifiNetworkID[0])) {
        $managedModeEnabled = true;
    }
    $hostapdstatus = $system->hostapdStatus();
    $serviceStatus = $hostapdstatus[0] == 0 ? "down" : "up";

    foreach ($hostapdconfig as $hostapdconfigline) {
        if (strlen($hostapdconfigline) === 0) {
            continue;
        }
        if ($hostapdconfigline[0] != "#") {
            $arrLine = explode("=", $hostapdconfigline);
            $arrConfig[$arrLine[0]]=$arrLine[1];
        }
    };
    // assign beacon_int boolean if value is set
    if (isset($arrConfig['beacon_int'])) {
        $arrConfig['beacon_interval_bool'] = 1;
    }
    // assign disassoc_low_ack boolean if value is set
    if (isset($arrConfig['disassoc_low_ack'])) {
        $arrConfig['disassoc_low_ack_bool'] = 1;
    }
    // assign country_code from iw reg if not set in config
    if (!isset($arrConfig['country_code']) && isset($country_code[0])) {
        $arrConfig['country_code'] = $country_code[0];
    }

    echo renderTemplate(
        "hostapd", compact(
            "status",
            "serviceStatus",
            "hostapdstatus",
            "managedModeEnabled",
            "interfaces",
            "arrConfig",
            "arr80211Standard",
            "selectedHwMode",
            "arrSecurity",
            "arrEncType",
            "arrHostapdConf"
        )
    );
}

/**
 * Validate user input, save configs for hostapd, dnsmasq & dhcp
 *
 * @param array $wpa_array
 * @param array $enc_types
 * @param array $modes
 * @param string $interface
 * @param object $status
 * @return boolean
 */
function SaveHostAPDConfig($wpa_array, $enc_types, $modes, $interfaces, $status)
{
    // It should not be possible to send bad data for these fields so clearly
    // someone is up to something if they fail. Fail silently.
    if (!(array_key_exists($_POST['wpa'], $wpa_array) 
        && array_key_exists($_POST['wpa_pairwise'], $enc_types) 
        && array_key_exists($_POST['hw_mode'], $modes))
    ) {
        error_log("Attempting to set hostapd config with wpa='".$_POST['wpa']."', wpa_pairwise='".$_POST['wpa_pairwise']."' and hw_mode='".$_POST['hw_mode']."'");  // FIXME: log injection
        return false;
    }
    // Validate input
    $good_input = true;

    if (!filter_var($_POST['channel'], FILTER_VALIDATE_INT)) {
        $status->addMessage('Attempting to set channel to invalid number.', 'danger');
        $good_input = false;
    }
    if (intval($_POST['channel']) < 1 || intval($_POST['channel']) > RASPI_5GHZ_MAX_CHANNEL) {
        $status->addMessage('Attempting to set channel outside of permitted range', 'danger');
        $good_input = false;
    }
    $arrHostapdConf = parse_ini_file('/etc/raspap/hostapd.ini');

    // Check for Bridged AP mode checkbox
    $bridgedEnable = 0;
    if ($arrHostapdConf['BridgedEnable'] == 0) {
        if (isset($_POST['bridgedEnable'])) {
            $bridgedEnable = 1;
        }
    } else {
        if (isset($_POST['bridgedEnable'])) {
            $bridgedEnable = 1;
        }
    }
    // Check for WiFi client AP mode checkbox
    $wifiAPEnable = 0;
    if ($bridgedEnable == 0) {  // enable client mode actions when not bridged
        if ($arrHostapdConf['WifiAPEnable'] == 0) {
            if (isset($_POST['wifiAPEnable'])) {
                $wifiAPEnable = 1;
            }
        } else {
            if (isset($_POST['wifiAPEnable'])) {
                $wifiAPEnable = 1;
            }
        }
    }
    // Check for Logfile output checkbox
    $logEnable = 0;
    if ($arrHostapdConf['LogEnable'] == 0) {
        if (isset($_POST['logEnable'])) {
            $logEnable = 1;
            exec('sudo '.RASPI_CONFIG.'/hostapd/enablelog.sh');
        } else {
            exec('sudo '.RASPI_CONFIG.'/hostapd/disablelog.sh');
        }
    } else {
        if (isset($_POST['logEnable'])) {
            $logEnable = 1;
            exec('sudo '.RASPI_CONFIG.'/hostapd/enablelog.sh');
        } else {
            exec('sudo '.RASPI_CONFIG.'/hostapd/disablelog.sh');
        }
    }
    // set AP interface default, override for ap-sta & bridged options
    $ap_iface = $_POST['interface'];
    if ($wifiAPEnable) { $ap_iface = 'uap0'; }
    if ($bridgedEnable) { $ap_iface = 'br0'; }

    // persist user options to /etc/raspap
    $cfg = [];
    $cfg['WifiInterface'] = $_POST['interface'];
    $cfg['LogEnable'] = $logEnable;
    // Save previous Client mode status when Bridged
    $cfg['WifiAPEnable'] = ($bridgedEnable == 1 ? $arrHostapdConf['WifiAPEnable'] : $wifiAPEnable);
    $cfg['BridgedEnable'] = $bridgedEnable;
    $cfg['WifiManaged'] = $_POST['interface'];
    write_php_ini($cfg, RASPI_CONFIG.'/hostapd.ini');
    $_SESSION['ap_interface'] = $ap_iface;

    // Verify input
    if (empty($_POST['ssid']) || strlen($_POST['ssid']) > 32) {
        // Not sure of all the restrictions of SSID
        $status->addMessage('SSID must be between 1 and 32 characters', 'danger');
        $good_input = false;
    }

    if ($_POST['wpa'] !== 'none' 
        && (strlen($_POST['wpa_passphrase']) < 8 || strlen($_POST['wpa_passphrase']) > 63)
    ) {
        $status->addMessage('WPA passphrase must be between 8 and 63 characters', 'danger');
        $good_input = false;
    }

    if (isset($_POST['hiddenSSID'])) {
        if (!is_int((int)$_POST['hiddenSSID'])) {
            $status->addMessage('Parameter hiddenSSID not a number.', 'danger');
            $good_input = false;
        } elseif ((int)$_POST['hiddenSSID'] < 0 || (int)$_POST['hiddenSSID'] >= 3) {
            $status->addMessage('Parameter hiddenSSID contains invalid configuratie value.', 'danger');
            $good_input = false;
        } else {
            $ignore_broadcast_ssid = $_POST['hiddenSSID'];
        }
    } else {
        $ignore_broadcast_ssid = '0';
    }

    if (! in_array($_POST['interface'], $interfaces)) {
        // The user is probably up to something here but it may also be a
        // genuine error.
        $status->addMessage('Unknown interface '.htmlspecialchars($_POST['interface'], ENT_QUOTES), 'danger');
        $good_input = false;
    }
    if (strlen($_POST['country_code']) !== 0 && strlen($_POST['country_code']) != 2) {
        $status->addMessage('Country code must be blank or two characters', 'danger');
        $good_input = false;
    }
    if (isset($_POST['beaconintervalEnable'])) {
        if (!is_numeric($_POST['beacon_interval'])) {
            $status->addMessage('Beacon interval must be a numeric value', 'danger');
            $good_input = false;
        } elseif ($_POST['beacon_interval'] < 15 || $_POST['beacon_interval'] > 65535) {
            $status->addMessage('Beacon interval must be between 15 and 65535', 'danger');
            $good_input = false;
        }
    }
    $_POST['max_num_sta'] = (int) $_POST['max_num_sta'];
    $_POST['max_num_sta'] = $_POST['max_num_sta'] > 2007 ? 2007 : $_POST['max_num_sta'];
    $_POST['max_num_sta'] = $_POST['max_num_sta'] < 1 ? null : $_POST['max_num_sta'];

    if ($good_input) {
        $return = updateHostapdConfig($ignore_broadcast_ssid,$wifiAPEnble,$bridgedEnable);

        // Fetch dhcp-range, lease time from system config
        $syscfg = parse_ini_file(RASPI_DNSMASQ_PREFIX.$ap_iface.'.conf', false, INI_SCANNER_RAW);

        if ($wifiAPEnable == 1) {
            // Enable uap0 configuration for ap-sta mode
            // Set dhcp-range from system config, fallback to default if undefined
            $dhcp_range = ($syscfg['dhcp-range'] == '') ? getDefaultNetValue('dnsmasq','uap0','dhcp-range') : $syscfg['dhcp-range'];
            $config = [ '# RaspAP uap0 configuration' ];
            $config[] = 'interface=lo,uap0               # Enable uap0 interface for wireless client AP mode';
            $config[] = 'bind-dynamic                    # Hybrid between --bind-interfaces and default';
            $config[] = 'server=8.8.8.8                  # Forward DNS requests to Google DNS';
            $config[] = 'domain-needed                   # Don\'t forward short names';
            $config[] = 'bogus-priv                      # Never forward addresses in the non-routed address spaces';
            $config[] = 'dhcp-range='.$dhcp_range;
            if (!empty($syscfg['dhcp-option'])) {
                $config[] = 'dhcp-option='.$syscfg['dhcp-option'];
            }
            $config[] = PHP_EOL;
            scanConfigDir('/etc/dnsmasq.d/','uap0',$status);
            $config = join(PHP_EOL, $config);
            file_put_contents("/tmp/dnsmasqdata", $config);
            system('sudo cp /tmp/dnsmasqdata '.RASPI_DNSMASQ_PREFIX.$ap_iface.'.conf', $return);
        } elseif ($bridgedEnable !==1) {
            $dhcp_range = ($syscfg['dhcp-range'] =='') ? getDefaultNetValue('dnsmasq','wlan0','dhcp-range') : $syscfg['dhcp-range'];
            $config = [ '# RaspAP '.$_POST['interface'].' configuration' ];
            $config[] = 'interface='.$_POST['interface'];
            $config[] = 'domain-needed';
            $config[] = 'dhcp-range='.$dhcp_range;
            if (!empty($syscfg['dhcp-option'])) {
                $config[] = 'dhcp-option='.$syscfg['dhcp-option'];
            }
            $config[] = PHP_EOL;
            $config = join(PHP_EOL, $config);
            file_put_contents("/tmp/dnsmasqdata", $config);
            system('sudo cp /tmp/dnsmasqdata '.RASPI_DNSMASQ_PREFIX.$ap_iface.'.conf', $return);
        }

        // Set dhcp values from system config, fallback to default if undefined
        $jsonData = json_decode(getNetConfig($ap_iface), true);
        $ip_address = ($jsonData['StaticIP'] == '') ? getDefaultNetValue('dhcp',$ap_iface,'static ip_address') : $jsonData['StaticIP'];
        $domain_name_server = ($jsonData['StaticDNS'] =='') ? getDefaultNetValue('dhcp',$ap_iface,'static domain_name_server') : $jsonData['StaticDNS'];
        $routers = ($jsonData['StaticRouters'] == '') ? getDefaultNetValue('dhcp',$ap_iface,'static routers') : $jsonData['StaticRouters'];
        $netmask = ($jsonData['SubnetMask'] == '' || $jsonData['SubnetMask'] == '0.0.0.0') ? getDefaultNetValue('dhcp',$ap_iface,'subnetmask') : $jsonData['SubnetMask'];
        $ip_address.= (!preg_match('/.*\/\d+/', $ip_address)) ? '/'.mask2cidr($netmask) : null;

        if ($bridgedEnable == 1) {
            $config = array_keys(getDefaultNetOpts('dhcp'));
            $config[] = PHP_EOL.'# RaspAP br0 configuration';
            $config[] = 'interface br0';
            $config[] = 'denyinterfaces eth0 wlan0';
            $config[] = PHP_EOL;
        } elseif ($wifiAPEnable == 1) {
            $config = array_keys(getDefaultNetOpts('dhcp'));
            $config[] = PHP_EOL.'# RaspAP uap0 configuration';
            $config[] = 'interface uap0';
            $config[] = 'static ip_address='.$ip_address;
            $config[] = 'nohook wpa_supplicant';
            $config[] = PHP_EOL;
        } else {
            // Default wlan0 config
            $def_ip = array();
            $config = [ '# RaspAP wlan0 configuration' ];
            $config[] = 'interface wlan0';
            $config[] = 'static ip_address='.$ip_address;
            $config[] = 'static routers='.$routers;
            $config[] = 'static domain_name_server='.$domain_name_server;
            if (! is_null($jsonData['Metric'])) { $config[] = 'metric '.$jsonData['Metric']; }
        }
        $dhcp_cfg = file_get_contents(RASPI_DHCPCD_CONFIG);
        if ($bridgedEnable == 1 || $wifiAPEnable == 1) {
            $dhcp_cfg = join(PHP_EOL, $config);
            $status->addMessage('DHCP configuration for '.$ap_iface.' enabled.', 'success');
        } elseif (!preg_match('/^interface\s'.$ap_iface.'$/m', $dhcp_cfg)) {
            $config[] = PHP_EOL;
            $config= join(PHP_EOL, $config);
            $dhcp_cfg = removeDHCPIface($dhcp_cfg,'br0');
            $dhcp_cfg = removeDHCPIface($dhcp_cfg,'uap0');
            $dhcp_cfg .= $config;
            $status->addMessage('DHCP configuration for '.$ap_iface.' added.', 'success');
        } else {
            $config = join(PHP_EOL, $config);
            $dhcp_cfg = removeDHCPIface($dhcp_cfg,'br0');
            $dhcp_cfg = removeDHCPIface($dhcp_cfg,'uap0');
            $dhcp_cfg = preg_replace('/^#\sRaspAP\s'.$ap_iface.'\s.*?(?=\s*^\s*$)/ms', $config, $dhcp_cfg, 1);
            $status->addMessage('DHCP configuration for '.$ap_iface.' updated.', 'success');
        }
        file_put_contents("/tmp/dhcpddata", $dhcp_cfg);
        system('sudo cp /tmp/dhcpddata '.RASPI_DHCPCD_CONFIG, $return);

        if ($return == 0) {
            $status->addMessage('Wifi Hotspot settings saved', 'success');
        } else {
            $status->addMessage('Unable to save wifi hotspot settings', 'danger');
        }
    } else {
        $status->addMessage('Unable to save wifi hotspot settings', 'danger');
        return false;
    }
    return true;
}

/**
 * Updates a hostapd configuration
 *
 * @return boolean $result
 */
function updateHostapdConfig($ignore_broadcast_ssid,$wifiAPEnable,$bridgedEnable)
{
    // Fixed values
    $country_code = $_POST['country_code'];
    $config = 'driver=nl80211'.PHP_EOL;
    $config.= 'ctrl_interface='.RASPI_HOSTAPD_CTRL_INTERFACE.PHP_EOL;
    $config.= 'ctrl_interface_group=0'.PHP_EOL;
    $config.= 'auth_algs=1'.PHP_EOL;
    $config.= 'wpa_key_mgmt=WPA-PSK'.PHP_EOL;
    if (isset($_POST['beaconintervalEnable'])) {
        $config.= 'beacon_int='.$_POST['beacon_interval'].PHP_EOL;
    }
    if (isset($_POST['disassoc_low_ackEnable'])) {
        $config.= 'disassoc_low_ack=0'.PHP_EOL;
    }
    $config.= 'ssid='.$_POST['ssid'].PHP_EOL;
    $config.= 'channel='.$_POST['channel'].PHP_EOL;
    if ($_POST['hw_mode'] === 'n') {
        $config.= 'hw_mode=g'.PHP_EOL;
        $config.= 'ieee80211n=1'.PHP_EOL;
        // Enable basic Quality of service
        $config.= 'wmm_enabled=1'.PHP_EOL;
    } elseif ($_POST['hw_mode'] === 'ac') {
        $config.= 'hw_mode=a'.PHP_EOL.PHP_EOL;
        $config.= '# N'.PHP_EOL;
        $config.= 'ieee80211n=1'.PHP_EOL;
        $config.= 'require_ht=1'.PHP_EOL;
        $config.= 'ht_capab=[MAX-AMSDU-3839][HT40+][SHORT-GI-20][SHORT-GI-40][DSSS_CCK-40]'.PHP_EOL.PHP_EOL;
        $config.= '# AC'.PHP_EOL;
        $config.= 'ieee80211ac=1'.PHP_EOL;
        $config.= 'require_vht=1'.PHP_EOL;
        $config.= 'ieee80211d=0'.PHP_EOL;
        $config.= 'ieee80211h=0'.PHP_EOL;
        $config.= 'vht_capab=[MAX-AMSDU-3839][SHORT-GI-80]'.PHP_EOL;
        $config.= 'vht_oper_chwidth=1'.PHP_EOL;
        $config.= 'vht_oper_centr_freq_seg0_idx=42'.PHP_EOL.PHP_EOL;
    } elseif ($_POST['hw_mode'] === 'w') {
        $config.= 'ieee80211w=2'.PHP_EOL;
        $config.= 'wpa_key_mgmt=WPA-EAP-SHA256'.PHP_EOL;
    } else {
        $config.= 'hw_mode='.$_POST['hw_mode'].PHP_EOL;
        $config.= 'ieee80211n=0'.PHP_EOL;
    }
    if ($_POST['wpa'] !== 'none') {
        $config.= 'wpa_passphrase='.$_POST['wpa_passphrase'].PHP_EOL;
    }
    if ($wifiAPEnable == 1) {
        $config.= 'interface=uap0'.PHP_EOL;
    } elseif ($bridgedEnable == 1) {
        $config.='interface='.$_POST['interface'].PHP_EOL;
        $config.= 'bridge=br0'.PHP_EOL;
    } else {
        $config.= 'interface='.$_SESSION['ap_interface'].PHP_EOL;
    }
    $config.= 'wpa='.$_POST['wpa'].PHP_EOL;
    $config.= 'wpa_pairwise='.$_POST['wpa_pairwise'].PHP_EOL;
    $config.= 'country_code='.$_POST['country_code'].PHP_EOL;
    $config.= 'ignore_broadcast_ssid='.$ignore_broadcast_ssid.PHP_EOL;
    if (isset($_POST['max_num_sta'])) {
        $config.= 'max_num_sta='.$_POST['max_num_sta'].PHP_EOL;
    }
    file_put_contents("/tmp/hostapddata", $config);
    system("sudo cp /tmp/hostapddata " . RASPI_HOSTAPD_CONFIG, $result);
    return $result;
}

