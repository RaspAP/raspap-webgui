<?php require_once 'session.php'; ?>
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
    $ipParts = explode('/', $cidr);
    $ip = $ipParts[0];
    $prefixLength = $ipParts[1] ?? null;

    $ipLong = ip2long($ip);
    $netmaskLong = bindec(str_pad(str_repeat('1', $prefixLength), 32, '0'));
    $netmask = long2ip(intval($netmaskLong));

    return $netmask;
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
 * @param string $key
 * @return object $json
 */
function getDefaultNetOpts($svc,$key)
{
    $json = json_decode(file_get_contents(RASPI_CONFIG_NETWORK), true);
    if ($json === null) {
        return false;
    } else {
        return $json[$svc][$key];
    }
}

/**
 * Returns a value for the specified VPN provider
 *
 * @param numeric $id
 * @param string $key
 * @return object $json
 */
function getProviderValue($id, $key)
{
    $obj = json_decode(file_get_contents(RASPI_CONFIG_PROVIDERS), true);
    if (!isset($obj['providers']) || !is_array($obj['providers'])) {
        return false;
    }
    $id--;
    if (!isset($obj['providers'][$id]) || !is_array($obj['providers'][$id])) {
        return false;
    }
    return $obj['providers'][$id][$key] ?? false;
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
 * Prepends data to a file if not exists
 *
 * @param string $filename
 * @param string $dataToSave
 * @return boolean
 */
function file_prepend_data($filename, $dataToSave)
{
    $context = stream_context_create();
    $file = fopen($filename, 'r', 1, $context);
    $file_data = readfile($file);

    if (!preg_match('/^'.$dataToSave.'/', $file_data)) {
        $tmp_file = tempnam(sys_get_temp_dir(), 'php_prepend_');
        file_put_contents($tmp_file, $dataToSave);
        file_put_contents($tmp_file, $file, FILE_APPEND);
        fclose($file);
        unlink($filename);
        rename($tmp_file, $filename);
        return true;
    } else {
        return false;
    }
}

/**
 * Fetches a meta value from a file
 *
 * @param string $filename
 * @param string $pattern
 * @return string
 */
function file_get_meta($filename, $pattern)
{
    if(file_exists($filename)) {
        $context = stream_context_create();
        $file_data = file_get_contents($filename, false, $context);
        preg_match('/^'.$pattern.'/', $file_data, $matched);
        return $matched[1];
    } else {
        return false;
    }
}

/**
 * Callback function for array_filter
 *
 * @param string $var
 * @return filtered value
 */
function filter_comments($var)
{
    return $var[0] != '#';
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
    echo '<select class="form-select" name="'.htmlspecialchars($name, ENT_QUOTES).'"';
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
 * Parses a configuration file
 * Options and values are mapped with "=" characters
 * Optional $wg flag is used for parsing WireGuard .conf files
 * @param  array   $arrConfig
 * @param  boolean $wg
 * @return $config
 */
function ParseConfig($arrConfig, $wg = false)
{
    $config = array();
    foreach ($arrConfig as $line) {
        $line = trim($line);
        if ($line == "" || $line[0] == "#") {
            if ($wg) {
                $config[$option] = null;
                continue;
            } else {
                continue;
            }
        }

        if (strpos($line, "=") !== false) {
            list($option, $value) = array_map("trim", explode("=", $line, 2));
        } else {
            $option = $line;
            $value = "";
        }
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
    $URI = $_SERVER['REQUEST_SCHEME'].'://' .'localhost'. dirname($_SERVER['SCRIPT_NAME']) .'/ajax/networking/get_netcfg.php?iface='.$interface;
    $jsonData = file_get_contents($URI, true);
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
function renderTemplate($name, $__template_data = [], $pluginName = null)
{
    if (is_string($pluginName)) {
        $file = realpath(dirname(__FILE__) . "/../plugins/$pluginName/templates/$name.php");
    } else {
        $file = realpath(dirname(__FILE__) . "/../templates/$name.php");
    }
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
    if (!file_exists(RASPI_CACHE_PATH)) {
        mkdir(RASPI_CACHE_PATH, 0777, true);
        $cacheKey = expandCacheKey($key);
        file_put_contents($cacheKey, $data);
    }
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
        return '"' . str_replace(array('"', '%'), '', $arg) . '"';
    } else {
        return "'" . str_replace("'", "'\\''", $arg) . "'";
    }
}

function safeOutputValue($def, $arr)
{
    if (array_key_exists($def, $arr)) {
        echo htmlspecialchars($arr[$def], ENT_QUOTES);
    }
}

function dnsServers()
{
    $data = json_decode(file_get_contents("./config/dns-servers.json"));
    return (array) $data;
}

function blocklistProviders()
{
    $raw = json_decode(file_get_contents("./config/blocklists.json"), true);
    $result = [];

    foreach ($raw as $group => $entries) {
        $result[$group] = array_keys($entries);
    }
    return $result;
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

function initializeApp()
{
    $_SESSION["theme_url"] = getThemeOpt();
    $_SESSION["bridgedEnabled"] = getBridgedState();
    $_SESSION["providerID"] = getProviderID();
}

function getThemeOpt()
{
    if (!isset($_COOKIE['theme'])) {
        $theme = "custom.php";
        setcookie('theme', $theme);
    } else {
        $theme = $_COOKIE['theme'];
    }
    return 'app/css/'.htmlspecialchars($theme, ENT_QUOTES);
}

function getColorOpt()
{
    if (!isset($_COOKIE['color'])) {
        $color = "#2b8080";
    } else {
        $color = $_COOKIE['color'];
    }

    // Define the regex pattern for valid CSS color formats
    $colorPattern = "/^(" .
        "#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})" . "|" .           // Hex colors (#RGB or #RRGGBB)
        "rgb\(\s*(?:\d{1,3}\s*,\s*){2}\d{1,3}\s*\)" . "|" .     // RGB format
        "rgba\(\s*(?:\d{1,3}\s*,\s*){3}\s*(0|0\.\d+|1)\s*\)" . "|" . // RGBA format
        "[a-zA-Z]+" .                                         // Named colors
    ")$/i";

    // Validate the color
    if (!preg_match($colorPattern, $color)) {
        // Return a default color if validation fails
        $color = "#2b8080";
    }

    return $color;
}

function getBridgedState()
{

	$hostapdIni = RASPI_CONFIG . '/hostapd.ini';
	if (!file_exists($hostapdIni)) {
		return 0;
	} else {
		$arrHostapdConf = parse_ini_file($hostapdIni);
	}
    return  $arrHostapdConf['BridgedEnable'];
 }

// Returns VPN provider ID, if defined
function getProviderID()
{
    if (RASPI_VPN_PROVIDER_ENABLED) {
        $arrProvider = parse_ini_file(RASPI_CONFIG.'/provider.ini');
        if (isset($arrProvider['providerID'])) {
            return $arrProvider['providerID'];
        }
    }
}

/**
 * Validates the format of a CIDR notation string
 *
 * @param string $cidr
 * @return bool
 */
function validateCidr($cidr)
{
    $parts = explode('/', $cidr);
    if(count($parts) != 2) {
        return false;
    }
    $ip = $parts[0];
    $netmask = intval($parts[1]);

    if($netmask < 0) {
        return false;
    }
    if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return $netmask <= 32;
    }
    if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return $netmask <= 128;
    }
    return false;
}    

// Validates a host or FQDN
function validate_host($host)
{
  return preg_match('/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i', $host);
}

/**
 * Validates a MAC address
 *
 * @param string $mac
 * @return bool
 */
function validateMac($mac) {
    $macAddress = strtoupper(preg_replace('/[^a-fA-F0-9]/', '', $mac));
    if (strlen($macAddress) !== 12) {
        return false;
    }
    if (!ctype_xdigit($macAddress)) {
        return false;
    }
    return true;
}

// Gets night mode toggle value
// @return boolean
function getNightmode()
{
    if (isset($_COOKIE['theme']) && $_COOKIE['theme'] == 'dark.css') {
        return true;
    } else {
        return false;
    }
}	

// Sets data-bs-theme
// @return string
function setTheme()
{
    if (getNightmode()) {
        echo 'data-bs-theme="dark"';
    } else {
        echo 'data-bs-theme="light"';
    }
}
// search array for matching string and return only first matching group
function preg_only_match($pat,$haystack)
{
  $match = "";
  if(!empty($haystack) && !empty($pat)) {
    if(!is_array($haystack)) $haystack = array($haystack);
    $str = preg_grep($pat,$haystack);
    if (!empty($str) && preg_match($pat,array_shift($str),$match) === 1 ) $match = $match[1];
  }
  return $match;
}

// Sanitizes a string for QR encoding
// @param string $str
// @return string
function qr_encode($str)
{
    return preg_replace('/(?<!\\\)([\":;,])/', '\\\\\1', $str);
}

function evalHexSequence($string)
{
    $evaluator = function ($input) {
	return hex2bin($input[1]);
    };
    return preg_replace_callback('/\\\x(..)/', $evaluator, $string);
}

function hexSequence2lower($string) {
 return preg_replace_callback('/\\\\x([0-9A-F]{2})/', function($b){ return '\x'.strtolower($b[1]); }, $string);
}

/* File upload callback object
 *
 */
class validation
{
    public function check_name_length($object)
    {
        if (strlen($object->file['filename']) > 255) {
            $object->set_error('File name is too long.');
        }
    }
}

/* Resolves public IP address
 *
 * @return string $public_ip
 */
function get_public_ip()
{
    exec('wget --timeout=5 --tries=1 https://ipinfo.io/ip -qO -', $public_ip);
    return $public_ip[0];
}

// Load non default JS/ECMAScript in footer
function loadFooterScripts($extraFooterScripts)
{
    foreach ($extraFooterScripts as $script) {
        echo '<script type="text/javascript" src="' , $script['src'] , '"';
        if ($script['defer']) {
            echo ' defer="defer"';
        }
        echo '></script>' , PHP_EOL;
    }
}

/**
 * Validate whether the given network interface exists on the system.
 * This function retrieves all currently available network interfaces using the `ip link show` command
 * and checks if the provided interface name is in the list.
 */
function validateInterface($interface)
{
    // Retrieve all available network interfaces
    $valid_interfaces = shell_exec('ip -o link show | awk -F": " \'{print $2}\'');

    // Convert to array (one interface per line)
    $valid_interfaces = explode("\n", trim($valid_interfaces));

    // Check if the provided interface exists in the list
    return in_array($interface, $valid_interfaces, true);
}

/**
 * Returns ISO standard 2-letter country codes
 *
 * @param string $locale
 * @param boolean $flag
 * @see   https://salsa.debian.org/debian/isoquery/
*/
function getCountryCodes($locale = 'en', $flag = true) {
    define("FLAG_SUPPORT", "3.3.0");
    $output = [];
    $version = shell_exec("isoquery --version | grep -oP '(?<=isoquery )\d+\.\d+\.\d+'");
    $compat = checkReleaseVersion(FLAG_SUPPORT, $version);

    if ($flag && $compat) {
        $opt = '--flag';
    }
    exec("isoquery $opt --locale $locale | awk -F'\t' '{print $5 \"\t\" $0}' | sort | cut -f2-", $output);

    $countryData = [];
    foreach ($output as $line) {
        $parts = explode("\t", $line);
        if (count($parts) >= 2) {
            $countryCode = $parts[0];
            if ($flag) {
                $countryFlag = $parts[3];
                $countryName = $parts[4] .' ';
            } else {
                $countryName = $parts[3];
            }
            $countryData[$countryCode] = $countryName.$countryFlag;
        }
    }
    return $countryData;
}

/**
 * Compares the current release with the latest available release
 *
 * @param string $installed
 * @param string $latest
 * @return boolean
 */
function checkReleaseVersion($installed, $latest) {
    $installedArray = explode('.', $installed);
    $latestArray = explode('.', $latest);

    // compare segments of the version number
    for ($i = 0; $i < max(count($installedArray), count($latestArray)); $i++) {
        $installedSegment = (int)($installedArray[$i] ?? 0);
        $latestSegment = (int)($latestArray[$i] ?? 0);

        if ($installedSegment < $latestSegment) {
            return true;
        } elseif ($installedSegment > $latestSegment) {
            return false;
        }
    }
    return false;
}

 /**
 * Returns logfile contents up to a maximum defined limit, in kilobytes
 *
 * @param string $file_path
 * @param string $file_data optional
 * @return string $log_limited
 */
function getLogLimited($file_path, $file_data = null) {
    $limit_in_kb = isset($_SESSION['log_limit']) ? $_SESSION['log_limit'] : RASPI_LOG_SIZE_LIMIT;
    $limit = $limit_in_kb * 1024; // convert KB to bytes

    if ($file_data === null) {
        $file_size = filesize($file_path);
        $start_position = max(0, $file_size - $limit);
        $log_limited = file_get_contents($file_path, false, null, $start_position);
    } else {
        $file_size = strlen($file_data);
        $start_position = max(0, $file_size - $limit);
        $log_limited = substr($file_data, $start_position);
    }
    return $log_limited;
 }

/**
 * Function to darken a color by a percentage
 * From @marek-guran's material-dark theme for RaspAP
 * Author URI: https://github.com/marek-guran
 */
function darkenColor($color, $percent)
{
    $percent /= 100;
    $r = hexdec(substr($color, 1, 2));
    $g = hexdec(substr($color, 3, 2));
    $b = hexdec(substr($color, 5, 2));

    $r = round($r * (1 - $percent));
    $g = round($g * (1 - $percent));
    $b = round($b * (1 - $percent));

    return sprintf("#%02x%02x%02x", $r, $g, $b);
}

/**
 * Function to lighten a color by a percentage
 * From @marek-guran's material-dark theme for RaspAP
 * Author URI: https://github.com/marek-guran
 */
function lightenColor($color, $percent)
{
    $percent /= 100;
    $r = hexdec(substr($color, 1, 2));
    $g = hexdec(substr($color, 3, 2));
    $b = hexdec(substr($color, 5, 2));

    $r = round($r + (255 - $r) * $percent);
    $g = round($g + (255 - $g) * $percent);
    $b = round($b + (255 - $b) * $percent);

    return sprintf("#%02x%02x%02x", $r, $g, $b);
}

function renderStatus($hostapd_led, $hostapd_status, $memused_led, $memused, $cputemp_led, $cputemp)
{
    ?>
    <div class="row g-0">
      <div class="col-4 ms-2 sidebar-brand-icon">
        <img src="app/img/raspAP-logo.php?static=1" class="navbar-logo" width="70" height="70">
      </div>
      <div class="col ml-2">
        <div class="ml-1 sb-status"><?php echo _("Status"); ?></div>
        <div class="info-item-xs"><span class="icon">
          <i class="fas fa-circle hostapd-led <?php echo ($hostapd_led); ?>"></i></span> <?php echo _("Hotspot").' '. _($hostapd_status); ?>
        </div>
        <div class="info-item-xs"><span class="icon">
          <i class="fas fa-circle <?php echo ($memused_led); ?>"></i></span> <?php echo _("Mem Use").': '. htmlspecialchars(strval($memused), ENT_QUOTES); ?>%
        </div>
        <div class="info-item-xs"><span class="icon">
          <i class="fas fa-circle <?php echo ($cputemp_led); ?>"></i></span> <?php echo _("CPU").': '. htmlspecialchars($cputemp, ENT_QUOTES); ?>°C
        </div>
      </div>
    </div>
    <?php
}


/**
 * Executes a callback with a timeout
 *
 * @param callable $callback function to execute
 * @param int $interval timeout in milliseconds
 * @return mixed result of the callback
 * @throws \Exception if the execution exceeds the timeout or an error occurs
 */
function callbackTimeout(callable $callback, int $interval)
{
    $startTime = microtime(true); // use high-resolution timer
    $result = $callback();
    $elapsed = (microtime(true) - $startTime) * 1000;

    if ($elapsed > $interval) {
        throw new \Exception('Operation timed out');
    }

    return $result;
}
