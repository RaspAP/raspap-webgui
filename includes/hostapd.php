<?php

include_once('includes/status_messages.php');

/**
*
*
*/
function DisplayHostAPDConfig()
{
    $status = new StatusMessages();
    $arrHostapdConf = parse_ini_file('/etc/raspap/hostapd.ini');
    $arrConfig = array();
    $arr80211Standard = [
        'a' => '802.11a - 5 GHz',
        'b' => '802.11b - 2.4 GHz',
        'g' => '802.11g - 2.4 GHz',
        'n' => '802.11n - 2.4 GHz'
    ];
    $arrSecurity = array(1 => 'WPA', 2 => 'WPA2', 3 => 'WPA+WPA2', 'none' => _("None"));
    $arrEncType = array('TKIP' => 'TKIP', 'CCMP' => 'CCMP', 'TKIP CCMP' => 'TKIP+CCMP');
    exec("ip -o link show | awk -F': ' '{print $2}'", $interfaces);

    if (isset($_POST['SaveHostAPDSettings'])) {
        SaveHostAPDConfig($arrSecurity, $arrEncType, $arr80211Standard, $interfaces, $status);
    } elseif (isset($_POST['StartHotspot'])) {
        $status->addMessage('Attempting to start hotspot', 'info');
        if ($arrHostapdConf['WifiAPEnable'] == 1) {
            exec('sudo /etc/raspap/hostapd/servicestart.sh --interface uap0 --seconds 3', $return);
        } else {
            exec('sudo /etc/raspap/hostapd/servicestart.sh --seconds 5', $return);
        }
        foreach ($return as $line) {
            $status->addMessage($line, 'info');
        }
    } elseif (isset($_POST['StopHotspot'])) {
        $status->addMessage('Attempting to stop hotspot', 'info');
        exec('sudo /etc/init.d/hostapd stop', $return);
        foreach ($return as $line) {
            $status->addMessage($line, 'info');
        }
    }

    exec('cat '. RASPI_HOSTAPD_CONFIG, $hostapdconfig);
    exec('pidof hostapd | wc -l', $hostapdstatus);

    $serviceStatus = $hostapdstatus[0] == 0 ? "stopped" : "running";

    foreach ($hostapdconfig as $hostapdconfigline) {
        if (strlen($hostapdconfigline) === 0) {
            continue;
        }

        if ($hostapdconfigline[0] != "#") {
            $arrLine = explode("=", $hostapdconfigline) ;
            $arrConfig[$arrLine[0]]=$arrLine[1];
        }
    };

    echo renderTemplate("hostapd", compact(
        "status",
        "serviceStatus",
        "hostapdstatus",
        "interfaces",
        "arrConfig",
        "arr80211Standard",
        "selectedHwMode",
        "arrSecurity",
        "arrEncType",
        "arrHostapdConf"
    ));
}

