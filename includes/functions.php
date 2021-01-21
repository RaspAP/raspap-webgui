<?php
/* Functions for Networking */

/**
 * Get a human readable data size string from a number of bytes.
 *
 * @param  long $numbytes  The number of bytes.
 * @param  int  $precision The number of numbers to round to after the dot/comma.
 * @return string Data size in units: PB, TB, GB, MB or KB otherwise an empty string.
 */
function getHumanReadableDatasize($numbytes, $precision = 2)
{
    $humanDatasize = '';
    $kib = 1024;
    $mib = $kib * 1024;
    $gib = $mib * 1024;
    $tib = $gib * 1024;
    $pib = $tib * 1024;
    if ($numbytes >= $pib) {
        $humanDatasize = ' ('.round($numbytes / $pib, $precision).' PB)';
    } elseif ($numbytes >= $tib) {
        $humanDatasize = ' ('.round($numbytes / $tib, $precision).' TB)';
    } elseif ($numbytes >= $gib) {
        $humanDatasize = ' ('.round($numbytes / $gib, $precision).' GB)';
    } elseif ($numbytes >= $mib) {
        $humanDatasize = ' ('.round($numbytes / $mib, $precision).' MB)';
    } elseif ($numbytes >= $kib) {
        $humanDatasize = ' ('.round($numbytes / $kib, $precision).' KB)';
    }

    return $humanDatasize;
}

/**
 * Converts a netmask to CIDR notation string
 *
 * @param string $mask
 * @return string
 */
function mask2cidr($mask)
{
    $long = ip2long($mask);
    $base = ip2long('255.255.255.255');
    return 32-log(($long ^ $base)+1, 2);
}

/**
 * Converts a CIDR notation string to a netmask
 *
 * @param string $cidr
 * @return string
 */
function cidr2mask($cidr)
{
    $ta = substr ($cidr, strpos ($cidr, '/') + 1) * 1;
    $netmask = str_split (str_pad (str_pad ('', $ta, '1'), 32, '0'), 8);
    foreach ($netmask as &$element)
      $element = bindec ($element);
    return join ('.', $netmask);
}

/**
 * Removes a dhcp configuration block for the specified interface
 *
 * @param string $iface
 * @param object $status
 * @return boolean $result
 */
function removeDHCPConfig($iface,$status)
{
    $dhcp_cfg = file_get_contents(RASPI_DHCPCD_CONFIG);
    $dhcp_cfg = preg_replace('/^#\sRaspAP\s'.$iface.'\s.*?(?=\s*^\s*$)([\s]+)/ms', '', $dhcp_cfg, 1);
    file_put_contents("/tmp/dhcpddata", $dhcp_cfg);
    system('sudo cp /tmp/dhcpddata '.RASPI_DHCPCD_CONFIG, $result);
    if ($result == 0) {
        $status->addMessage('DHCP configuration for '.$iface.'  removed.', 'success');
    } else {
        $status->addMessage('Failed to remove DHCP configuration for '.$iface.'.', 'danger');
        return $result;
    }
}

/**
 * Removes a dhcp configuration block for the specified interface
 *
 * @param string $dhcp_cfg
 * @param string $iface
 * @return string $dhcp_cfg
 */
function removeDHCPIface($dhcp_cfg,$iface)
{
    $dhcp_cfg = preg_replace('/^#\sRaspAP\s'.$iface.'\s.*?(?=\s*^\s*$)([\s]+)/ms', '', $dhcp_cfg, 1);
    return $dhcp_cfg;
}

/**
 * Removes a dnsmasq configuration block for the specified interface
 *
 * @param string $iface
 * @param object $status
 * @return boolean $result
 */
function removeDnsmasqConfig($iface,$status)
{
    system('sudo rm '.RASPI_DNSMASQ_PREFIX.$iface.'.conf', $result);
    if ($result == 0) {
        $status->addMessage('Dnsmasq configuration for '.$iface.' removed.', 'success');
    } else {
        $status->addMessage('Failed to remove dnsmasq configuration for '.$iface.'.', 'danger');
    }
    return $result;
}

