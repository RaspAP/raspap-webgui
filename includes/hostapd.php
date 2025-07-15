<?php

require_once 'includes/wifi_functions.php';
require_once 'includes/config.php';

getWifiInterface();

/**
 * Initialize hostapd values, display interface
 *
 */
function DisplayHostAPDConfig()
{
    $status = new \RaspAP\Messages\StatusMessage;
    $system = new \RaspAP\System\Sysinfo;
    $operatingSystem = $system->operatingSystem();
    $arrConfig = array();
    $arr80211Standard = [
        'a' => '802.11a - 5 GHz',
        'b' => '802.11b - 2.4 GHz',
        'g' => '802.11g - 2.4 GHz',
        'n' => '802.11n - 2.4/5 GHz',
        'ac' => '802.11ac - 5 GHz'
    ];
    $languageCode = strtok($_SESSION['locale'], '_');
    $countryCodes = getCountryCodes($languageCode);

    $arrSecurity = array(1 => 'WPA', 2 => 'WPA2', 3 => _("WPA and WPA2"));
    $arrSecurity += [4 => _("WPA2 and WPA3-Personal (transitional mode)")];
    $arrSecurity += [5 => 'WPA3-Personal (required)'];
    $arrSecurity += ['none' => _("None")];
    $arrEncType = array('TKIP' => 'TKIP', 'CCMP' => 'CCMP', 'TKIP CCMP' => 'TKIP+CCMP');
    $arr80211w = array(3 => _("Disabled"), 1 => _("Enabled (for supported clients)"), 2 => _("Required (for supported clients)"));
    $arrTxPower = getDefaultNetOpts('txpower','dbm');
    $managedModeEnabled = false;
    exec("ip -o link show | awk -F': ' '{print $2}'", $interfaces);
    sort($interfaces);

    $reg_domain = shell_exec("iw reg get | grep -o 'country [A-Z]\{2\}' | awk 'NR==1{print $2}'");
    $cmd = "iw dev ".escapeshellarg($_SESSION['ap_interface'])." info | awk '$1==\"txpower\" {print $2}'";
    exec($cmd, $txpower);
    $txpower = intval($txpower[0]);

    if (isset($_POST['interface'])) {
        $interface = escapeshellarg($_POST['interface']);
    }

    if (!RASPI_MONITOR_ENABLED) {
        if (isset($_POST['SaveHostAPDSettings'])) {
            SaveHostAPDConfig($arrSecurity, $arrEncType, $arr80211Standard, $interfaces, $reg_domain, $status);
        }
    }

    $arrHostapdConf = [];
    $hostapdIni = RASPI_CONFIG . '/hostapd.ini';
	if (file_exists($hostapdIni)) {
        $arrHostapdConf = parse_ini_file($hostapdIni);
    }

    if (!RASPI_MONITOR_ENABLED) {
         if (isset($_POST['StartHotspot']) || isset($_POST['RestartHotspot'])) {
            $status->addMessage('Attempting to start hotspot', 'info');
            if ($arrHostapdConf['BridgedEnable'] == 1) {
                exec('sudo '.RASPI_CONFIG.'/hostapd/servicestart.sh --interface br0 --seconds 1', $return);
            } elseif ($arrHostapdConf['WifiAPEnable'] == 1) {
                exec('sudo '.RASPI_CONFIG.'/hostapd/servicestart.sh --interface uap0 --seconds 1', $return);
            } else {
                // systemctl expects a unit name like raspap-network-activity@wlan0.service
                $iface_nonescaped = $_POST['interface'];
                if (preg_match('/^[a-zA-Z0-9_-]+$/', $iface_nonescaped)) { // validate interface name
                    exec('sudo '.RASPI_CONFIG.'/hostapd/servicestart.sh --interface ' .$iface_nonescaped. ' --seconds 1', $return);
                } else {
                    throw new \Exception('Invalid network interface');
                }
            }
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        } elseif (isset($_POST['StopHotspot'])) {
            $status->addMessage('Attempting to stop hotspot', 'info');
            exec('sudo /bin/systemctl stop hostapd.service', $return);
            exec('sudo systemctl stop "raspap-network-activity@*.service"');
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        }
    }
    exec('cat '. RASPI_HOSTAPD_CONFIG, $hostapdconfig);
    if (isset($_SESSION['wifi_client_interface'])) {
        exec('iwgetid '.escapeshellarg($_SESSION['wifi_client_interface']). ' -r', $wifiNetworkID);
        if (!empty($wifiNetworkID[0])) {
            $managedModeEnabled = true;
        }
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
    } else {
        $arrConfig['disassoc_low_ack_bool'] = 0;
    }
    // assign country_code from iw reg if not set in config
    if (empty($arrConfig['country_code']) && isset($country_code[0])) {
        $arrConfig['country_code'] = $country_code[0];
    }
    // set txpower with iw if value is non-default ('auto')
    if (isset($_POST['txpower'])) {
        if ($_POST['txpower'] != 'auto') {
            $txpower = intval($_POST['txpower']);
            $sdBm = $txpower * 100;
            exec('sudo /sbin/iw dev '.$interface.' set txpower fixed '.$sdBm, $return);
            $status->addMessage('Setting transmit power to '.$_POST['txpower'].' dBm.', 'success');
            $txpower = $_POST['txpower'];
        } elseif ($_POST['txpower'] == 'auto') {
            exec('sudo /sbin/iw dev '.$interface.' set txpower auto', $return);
            $status->addMessage('Setting transmit power to '.$_POST['txpower'].'.', 'success');
            $txpower = $_POST['txpower'];
        }
    } 
    // map wpa_key_mgmt to security types
    if ($arrConfig['wpa_key_mgmt'] == 'WPA-PSK WPA-PSK-SHA256 SAE') {
        $arrConfig['wpa'] = 4;
    } elseif ($arrConfig['wpa_key_mgmt'] == 'SAE') {
        $arrConfig['wpa'] = 5;
    }

    $selectedHwMode = $arrConfig['hw_mode'];
    if (isset($arrConfig['ieee80211n'])) {
        if (strval($arrConfig['ieee80211n']) === '1') {
            $selectedHwMode = 'n';
        }
    }
    if (isset($arrConfig['ieee80211ac'])) {
        if (strval($arrConfig['ieee80211ac']) === '1') {
            $selectedHwMode = 'ac';
        }
    }
    if (isset($arrConfig['ieee80211w'])) {
        if (strval($arrConfig['ieee80211w']) === '2') {
            $selectedHwMode = 'w';
        }
    }

    $arrConfig['ignore_broadcast_ssid'] ??= 0;
    $arrConfig['max_num_sta'] ??= 0;
    $arrConfig['wep_default_key'] ??= 0;
    exec('sudo /bin/chmod o+r '.RASPI_HOSTAPD_LOG);
    $logdata = getLogLimited(RASPI_HOSTAPD_LOG);

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
            "arr80211w",
            "arrTxPower",
            "txpower",
            "arrHostapdConf",
            "operatingSystem",
            "selectedHwMode",
            "countryCodes",
            "logdata"
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
 * @param string $reg_domain
 * @param object $status
 * @return boolean
 */
function SaveHostAPDConfig($wpa_array, $enc_types, $modes, $interfaces, $reg_domain, $status)
{
    // It should not be possible to send bad data for these fields. 
    // If wpa fields are absent, return false and log securely.
    if (!(array_key_exists($_POST['wpa'], $wpa_array) 
        && array_key_exists($_POST['wpa_pairwise'], $enc_types) 
        && array_key_exists($_POST['hw_mode'], $modes))
    ) {
        $err  = "Attempting to set hostapd config with wpa='".escapeshellarg($_POST['wpa']);
        $err .= "', wpa_pairwise='".$escapeshellarg(_POST['wpa_pairwise']);
        $err .= "and hw_mode='".$escapeshellarg(_POST['hw_mode'])."'";
        error_log($err);
        return false;
    }
    // Validate input
    $good_input = true;

    if (!filter_var($_POST['channel'], FILTER_VALIDATE_INT)) {
        $status->addMessage('Attempting to set channel to invalid number.', 'danger');
        $good_input = false;
    }
    if (intval($_POST['channel']) < 1 || intval($_POST['channel']) > RASPI_5GHZ_CHANNEL_MAX) {
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
    // Check for WiFi repeater mode checkbox
    $repeaterEnable = 0;
    if ($bridgedEnable == 0) {  // enable client mode actions when not bridged
        if ($arrHostapdConf['RepeaterEnable'] == 0) {
            if (isset($_POST['repeaterEnable'])) {
                $repeaterEnable = 1;
            }
        } else {
            if (isset($_POST['repeaterEnable'])) {
                $repeaterEnable = 1;
            }
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
    $iface = validateInterface($_POST['interface']) ? $_POST['interface'] : RASPI_WIFI_AP_INTERFACE;

    $ap_iface = $iface; // the hostap AP interface
    $cli_iface = $iface; // the wifi client interface
    $session_iface = $iface; // the interface that the UI needs to monitor for data usage etc.
    if ($wifiAPEnable) { // for AP-STA we monitor the uap0 interface, which is always the ap interface.
        $ap_iface = $session_iface = 'uap0';
    }
    if ($bridgedEnable) { // for bridged mode we monitor the bridge, but keep the selected interface as AP.
        $cli_iface = $session_iface = 'br0';
    }

    // persist user options to /etc/raspap
    $cfg = [];
    $cfg['WifiInterface'] = $ap_iface;
    $cfg['LogEnable'] = $logEnable;
    // Save previous Client mode status when Bridged
    $cfg['WifiAPEnable'] = ($bridgedEnable == 1 ? $arrHostapdConf['WifiAPEnable'] : $wifiAPEnable);
    $cfg['BridgedEnable'] = $bridgedEnable;
    $cfg['RepeaterEnable'] = $repeaterEnable;
    $cfg['WifiManaged'] = $cli_iface;
    write_php_ini($cfg, RASPI_CONFIG.'/hostapd.ini');
    $_SESSION['ap_interface'] = $session_iface;

    // Verify input
    if (empty($_POST['ssid']) || strlen($_POST['ssid']) > 32) {
        $status->addMessage('SSID must be between 1 and 32 characters', 'danger');
        $good_input = false;
    }

    # NB: A pass-phrase is a sequence of between 8 and 63 ASCII-encoded characters (IEEE Std. 802.11i-2004)
    # Each character in the pass-phrase must have an encoding in the range of 32 to 126 (decimal). (IEEE Std. 802.11i-2004, Annex H.4.1)
    if ($_POST['wpa'] !== 'none' && (strlen($_POST['wpa_passphrase']) < 8 || strlen($_POST['wpa_passphrase']) > 63)) {
        $status->addMessage('WPA passphrase must be between 8 and 63 characters', 'danger');
        $good_input = false;
    } elseif (!ctype_print($_POST['wpa_passphrase'])) {
        $status->addMessage('WPA passphrase must be comprised of printable ASCII characters', 'danger');
        $good_input = false;
    }

    $ignore_broadcast_ssid = $_POST['hiddenSSID'] ?? '0';
    if (!ctype_digit($ignore_broadcast_ssid)) {
        $status->addMessage('Parameter hiddenSSID not a number.', 'danger');
        $good_input = false;
    } elseif ((int)$ignore_broadcast_ssid < 0 || (int)$ignore_broadcast_ssid >= 3) {
        $status->addMessage('Parameter hiddenSSID contains an invalid configuration value.', 'danger');
        $good_input = false;
    }
    if (! in_array($_POST['interface'], $interfaces)) {
        $status->addMessage('Unknown interface '.htmlspecialchars($_POST['interface'], ENT_QUOTES), 'danger');
        $good_input = false;
    }
    if (strlen($_POST['country_code']) !== 0 && strlen($_POST['country_code']) != 2) {
        $status->addMessage('Country code must be blank or two characters', 'danger');
        $good_input = false;
    } else {
        $country_code = $_POST['country_code'];
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
        $config = buildHostapdConfig([
            'interface' => $_POST['interface'],
            'ssid' => $_POST['ssid'],
            'channel' => $_POST['channel'],
            'wpa' => $_POST['wpa'],
            '80211w' => $_POST['80211w'] ?? 0,
            'wpa_passphrase' => $_POST['wpa_passphrase'],
            'wpa_pairwise' => $_POST['wpa_pairwise'],
            'hw_mode' => $_POST['hw_mode'],
            'country_code' => $_POST['country_code'],
            'hiddenSSID' => $_POST['hiddenSSID'],
            'max_num_sta' => $_POST['max_num_sta'] ?? null,
            'beacon_interval' => $_POST['beacon_interval'] ?? null,
            'disassoc_low_ack' => $_POST['disassoc_low_ackEnable'] ?? null,
            'bridge' => $bridgedEnable ? 'br0' : null
        ]);

        file_put_contents('/tmp/hostapddata', $config);
        if ($dualAPEnable) {
            system("sudo cp /tmp/hostapddata " . '/etc/hostapd/hostapd-'.$_POST['interface'].'.conf', $result);
        } else {
            system("sudo cp /tmp/hostapddata " . RASPI_HOSTAPD_CONFIG, $result);
        }

        if (trim($country_code) != trim($reg_domain)) {
            $return = iwRegSet($country_code, $status);
        }

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
            $dhcp_range = ($syscfg['dhcp-range'] =='') ? getDefaultNetValue('dnsmasq',$ap_iface,'dhcp-range') : $syscfg['dhcp-range'];
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
        $ip_address = empty($jsonData['StaticIP'])
            ? getDefaultNetValue('dhcp', $ap_iface, 'static ip_address') : $jsonData['StaticIP'];
        $domain_name_server = empty($jsonData['StaticDNS'])
            ? getDefaultNetValue('dhcp', $ap_iface, 'static domain_name_server') : $jsonData['StaticDNS'];
        $routers = empty($jsonData['StaticRouters'])
            ? getDefaultNetValue('dhcp', $ap_iface, 'static routers') : $jsonData['StaticRouters'];
        $netmask = (empty($jsonData['SubnetMask']) || $jsonData['SubnetMask'] === '0.0.0.0')
            ? getDefaultNetValue('dhcp', $ap_iface, 'subnetmask') : $jsonData['SubnetMask'];
        if (isset($ip_address) && !preg_match('/.*\/\d+/', $ip_address)) {
            $ip_address.='/'.mask2cidr($netmask);
        }
        $hasDefaults = !(
            empty($ip_address) ||
            empty($domain_name_server) ||
            empty($routers) ||
            empty($netmask) ||
            $netmask === '0.0.0.0'
        );
        if (!$hasDefaults) {
            $status->addMessage(sprintf(_('Interface %s has no default settings.'), $ap_iface), 'warning');
            $status->addMessage(('Configure settings in <strong>DHCP Server</strong> before starting AP.'), 'warning');
        }
        if ($bridgedEnable == 1) {
            $config = array_keys(getDefaultNetOpts('dhcp','options'));
            $config[] = PHP_EOL.'# RaspAP br0 configuration';
            $config[] = 'denyinterfaces eth0 wlan0';
            $config[] = 'interface br0';
            $config[] = PHP_EOL;
        } elseif ($repeaterEnable == 1) {
            $config = [ '# RaspAP '.$ap_iface.' configuration' ];
            $config[] = 'interface '.$ap_iface;
            $config[] = 'static ip_address='.$ip_address;
            $config[] = 'static routers='.$routers;
            $config[] = 'static domain_name_server='.$domain_name_server;
            $client_metric = getIfaceMetric($_SESSION['wifi_client_interface']);
            if (is_int($client_metric)) {
                $ap_metric = (int)$client_metric + 1;
                $config[] = 'metric '.$ap_metric;
            } else {
                $status->addMessage('Unable to obtain metric value for client interface. Repeater mode inactive.', 'warning');
                $repeaterEnable = false;
            }
        } elseif ($wifiAPEnable == 1) {
            $config = array_keys(getDefaultNetOpts('dhcp','options'));
            $config[] = PHP_EOL.'# RaspAP uap0 configuration';
            $config[] = 'interface uap0';
            $config[] = 'static ip_address='.$ip_address;
            $config[] = 'nohook wpa_supplicant';
            $config[] = PHP_EOL;
        } else {
            $config = updateDhcpcdConfig($ap_iface, $jsonData, $ip_address, $routers, $domain_name_server);
        }
        $dhcp_cfg = file_get_contents(RASPI_DHCPCD_CONFIG);

        if (preg_match('/wlan[3-9]\d*|wlan[1-9]\d+/', $ap_iface)) {
            $skip_dhcp = true;
        } elseif ($bridgedEnable == 1 || $wifiAPEnable == 1) {
            $dhcp_cfg = join(PHP_EOL, $config);
            $status->addMessage(sprintf(_('DHCP configuration for %s enabled.'), $ap_iface), 'success');
        } elseif (!preg_match('/^interface\s'.$ap_iface.'$/m', $dhcp_cfg)) {
            $config[] = PHP_EOL;
            $config= join(PHP_EOL, $config);
            $dhcp_cfg = removeDHCPIface($dhcp_cfg,'br0');
            $dhcp_cfg = removeDHCPIface($dhcp_cfg,'uap0');
            $dhcp_cfg .= $config;
        } else {
            $config = join(PHP_EOL, $config);
            $dhcp_cfg = removeDHCPIface($dhcp_cfg,'br0');
            $dhcp_cfg = removeDHCPIface($dhcp_cfg,'uap0');
            if (!strpos($dhcp_cfg, 'metric')) {
                $dhcp_cfg = preg_replace('/^#\sRaspAP\s'.$ap_iface.'\s.*?(?=(?:\s*^\s*$|\s*nogateway))/ms', $config, $dhcp_cfg, 1);
            } else {
                $metrics = true;
            }
        }
        if ($repeaterEnable && $metrics) {
            $status->addMessage(_('WiFi repeater mode: A metric value is already defined for DHCP.'), 'warning');
        } else if ($repeaterEnable && !$metrics) {
            $status->addMessage(sprintf(_('Metric value configured for the %s interface.'), $ap_iface), 'success');
            $status->addMessage('Restart hotspot to enable WiFi repeater mode.', 'success');
            persistDHCPConfig($dhcp_cfg, $ap_iface, $status);
        } elseif (!$skip_dhcp) {
            persistDHCPConfig($dhcp_cfg, $ap_iface, $status);
        } else {
            $status->addMessage('WiFi hotspot settings saved.', 'success');
        }
    } else {
        $status->addMessage('Unable to save WiFi hotspot settings', 'danger');
        return false;
    }
    return true;
}

/**
 * Persists a DHCP configuration
 *
 * @param string $dhcp_cfg
 * @param string $ap_iface
 * @param object $status
 * @return $status
 */
function persistDHCPConfig($dhcp_cfg, $ap_iface, $status)
{
    file_put_contents("/tmp/dhcpddata", $dhcp_cfg);
    system('sudo cp /tmp/dhcpddata '.RASPI_DHCPCD_CONFIG, $return);
    if ($return == 0) {
        $status->addMessage(sprintf(_('DHCP configuration for %s updated.'), $ap_iface), 'success');
        $status->addMessage('WiFi hotspot settings saved.', 'success');
    } else {
        $status->addMessage('Unable to save WiFi hotspot settings.', 'danger');
    }
    return $status;
}

/**
 * Returns a count of hostapd-<interface>.conf files
 *
 * @return int
 */
function countHostapdConfigs(): int
{
    $configs = glob('/etc/hostapd/hostapd-*.conf');
    return is_array($configs) ? count($configs) : 0;
}

/**
 * Retrieves the metric value for a given interface
 *
 * @param string $iface
 * @return int $metric
 */
function getIfaceMetric($iface)
{
    $metric = shell_exec("ip -o -4 route show dev ".$iface." | awk '/metric/ {print \$NF; exit}'");
    if (isset($metric)) {
        $metric = (int)$metric;
        return $metric;
    } else {
        return false;
    }
}

/**
 * Builds a hostapd configuration string for a given interface
 *
 * @param array $params Associative array of config values
 * @return string
 */
function buildHostapdConfig(array $params): string
{
    $config = [];
    $config[] = 'driver=nl80211';
    $config[] = 'ctrl_interface=' . RASPI_HOSTAPD_CTRL_INTERFACE;
    $config[] = 'ctrl_interface_group=0';
    $config[] = 'auth_algs=1';

    $wpa = $params['wpa'];
    $wpa_key_mgmt = 'WPA-PSK';

    if ($wpa == 4) {
        $config[] = 'ieee80211w=1';
        $wpa_key_mgmt = 'WPA-PSK WPA-PSK-SHA256 SAE';
        $wpa = 2;
    } elseif ($wpa == 5) {
        $config[] = 'ieee80211w=2';
        $wpa_key_mgmt = 'SAE';
        $wpa = 2;
    }

    if ($params['80211w'] == 1) {
        $config[] = 'ieee80211w=1';
        $wpa_key_mgmt = 'WPA-PSK';
    } elseif ($params['80211w'] == 2) {
        $config[] = 'ieee80211w=2';
        $wpa_key_mgmt = 'WPA-PSK-SHA256';
    }

    $config[] = 'wpa_key_mgmt=' . $wpa_key_mgmt;

    if (!empty($params['beacon_interval'])) {
        $config[] = 'beacon_int=' . $params['beacon_interval'];
    }

    if (!empty($params['disassoc_low_ack'])) {
        $config[] = 'disassoc_low_ack=0';
    }

    $config[] = 'ssid=' . $params['ssid'];
    $config[] = 'channel=' . $params['channel'];

    // Choose VHT segment index (fallback only if required)
    $vht_freq_idx = ($params['channel'] < RASPI_5GHZ_CHANNEL_MIN) ? 42 : 155;

    switch ($params['hw_mode']) {
        case 'n':
            $config[] = 'hw_mode=g';
            $config[] = 'ieee80211n=1';
            $config[] = 'wmm_enabled=1';
            break;
        case 'ac':
            $config[] = 'hw_mode=a';
            $config[] = '# N';
            $config[] = 'ieee80211n=1';
            $config[] = 'require_ht=1';
            $config[] = 'ht_capab=[MAX-AMSDU-3839][HT40+][SHORT-GI-20][SHORT-GI-40][DSSS_CCK-40]';
            $config[] = '# AC';
            $config[] = 'ieee80211ac=1';
            $config[] = 'require_vht=1';
            $config[] = 'ieee80211d=0';
            $config[] = 'ieee80211h=0';
            $config[] = 'vht_capab=[MAX-AMSDU-3839][SHORT-GI-80]';
            $config[] = 'vht_oper_chwidth=1';
            $config[] = 'vht_oper_centr_freq_seg0_idx=' . $vht_freq_idx;
            break;
        default:
            $config[] = 'hw_mode=' . $params['hw_mode'];
            $config[] = 'ieee80211n=0';
    }

    if ($params['wpa'] !== 'none') {
        $config[] = 'wpa_passphrase=' . $params['wpa_passphrase'];
    }

    if (!empty($params['bridge'])) {
        $config[] = 'interface=' . $params['interface'];
        $config[] = 'bridge=' . $params['bridge'];
    } else {
        $config[] = 'interface=' . $params['interface'];
    }

    $config[] = 'wpa=' . $wpa;
    $config[] = 'wpa_pairwise=' . $params['wpa_pairwise'];
    $config[] = 'country_code=' . $params['country_code'];
    $config[] = 'ignore_broadcast_ssid=' . $params['hiddenSSID'];
    if (!empty($params['max_num_sta'])) {
        $config[] = 'max_num_sta=' . (int)$params['max_num_sta'];
    }
    
    // Optional additional user config
    $config[] = parseUserHostapdCfg();

    return implode(PHP_EOL, $config) . PHP_EOL;
}

/**
 * Updates the dhcpcd configuration for a given interface, preserving existing settings
 *
 * @param string $ap_iface
 * @param array $jsonData
 * @param string $ip_address
 * @param string $routers
 * @param string $domain_name_server
 * @return array updated configuration
 */
function updateDhcpcdConfig($ap_iface, $jsonData, $ip_address, $routers, $domain_name_server) {
    $dhcp_cfg = file_get_contents(RASPI_DHCPCD_CONFIG);
    $existing_config = [];
    $section_regex = '/^#\sRaspAP\s'.preg_quote($ap_iface, '/').'\s.*?(?=\s*^\s*$)/ms';

    // extract existing interface configuration
    if (preg_match($section_regex, $dhcp_cfg, $matches)) {
        $lines = explode(PHP_EOL, $matches[0]);
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^(interface|static|metric|nogateway|nohook)/', $line)) {
                $existing_config[] = $line;
            }
        }
    }

    // initialize with comment
    $config = [ '# RaspAP '.$ap_iface.' configuration' ];
    $config[] = 'interface '.$ap_iface;
    $static_settings = [
        'static ip_address' => $ip_address,
        'static routers' => $routers,
        'static domain_name_server' => $domain_name_server
    ];

    // merge existing settings with updates
    foreach ($existing_config as $line) {
        $matched = false;
        foreach ($static_settings as $key => $value) {
            if (strpos($line, $key) === 0) {
                $config[] = "$key=$value";
                $matched = true;
                unset($static_settings[$key]);
                break;
            }
        }
        if (!$matched && !preg_match('/^interface/', $line)) {
            $config[] = $line;
        }
    }

    // add any new static settings
    foreach ($static_settings as $key => $value) {
        $config[] = "$key=$value";
    }

    // add metric if provided
    if (!empty($jsonData['Metric']) && !in_array('metric '.$jsonData['Metric'], $config)) {
        $config[] = 'metric '.$jsonData['Metric'];
    }

    return $config;
}

/**
 * Executes iw to set the specified ISO 2-letter country code
 *
 * @param string $country_code
 * @param object $status
 * @return boolean $result
 */
function iwRegSet(string $country_code, $status)
{
    $country_code = escapeshellarg($country_code);
    $result = shell_exec("sudo iw reg set $country_code");
    $status->addMessage(sprintf(_('Setting wireless regulatory domain to %s'), $country_code, 'success'));
    return $result;
}

/**
 * Parses optional /etc/hostapd/hostapd.conf.users file
 *
 * @return string $tmp
 */
function parseUserHostapdCfg()
{
    if (file_exists(RASPI_HOSTAPD_CONFIG . '.users')) {
        exec('cat '. RASPI_HOSTAPD_CONFIG . '.users', $hostapdconfigusers);
        foreach ($hostapdconfigusers as $hostapdconfigusersline) {
            if (strlen($hostapdconfigusersline) === 0) {
                continue;
            }
            if ($hostapdconfigusersline[0] != "#") {
                $arrLine = explode("=", $hostapdconfigusersline);
                $tmp.= $arrLine[0]."=".$arrLine[1].PHP_EOL;;
            }
        }
        return $tmp;
    }
}
