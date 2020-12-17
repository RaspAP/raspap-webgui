<?php

use eftec\bladeone\BladeOne;

/* Functions for Networking */

function mask2cidr($mask)
{
    $long = ip2long($mask);
    $base = ip2long('255.255.255.255');
    return 32-log(($long ^ $base)+1, 2);
}

/* Functions to write ini files */

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
function renderTemplate($name, $viewData = [], $isAjax = false)
{

    /*
    $file = realpath(dirname(__FILE__) . "/../templates/$name.php");
    if (!file_exists($file)) {
        return "template $name ($file) not found";
    }

    if (is_array($viewData)) {
        extract($viewData);
    }

    ob_start();
    include $file;
    return ob_get_clean();
    */

    if (! $isAjax){

      // there is some data which is required by the layout, so add
      // it here to save every controller from having to retreive it.
      $system = new System();

      $viewData['hostname'] = $system->hostname();
      $viewData['uptime']   = $system->uptime();
      $viewData['cores']    = $system->processorCount();

      // mem used
      $viewData['memused']  = $system->usedMemory();
      $viewData['memused_status'] = "primary";
      if ($viewData['memused'] > 90) {
          $viewData['memused_status'] = "danger";
          $viewData['memused_led'] = "service-status-down";
      } elseif ($viewData['memused'] > 75) {
          $viewData['memused_status'] = "warning";
          $viewData['memused_led'] = "service-status-warn";
      } elseif ($viewData['memused'] >  0) {
          $viewData['memused_status'] = "success";
          $viewData['memused_led'] = "service-status-up";
      }

      // cpu load
      $viewData['cpuload'] = $system->systemLoadPercentage();
      if ($viewData['cpuload'] > 90) {
          $viewData['cpuload_status'] = "danger";
      } elseif ($viewData['cpuload'] > 75) {
          $viewData['cpuload_status'] = "warning";
      } elseif ($viewData['cpuload'] >=  0) {
          $viewData['cpuload_status'] = "success";
      }

      // cpu temp
      $viewData['cputemp'] = $system->systemTemperature();
      if ($viewData['cputemp'] > 70) {
          $viewData['cputemp_status'] = "danger";
          $viewData['cputemp_led'] = "service-status-down";
      } elseif ($viewData['cputemp'] > 50) {
          $viewData['cputemp_status'] = "warning";
          $viewData['cputemp_led'] = "service-status-warn";
      } else {
          $viewData['cputemp_status'] = "success";
          $viewData['cputemp_led'] = "service-status-up";
      }

      // hostapd status
      $viewData['hostapd'] = $system->hostapdStatus();
      if ($viewData['hostapd'][0] == 1) {
          $viewData['hostapd_status'] = "active";
          $viewData['hostapd_led'] = "service-status-up";
      } else {
          $viewData['hostapd_status'] = "inactive";
          $viewData['hostapd_led'] = "service-status-down";
      }



      $viewData['config'] = getConfig();
      $viewData['theme_url'] = getThemeOpt();
      $viewData['toggleState'] = getSidebarState();
      $viewData['bridgedEnable'] = getBridgedState();

    }

    $views = __DIR__ . '/../views';
    $cache = __DIR__ . '/../compiles';
    //$blade = new BladeOne($views,$cache,BladeOne::MODE_DEBUG); // MODE_DEBUG allows to pinpoint troubles.
    $blade = new BladeOne($views,$cache);
    return $blade->run($name, $viewData); // it calls /views/$name.blade.php


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
    if ($_COOKIE['sidebarToggled'] ?? 'false' == 'true' ) {
        return"toggled";
    }
}

// Returns bridged AP mode status
function getBridgedState()
{
    $arrHostapdConf = parse_ini_file(RASPI_CONFIG.'/hostapd.ini');
    // defaults to false
    return  $arrHostapdConf['BridgedEnable'] ?? 0 == 1;
}

// Validates a host or FQDN
function validate_host($host) {
  return preg_match('/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i', $host);
}