/**
 * Scans dnsmasq configuration dir for the specified interface
 * Non-matching configs are removed, optional adblock.conf is protected
 *
 * @param string $dir_conf
 * @param string $interface
 * @param object $status
 */
function scanConfigDir($dir_conf,$interface,$status)
{
    $syscnf = preg_grep('~\.(conf)$~', scandir($dir_conf));
    foreach ($syscnf as $cnf) {
        if ($cnf !== '090_adblock.conf' && !preg_match('/.*_'.$interface.'.conf/', $cnf)) {
            system('sudo rm /etc/dnsmasq.d/'.$cnf, $result);
        }
    }
    return $status;
}

/**
 * Returns a default (fallback) value for the selected service, interface & setting
 * from /etc/raspap/networking/defaults.json
 *
 * @param string $svc
 * @param string $iface
 * @return string $value
 */
function getDefaultNetValue($svc,$iface,$key)
{
    $json = json_decode(file_get_contents(RASPI_CONFIG_NETWORK), true);
    if ($json === null) {
        return false;
    } else {
        return $json[$svc][$iface][$key][0];
    }
}

/**
 * Returns default options for the specified service
 *
 * @param string $svc
 * @return object $json
 */
function getDefaultNetOpts($svc)
{
    $json = json_decode(file_get_contents(RASPI_CONFIG_NETWORK), true);
    if ($json === null) {
        return false;
    } else {
        return $json[$svc]['options'];
    }
}

/* Functions to write ini files */

/**
 * Writes a configuration to an .ini file
 *
 * @param array $array
 * @param string $file
 * @return boolean
 */
