<?php

use RaspAP\Networking\Hotspot\DnsmasqManager;
use RaspAP\Networking\Hotspot\HostapdManager;
use RaspAP\Networking\Hotspot\DhcpcdManager;
use RaspAP\Networking\Hotspot\WiFiManager;
use RaspAP\Messages\StatusMessage;
use RaspAP\System\Sysinfo;

$wifi = new WiFiManager();
$wifi->getWifiInterface();

/**
 * Initialize hostapd values, display interface
 *
 */
function DisplayHostAPDConfig()
{
    $hostapd = new HostapdManager();
    $status = new StatusMessage();
    $system = new Sysinfo();
    $operatingSystem = $system->operatingSystem();

    $arrConfig = array();
    $arr80211Standard = $hostapd->get80211Standards();
    $arrSecurity = $hostapd->getSecurityModes();
    $arrEncType = $hostapd->getEncTypes();
    $arr80211w = $hostapd->get80211wOptions();
    $languageCode = strtok($_SESSION['locale'], '_');
    $countryCodes = getCountryCodes($languageCode);
    $reg_domain = $hostapd->getRegDomain();
    $interfaces = $hostapd->getInterfaces();

    // set defaults
    $arrTxPower = getDefaultNetOpts('txpower','dbm');
    $managedModeEnabled = false;

    if (isset($_POST['interface'])) {
        $interface = $_POST['interface'];
    } else {
        $interface = $_SESSION['ap_interface'];
    }
    $txpower = $hostapd->getTxPower($interface);

    if (!RASPI_MONITOR_ENABLED) {
        if (isset($_POST['SaveHostAPDSettings'])) {
            saveHostAPDConfig($arrSecurity, $arrEncType, $arr80211Standard, $interfaces, $reg_domain, $status);
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
    if (isset($_SESSION['wifi_client_interface'])) {
        exec('iwgetid '.escapeshellarg($_SESSION['wifi_client_interface']). ' -r', $wifiNetworkID);
        if (!empty($wifiNetworkID[0])) {
            $managedModeEnabled = true;
        }
    }

    // process txpower user input 
    if (isset($_POST['txpower'])) {
        if ($_POST['txpower'] != 'auto') {
            $txpower = intval($_POST['txpower']);
            $hostapd->maybeSetTxPower($interface, $txpower, $status);
        } elseif ($_POST['txpower'] == 'auto') {
            $hostapd->maybeSetTxPower($interface, 'auto', $status);
        }
        $txpower = $_POST['txpower'];
    }

    // parse hostapd configuration
    try {
        $arrConfig = $hostapd->getConfig();
    } catch (\RuntimeException $e) {
        error_log('Error: ' . $e->getMessage());
    }

    // assign disassoc_low_ack boolean if value is set
    $arrConfig['disassoc_low_ack_bool'] = isset($arrConfig['disassoc_low_ack']) ? 1 : 0;
    $hostapdstatus = $system->hostapdStatus();
    $serviceStatus = $hostapdstatus[0] == 0 ? "down" : "up";
    
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
            "arrSecurity",
            "arrEncType",
            "arr80211w",
            "arrTxPower",
            "txpower",
            "arrHostapdConf",
            "operatingSystem",
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
function saveHostAPDConfig($wpa_array, $enc_types, $modes, $interfaces, $reg_domain, $status)
{
    $hostapd = new HostapdManager();
    $dnsmasq = new DnsmasqManager();
    $dhcpcd = new DhcpcdManager();
    $dualAPEnable = false;

    $hostapdIniPath = RASPI_CONFIG . '/hostapd.ini';
    $arrHostapdConf = file_exists($hostapdIniPath) ? parse_ini_file($hostapdIniPath) : [];

    // derive mode states
    $states = $hostapd->deriveModeStates($_POST, $arrHostapdConf);

    // determine base interface (validated or fallback)
    $baseIface = validateInterface($_POST['interface']) ? $_POST['interface'] : RASPI_WIFI_AP_INTERFACE;

    // derive interface roles
    [$apIface, $cliIface, $sessionIface] = $hostapd->deriveInterfaces($baseIface, $states);

    // persist hostapd.ini
    $hostapd->persistHostapdIni($states, $apIface, $cliIface, $arrHostapdConf);

    // store session (compatibility)
    $_SESSION['ap_interface'] = $sessionIface;

    // validate config from $_POST
    $validated = $hostapd->validate($_POST, $wpa_array, $enc_types, $modes, $interfaces, $reg_domain, $status);

    if ($validated !== false) {
        try {
            $validated['interface'] = $apIface;
            $validated['bridge']    = $states['BridgedEnable'] ? 'br0' : null;
            $validated['txpower']   = $txpower;
            // build and save configuration
            $config = $hostapd->buildConfig($validated, $status);
            $hostapd->saveConfig($config, $dualAPEnable, $validated['interface']);
            $status->addMessage('WiFi hotspot settings saved.', 'success');
        } catch (\RuntimeException $e) {
            error_log('Error: ' . $e->getMessage());
        }
    } else {
        $status->addMessage('Unable to save WiFi hotspot settings', 'danger');
        return false;
    }
 
    /// TODO: build out DHCP class
    /// finish processing save
        /*
        if (trim($country_code) != trim($reg_domain)) {
            $return = $hostapd->iwRegSet($country_code, $status);
        }

        // Parse dnsmasq config for selected interface 
        try {
            $syscfg = $dnsmasq->getConfig($ap_iface ?? RASPI_WIFI_AP_INTERFACE);
        } catch (\RuntimeException $e) {
            error_log('Error: ' . $e->getMessage());
        }
        // Build and save dsnmasq config
        try {
            $config = $dnsmasq->buildConfig($syscfg, $ap_iface, $wifiAPEnable, $bridgedEnable);
            $dnsmasq->saveConfig($config, $ap_iface);
        } catch (\RuntimeException $e) {
            error_log('Error: ' . $e->getMessage());
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
        */
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