function SaveHostAPDConfig($wpa_array, $enc_types, $modes, $interfaces, $status)
{
    // It should not be possible to send bad data for these fields so clearly
    // someone is up to something if they fail. Fail silently.
    if (!(array_key_exists($_POST['wpa'], $wpa_array) &&
      array_key_exists($_POST['wpa_pairwise'], $enc_types) &&
      array_key_exists($_POST['hw_mode'], $modes))) {
        error_log("Attempting to set hostapd config with wpa='".$_POST['wpa']."', wpa_pairwise='".$_POST['wpa_pairwise']."' and hw_mode='".$_POST['hw_mode']."'");  // FIXME: log injection
        return false;
    }

    if (!filter_var($_POST['channel'], FILTER_VALIDATE_INT)) {
        error_log("Attempting to set channel to invalid number.");
        return false;
    }

    if (intval($_POST['channel']) < 1 || intval($_POST['channel']) > 14) {
        error_log("Attempting to set channel to '".$_POST['channel']."'");
        return false;
    }

    $good_input = true;
  
    // Check for WiFi client AP mode checkbox
    $wifiAPEnable = 0;
    if ($arrHostapdConf['WifiAPEnable'] == 0) {
        if (isset($_POST['wifiAPEnable'])) {
            $wifiAPEnable = 1;
        }
    } else {
        if (isset($_POST['wifiAPEnable'])) {
            $wifiAPEnable = 1;
        }
    }

    // Check for Logfile output checkbox
    $logEnable = 0;
    if ($arrHostapdConf['LogEnable'] == 0) {
        if (isset($_POST['logEnable'])) {
            $logEnable = 1;
            exec('sudo /etc/raspap/hostapd/enablelog.sh');
        } else {
            exec('sudo /etc/raspap/hostapd/disablelog.sh');
        }
    } else {
        if (isset($_POST['logEnable'])) {
            $logEnable = 1;
            exec('sudo /etc/raspap/hostapd/enablelog.sh');
        } else {
            exec('sudo /etc/raspap/hostapd/disablelog.sh');
        }
    }
    $cfg = [];
    $cfg['LogEnable'] = $logEnable;
    $cfg['WifiAPEnable'] = $wifiAPEnable;
    $cfg['WifiManaged'] = RASPI_WIFI_CLIENT_INTERFACE;
    write_php_ini($cfg, '/etc/raspap/hostapd.ini');

    // Verify input
    if (empty($_POST['ssid']) || strlen($_POST['ssid']) > 32) {
        // Not sure of all the restrictions of SSID
        $status->addMessage('SSID must be between 1 and 32 characters', 'danger');
        $good_input = false;
    }

    if ($_POST['wpa'] !== 'none' &&
      (strlen($_POST['wpa_passphrase']) < 8 || strlen($_POST['wpa_passphrase']) > 63)) {
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

    $_POST['max_num_sta'] = (int) $_POST['max_num_sta'];
    $_POST['max_num_sta'] = $_POST['max_num_sta'] > 2007 ? 2007 : $_POST['max_num_sta'];
    $_POST['max_num_sta'] = $_POST['max_num_sta'] < 1 ? null : $_POST['max_num_sta'];

    if ($good_input) {
        // Fixed values
        $config = 'driver=nl80211'.PHP_EOL;
        $config.= 'ctrl_interface='.RASPI_HOSTAPD_CTRL_INTERFACE.PHP_EOL;
        $config.= 'ctrl_interface_group=0'.PHP_EOL;
        $config.= 'auth_algs=1'.PHP_EOL;
        $config.= 'wpa_key_mgmt=WPA-PSK'.PHP_EOL;
        $config.= 'beacon_int=100'.PHP_EOL;
        $config.= 'ssid='.$_POST['ssid'].PHP_EOL;
        $config.= 'channel='.$_POST['channel'].PHP_EOL;
        if ($_POST['hw_mode'] === 'n') {
            $config.= 'hw_mode=g'.PHP_EOL;
            $config.= 'ieee80211n=1'.PHP_EOL;
            // Enable basic Quality of service
            $config.= 'wmm_enabled=1'.PHP_EOL;
        } else {
            $config.= 'hw_mode='.$_POST['hw_mode'].PHP_EOL;
            $config.= 'ieee80211n=0'.PHP_EOL;
        }
        $config.= 'wpa_passphrase='.$_POST['wpa_passphrase'].PHP_EOL;
        if ($wifiAPEnable == 1) {
            $config.= 'interface=uap0'.PHP_EOL;
        } else {
            $config.= 'interface='.$_POST['interface'].PHP_EOL;
        }
        $config.= 'wpa='.$_POST['wpa'].PHP_EOL;
        $config.= 'wpa_pairwise='.$_POST['wpa_pairwise'].PHP_EOL;
        $config.= 'country_code='.$_POST['country_code'].PHP_EOL;
        $config.= 'ignore_broadcast_ssid='.$ignore_broadcast_ssid.PHP_EOL;
        if (isset($_POST['max_num_sta'])) {
            $config.= 'max_num_sta='.$_POST['max_num_sta'].PHP_EOL;
        }

        file_put_contents("/tmp/hostapddata", $config);
        system("sudo cp /tmp/hostapddata " . RASPI_HOSTAPD_CONFIG, $return);

        // Set dhcp-range from system config, fallback to default if undefined
        $dhcpConfig = parse_ini_file(RASPI_DNSMASQ_CONFIG, false, INI_SCANNER_RAW);
        $dhcp_range = ($dhcpConfig['dhcp-range'] =='') ? '10.3.141.50,10.3.141.255,255.255.255.0,12h' : $dhcpConfig['dhcp-range'];

        if ($wifiAPEnable == 1) {
            // Enable uap0 configuration in dnsmasq for Wifi client AP mode
            $config = 'interface=lo,uap0               # Enable uap0 interface for wireless client AP mode'.PHP_EOL;
            $config.= 'bind-interfaces                 # Bind to the interfaces'.PHP_EOL;
            $config.= 'server=8.8.8.8                  # Forward DNS requests to Google DNS'.PHP_EOL;
            $config.= 'domain-needed                   # Don\'t forward short names'.PHP_EOL;
            $config.= 'bogus-priv                      # Never forward addresses in the non-routed address spaces'.PHP_EOL;
            $config.= 'dhcp-range=192.168.50.50,192.168.50.150,12h'.PHP_EOL;
        } else {
            // Fallback to system config
            $config = 'domain-needed'.PHP_EOL;
            $config.= 'interface='.$_POST['interface'].PHP_EOL;
            $config.= 'dhcp-range='.$dhcp_range.PHP_EOL;
        }
        file_put_contents("/tmp/dnsmasqdata", $config);
        system('sudo cp /tmp/dnsmasqdata '.RASPI_DNSMASQ_CONFIG, $return);

        // Set dnsmasq values from ini, fallback to default if undefined
        $intConfig = parse_ini_file(RASPI_CONFIG_NETWORKING.'/'.RASPI_WIFI_CLIENT_INTERFACE.'.ini', false, INI_SCANNER_RAW);
        $ip_address = ($intConfig['ip_address'] == '') ? '10.3.141.1/24' : $intConfig['ip_address'];
        $domain_name_server = ($intConfig['domain_name_server'] =='') ? '1.1.1.1 8.8.8.8' : $intConfig['domain_name_server'];
        $routers = ($intConfig['routers'] == '') ? '10.3.141.1' : $intConfig['routers'];

        if ($wifiAPEnable == 1) {
            // Enable uap0 configuration in dhcpcd for Wifi client AP mode
            $config = PHP_EOL.'# RaspAP uap0 configuration'.PHP_EOL;
            $config.= 'interface uap0'.PHP_EOL;
            $config.= 'static ip_address=192.168.50.1/24'.PHP_EOL;
            $config.= 'nohook wpa_supplicant'.PHP_EOL;
        } else {
            // Default config
            $config = '# RaspAP wlan0 configuration'.PHP_EOL;
            $config.= 'hostname'.PHP_EOL;
            $config.= 'clientid'.PHP_EOL;
            $config.= 'persistent'.PHP_EOL;
            $config.= 'option rapid_commit'.PHP_EOL;
            $config.= 'option domain_name_servers, domain_name, domain_search, host_name'.PHP_EOL;
            $config.= 'option classless_static_routes'.PHP_EOL;
            $config.= 'option ntp_servers'.PHP_EOL;
            $config.= 'require dhcp_server_identifier'.PHP_EOL;
            $config.= 'slaac private'.PHP_EOL;
            $config.= 'nohook lookup-hostname'.PHP_EOL;
            $config.= 'interface '.RASPI_WIFI_CLIENT_INTERFACE.PHP_EOL;
            $config.= 'static ip_address='.$ip_address.PHP_EOL;
            $config.= 'static routers='.$routers.PHP_EOL;
            $config.= 'static domain_name_server='.$domain_name_server.PHP_EOL;
        }
        file_put_contents("/tmp/dhcpddata", $config);
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