function write_php_ini($array, $file)
{
    $res = array();
    foreach ($array as $key => $val) {
        if (is_array($val)) {
            $res[] = "[$key]";
            foreach ($val as $skey => $sval) {
                $res[] = "$skey = $sval";
            }
        } else {
            $res[] = "$key = $val";
        }
    }
    if (safefilerewrite($file, implode(PHP_EOL, $res))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Writes to a file without conflicts
 *
 * @param string $fileName
 * @param string $dataToSave
 * @return boolean
 */
function safefilerewrite($fileName, $dataToSave)
{
    if ($fp = fopen($fileName, 'w')) {
        $startTime = microtime(true);
        do {
            $canWrite = flock($fp, LOCK_EX);
            // If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
            if (!$canWrite) {
                usleep(round(rand(0, 100)*1000));
            }
        } while ((!$canWrite)and((microtime(true)-$startTime) < 5));

        //file was locked so now we can store information
        if ($canWrite) {
            fwrite($fp, $dataToSave.PHP_EOL);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
        return true;
    } else {
        return false;
    }
}

/**
 * Saves a CSRF token in the session
 */
function ensureCSRFSessionToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * Add CSRF Token to form
 */
function CSRFTokenFieldTag()
{
    $token = htmlspecialchars($_SESSION['csrf_token']);
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Retuns a CSRF meta tag (for use with xhr, for example)
 */
function CSRFMetaTag()
{
    $token = htmlspecialchars($_SESSION['csrf_token']);
    return '<meta name="csrf_token" content="' . $token . '">';
}

/**
 * Validate CSRF Token
 */
function CSRFValidate()
{
    $post_token   = $_POST['csrf_token'];
    $header_token = $_SERVER['HTTP_X_CSRF_TOKEN'];

    if (empty($post_token) && empty($header_token)) {
        return false;
    }

    $request_token = $post_token;
    if (empty($post_token)) {
        $request_token = $header_token;
    }

    if (hash_equals($_SESSION['csrf_token'], $request_token)) {
        return true;
    } else {
        error_log('CSRF violation');
        return false;
    }
}

/**
 * Should the request be CSRF-validated?
 */
function csrfValidateRequest()
{
    $request_method = strtolower($_SERVER['REQUEST_METHOD']);
    return in_array($request_method, [ "post", "put", "patch", "delete" ]);
}

/**
 * Handle invalid CSRF
 */
function handleInvalidCSRFToken()
{
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: text/plain');
    echo 'Invalid CSRF token';
    exit;
}

/**
 * Test whether array is associative
 */
function isAssoc($arr)
{
    return array_keys($arr) !== range(0, count($arr) - 1);
}

/**
 * Display a selector field for a form. Arguments are:
 *
 * @param string $name:     Field name
 * @param array  $options:  Array of options
 * @param string $selected: Selected option (optional)
 * @param string $id:       $options is an associative array this should be the key
 * @param string $event:    onChange event (optional)
 * @param string $disabled  (optional)
 */
function SelectorOptions($name, $options, $selected = null, $id = null, $event = null, $disabled = null)
{
    echo '<select class="form-control" name="'.htmlspecialchars($name, ENT_QUOTES).'"';
    if (isset($id)) {
        echo ' id="' . htmlspecialchars($id, ENT_QUOTES) .'"';
    }
    if (isset($event)) {
        echo ' onChange="' . htmlspecialchars($event, ENT_QUOTES).'()"';
    }
    echo '>' , PHP_EOL;
    foreach ($options as $opt => $label) {
        $select = '';
        $key = isAssoc($options) ? $opt : $label;
        if ($key == $selected) {
            $select = ' selected="selected"';
        }
        if ($key == $disabled) {
            $disabled = ' disabled';
        }
        echo '<option value="'.htmlspecialchars($key, ENT_QUOTES).'"'.$select.$disabled.'>'.
            htmlspecialchars($label, ENT_QUOTES).'</option>' , PHP_EOL;
    }

    echo '</select>' , PHP_EOL;
}

/**
 *
 * @param  string $input
 * @param  string $string
 * @param  int    $offset
 * @param  string $separator
 * @return $string
 */
function GetDistString($input, $string, $offset, $separator)
{
    $string = substr($input, strpos($input, $string)+$offset, strpos(substr($input, strpos($input, $string)+$offset), $separator));
    return $string;
}

/**
 *
 * @param  array $arrConfig
 * @return $config
 */
function ParseConfig($arrConfig)
{
    $config = array();
    foreach ($arrConfig as $line) {
        $line = trim($line);
        if ($line == "" || $line[0] == "#") {
            continue;
        }

        list($option, $value) = array_map("trim", explode("=", $line, 2));

        if (empty($config[$option])) {
            $config[$option] = $value ?: true;
        } else {
            if (!is_array($config[$option])) {
                $config[$option] = [ $config[$option] ];
            }
            $config[$option][] = $value;
        }
    }
    return $config;
}

/**
 * Fetches DHCP configuration for an interface, returned as JSON data
 *
 * @param  string $interface
 * @return json $jsonData
 */
function getNetConfig($interface)
{
    $URI = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'] .'/ajax/networking/get_netcfg.php?iface='.$interface;
    $jsonData = file_get_contents($URI);
    return $jsonData;
}

/**
 *
 * @param  string $freq
 * @return $channel
 */
function ConvertToChannel($freq)
{
    if ($freq >= 2412 && $freq <= 2484) {
        $channel = ($freq - 2407)/5;
    } elseif ($freq >= 4915 && $freq <= 4980) {
        $channel = ($freq - 4910)/5 + 182;
    } elseif ($freq >= 5035 && $freq <= 5865) {
        $channel = ($freq - 5030)/5 + 6;
    } else {
        $channel = -1;
    }
    if ($channel >= 1 && $channel <= 196) {
        return $channel;
    } else {
        return 'Invalid Channel';
    }
}

/**
 * Converts WPA security string to readable format
 *
 * @param  string $security
 * @return string
 */
function ConvertToSecurity($security)
{
    $options = array();
    preg_match_all('/\[([^\]]+)\]/s', $security, $matches);
    foreach ($matches[1] as $match) {
        if (preg_match('/^(WPA\d?)/', $match, $protocol_match)) {
            $protocol = $protocol_match[1];
            $matchArr = explode('-', $match);
            if (count($matchArr) > 2) {
                $options[] = htmlspecialchars($protocol . ' ('. $matchArr[2] .')', ENT_QUOTES);
            } else {
                $options[] = htmlspecialchars($protocol, ENT_QUOTES);
            }
        }
    }

    if (count($options) === 0) {
        // This could also be WEP but wpa_supplicant doesn't have a way to determine
        // this.
        // And you shouldn't be using WEP these days anyway.
        return 'Open';
    } else {
        return implode(' / ', $options);
    }
}

/**
 * Renders a simple PHP template
 */
function renderTemplate($name, $__template_data = [])
{
    $file = realpath(dirname(__FILE__) . "/../templates/$name.php");
    if (!file_exists($file)) {
        return "template $name ($file) not found";
    }

    if (is_array($__template_data)) {
        extract($__template_data);
    }

    ob_start();
    include $file;
    return ob_get_clean();
}

function expandCacheKey($key)
{
    return RASPI_CACHE_PATH . "/" . $key;
}

function hasCache($key)
{
    $cacheKey = expandCacheKey($key);
    return file_exists($cacheKey);
}

function readCache($key)
{
    $cacheKey = expandCacheKey($key);
    if (!file_exists($cacheKey)) {
        return null;
    }
    return file_get_contents($cacheKey);
}

function writeCache($key, $data)
{
    mkdir(RASPI_CACHE_PATH, 0777, true);
    $cacheKey = expandCacheKey($key);
    file_put_contents($cacheKey, $data);
}

function deleteCache($key)
{
    if (hasCache($key)) {
        $cacheKey = expandCacheKey($key);
        unlink($cacheKey);
    }
}

function cache($key, $callback)
{
    if (hasCache($key)) {
        return readCache($key);
    } else {
        $data = $callback();
        writeCache($key, $data);
        return $data;
    }
}

// insspired by
// http://markushedlund.com/dev/php-escapeshellarg-with-unicodeutf-8-support
function mb_escapeshellarg($arg)
{
    $isWindows = strtolower(substr(PHP_OS, 0, 3)) === 'win';
    if ($isWindows) {
        $escaped_arg = str_replace(array('"', '%'), '', $arg);
    } else {
        $escaped_arg = str_replace("'", "'\\''", $arg);
    }
    return "\"$escaped_arg\"";
}

function dnsServers()
{
    $data = json_decode(file_get_contents("./config/dns-servers.json"));
    return (array) $data;
}

function blocklistProviders()
{
    $data = json_decode(file_get_contents("./config/blocklists.json"));
    return (array) $data;
}

function optionsForSelect($options)
{
    $html = "";
    foreach ($options as $key => $value) {
        // optgroup
        if (is_array($value)) {
            $html .= "<optgroup label=\"$key\">";
            $html .= optionsForSelect($value);
            $html .= "</optgroup>";
        }
        // option
        else {
            $key = is_int($key) ? $value : $key;
            $html .= "<option value=\"$value\">$key</option>";
        }
    }
    return $html;
}

function blocklistUpdated($file)
{
    $blocklist = RASPI_CONFIG.'/adblock/'.$file;
    if (file_exists($blocklist)) {
        $lastModified = date ("F d Y H:i:s.", filemtime($blocklist));
        $lastModified = formatDateAgo($lastModified);
        return $lastModified;
    } else {
        return 'Never';
    }
}

function formatDateAgo($datetime, $full = false)
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

function getThemeOpt()
{
    if (!isset($_COOKIE['theme'])) {
        $theme = "custom.php";
    } else {
        $theme = $_COOKIE['theme'];
    }
    return 'app/css/'.htmlspecialchars($theme, ENT_QUOTES);
}

function getColorOpt()
{
    if (!isset($_COOKIE['color'])) {
        $color = "#d8224c";
    } else {
        $color = $_COOKIE['color'];
    }
    return $color;
}
function getSidebarState()
{
    if ($_COOKIE['sidebarToggled'] == 'true' ) {
        return"toggled";
    }
}

// Returns bridged AP mode status
function getBridgedState()
{
    $arrHostapdConf = parse_ini_file(RASPI_CONFIG.'/hostapd.ini');
    // defaults to false
    return  $arrHostapdConf['BridgedEnable'];
}

// Validates a host or FQDN
function validate_host($host) {
  return preg_match('/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i', $host);
}

