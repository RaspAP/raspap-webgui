<?php
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
*
* Add CSRF Token to form
*
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
*
* Validate CSRF Token
*
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
*
* Display a selector field for a form. Arguments are:
*   $name:     Field name
*   $options:  Array of options
*   $selected: Selected option (optional)
*       If $options is an associative array this should be the key
*
*/
function SelectorOptions($name, $options, $selected = null, $id = null)
{
    echo '<select class="form-control" name="'.htmlspecialchars($name, ENT_QUOTES).'"';
    if (isset($id)) {
        echo ' id="' . htmlspecialchars($id, ENT_QUOTES) .'"';
    }

    echo '>' , PHP_EOL;
    foreach ($options as $opt => $label) {
        $select = '';
        $key = isAssoc($options) ? $opt : $label;
        if ($key == $selected) {
            $select = ' selected="selected"';
        }

        echo '<option value="'.htmlspecialchars($key, ENT_QUOTES).'"'.$select.'>'.
            htmlspecialchars($label, ENT_QUOTES).'</option>' , PHP_EOL;
    }

    echo '</select>' , PHP_EOL;
}

/**
*
* @param string $input
* @param string $string
* @param int $offset
* @param string $separator
* @return $string
*/
function GetDistString($input, $string, $offset, $separator)
{
    $string = substr($input, strpos($input, $string)+$offset, strpos(substr($input, strpos($input, $string)+$offset), $separator));
    return $string;
}

/**
*
* @param array $arrConfig
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
* @param string $freq
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
* @param string $security
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
*
*
*/
function DisplayOpenVPNConfig()
{

    exec('cat '. RASPI_OPENVPN_CLIENT_CONFIG, $returnClient);
    exec('cat '. RASPI_OPENVPN_SERVER_CONFIG, $returnServer);
    exec('pidof openvpn | wc -l', $openvpnstatus);

    if ($openvpnstatus[0] == 0) {
        $status = '<div class="alert alert-warning alert-dismissable">OpenVPN is not running
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button></div>';
    } else {
        $status = '<div class="alert alert-success alert-dismissable">OpenVPN is running
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button></div>';
    }

    // parse client settings
    foreach ($returnClient as $a) {
        if ($a[0] != "#") {
            $arrLine = explode(" ", $a) ;
            $arrClientConfig[$arrLine[0]]=$arrLine[1];
        }
    }

    // parse server settings
    foreach ($returnServer as $a) {
        if ($a[0] != "#") {
            $arrLine = explode(" ", $a) ;
            $arrServerConfig[$arrLine[0]]=$arrLine[1];
        }
    }
    ?>
    <div class="row">
    <div class="col-lg-12">
      <div class="card">
      <div class="card-header"><i class="fas fa-key fa-fw mr-2"></i>Configure OpenVPN</div>
        <div class="card-body">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs">
                <li class="nav-item"><a class="nav-link active" href="#openvpnclient" data-toggle="tab">Client settings</a></li>
                <li class="nav-item"><a class="nav-link" href="#openvpnserver" data-toggle="tab">Server settings</a></li>
            </ul>
            <!-- Tab panes -->
            <div class="tab-content">
                <p><?php echo $status; ?></p>
                <div class="tab-pane active" id="openvpnclient">
                    <h4>Client settings</h4>
                    <form role="form" action="?page=save_hostapd_conf" method="POST">
                    <?php echo CSRFTokenFieldTag() ?>

                    <div class="row">
                        <div class="form-group col-md-6">
                          <div class="custom-file">
                            <input type="file" class="custom-file-input" id="customFile">
                            <label class="custom-file-label" for="customFile">Select OpenVPN configuration file (.ovpn)</label>
                          </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">Client Log</label>
                            <input type="text" class="form-control" id="disabledInput" name="log-append" type="text" placeholder="<?php echo htmlspecialchars($arrClientConfig['log-append'], ENT_QUOTES); ?>" disabled="disabled" />
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="openvpnserver">
                    <h4>Server settings</h4>
                    <div class="row">
                        <div class="form-group col-md-6">
                        <label for="code">Port</label> 
                        <input type="text" class="form-control" name="openvpn_port" value="<?php echo htmlspecialchars($arrServerConfig['port'], ENT_QUOTES); ?>" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                        <label for="code">Protocol</label>
                        <input type="text" class="form-control" name="openvpn_proto" value="<?php echo htmlspecialchars($arrServerConfig['proto'], ENT_QUOTES); ?>" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                        <label for="code">Root CA certificate</label>
                        <input type="text" class="form-control" name="openvpn_rootca" placeholder="<?php echo htmlspecialchars($arrServerConfig['ca'], ENT_QUOTES); ?>" disabled="disabled" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                        <label for="code">Server certificate</label>
                        <input type="text" class="form-control" name="openvpn_cert" placeholder="<?php echo htmlspecialchars($arrServerConfig['cert'], ENT_QUOTES); ?>" disabled="disabled" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                        <label for="code">Diffie Hellman parameters</label>
                        <input type="text" class="form-control" name="openvpn_dh" placeholder="<?php echo htmlspecialchars($arrServerConfig['dh'], ENT_QUOTES); ?>" disabled="disabled" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                        <label for="code">KeepAlive</label>
                        <input type="text" class="form-control" name="openvpn_keepalive" value="<?php echo htmlspecialchars($arrServerConfig['keepalive'], ENT_QUOTES); ?>" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                        <label for="code">Server log</label>
                        <input type="text" class="form-control" name="openvpn_status" placeholder="<?php echo htmlspecialchars($arrServerConfig['status'], ENT_QUOTES); ?>" disabled="disabled" />
                        </div>
                    </div>
                </div>
                <input type="submit" class="btn btn-outline btn-primary" name="SaveOpenVPNSettings" value="Save settings" />
                <?php
                if ($hostapdstatus[0] == 0) {
                    echo '<input type="submit" class="btn btn-success" name="StartOpenVPN" value="Start OpenVPN" />' , PHP_EOL;
                } else {
                    echo '<input type="submit" class="btn btn-warning" name="StopOpenVPN" value="Stop OpenVPN" />' , PHP_EOL;
                }
                ?>
                </form>
            </div>
        </div><!-- /.card-body -->
    <div class="card-footer"> Information provided by openvpn</div>
  </div><!-- /.card -->
</div><!-- /.col-lg-12 -->
</div><!-- /.row -->
<?php
}

/**
*
*
*/
function DisplayTorProxyConfig()
{

    exec('cat '. RASPI_TORPROXY_CONFIG, $return);
    exec('pidof tor | wc -l', $torproxystatus);

    if ($torproxystatus[0] == 0) {
        $status = '<div class="alert alert-warning alert-dismissable">TOR is not running
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button></div>';
    } else {
        $status = '<div class="alert alert-success alert-dismissable">TOR is running
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button></div>';
    }

    $arrConfig = array();
    foreach ($return as $a) {
        if ($a[0] != "#") {
            $arrLine = explode(" ", $a) ;
            $arrConfig[$arrLine[0]]=$arrLine[1];
        }
    }

    ?>
    <div class="row">
    <div class="col-lg-12">
      <div class="card"> 
        <div class="card-header"><i class="fa fa-eye-slash fa-fw"></i> Configure TOR proxy</div>
        <div class="card-body">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs">
                <li class="nav-item"><a class="nav-link active" href="#basic" data-toggle="tab">Basic</a></li>
                <li class="nav-item"><a class="nav-link" href="#relay" data-toggle="tab">Relay</a>
                </li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content">
                <p><?php echo $status; ?></p>

                <div class="tab-pane active" id="basic">
                    <h4>Basic settings</h4>
                    <form role="form" action="?page=save_hostapd_conf" method="POST">
                    <?php echo CSRFTokenFieldTag() ?>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">VirtualAddrNetwork</label>
                            <input type="text" class="form-control" name="virtualaddrnetwork" value="<?php echo htmlspecialchars($arrConfig['VirtualAddrNetwork'], ENT_QUOTES); ?>" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">AutomapHostsSuffixes</label>
                            <input type="text" class="form-control" name="automaphostssuffixes" value="<?php echo htmlspecialchars($arrConfig['AutomapHostsSuffixes'], ENT_QUOTES); ?>" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">AutomapHostsOnResolve</label>
                            <input type="text" class="form-control" name="automaphostsonresolve" value="<?php echo htmlspecialchars($arrConfig['AutomapHostsOnResolve'], ENT_QUOTES); ?>" />
                        </div>
                    </div>  
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">TransListenAddress</label>
                            <input type="text" class="form-control" name="translistenaddress" value="<?php echo htmlspecialchars($arrConfig['TransListenAddress'], ENT_QUOTES); ?>" />
                        </div>
                    </div>  
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">DNSPort</label>
                            <input type="text" class="form-control" name="dnsport" value="<?php echo htmlspecialchars($arrConfig['DNSPort'], ENT_QUOTES); ?>" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">DNSListenAddress</label>
                            <input type="text" class="form-control" name="dnslistenaddress" value="<?php echo htmlspecialchars($arrConfig['DNSListenAddress'], ENT_QUOTES); ?>" />
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="relay">
                    <h4>Relay settings</h4>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">ORPort</label>
                            <input type="text" class="form-control" name="orport" value="<?php echo htmlspecialchars($arrConfig['ORPort'], ENT_QUOTES); ?>" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">ORListenAddress</label>
                            <input type="text" class="form-control" name="orlistenaddress" value="<?php echo htmlspecialchars($arrConfig['ORListenAddress'], ENT_QUOTES); ?>" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">Nickname</label>
                            <input type="text" class="form-control" name="nickname" value="<?php echo htmlspecialchars($arrConfig['Nickname'], ENT_QUOTES); ?>" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">Address</label>
                            <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($arrConfig['Address'], ENT_QUOTES); ?>" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">RelayBandwidthRate</label>
                            <input type="text" class="form-control" name="relaybandwidthrate" value="<?php echo htmlspecialchars($arrConfig['RelayBandwidthRate'], ENT_QUOTES); ?>" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">RelayBandwidthBurst</label>
                            <input type="text" class="form-control" name="relaybandwidthburst" value="<?php echo htmlspecialchars($arrConfig['RelayBandwidthBurst'], ENT_QUOTES); ?>" />
                        </div>
                    </div>
                </div>

                <input type="submit" class="btn btn-outline btn-primary" name="SaveTORProxySettings" value="Save settings" />
                <?php
                if ($torproxystatus[0] == 0) {
                    echo '<input type="submit" class="btn btn-success" name="StartTOR" value="Start TOR" />' , PHP_EOL;
                } else {
                    echo '<input type="submit" class="btn btn-warning" name="StopTOR" value="Stop TOR" />' , PHP_EOL;
                };
                ?>
                </form>
            </div><!-- /.tab-content -->
        </div><!-- /.card-body -->
        <div class="card-footer"> Information provided by tor</div>
</div><!-- /.col-lg-12 -->
</div><!-- /.row -->
    <?php
}

/**
*
*
*/
function SaveTORAndVPNConfig()
{
    if (isset($_POST['SaveOpenVPNSettings'])) {
        // TODO
    } elseif (isset($_POST['SaveTORProxySettings'])) {
        // TODO
    } elseif (isset($_POST['StartOpenVPN'])) {
        echo "Attempting to start openvpn";
        exec('sudo /etc/init.d/openvpn start', $return);
        foreach ($return as $line) {
            echo htmlspecialchars($line, ENT_QUOTES).'<br />' , PHP_EOL;
        }
    } elseif (isset($_POST['StopOpenVPN'])) {
        echo "Attempting to stop openvpn";
        exec('sudo /etc/init.d/openvpn stop', $return);
        foreach ($return as $line) {
            echo htmlspecialchars($line, ENT_QUOTES).'<br />' , PHP_EOL;
        }
    } elseif (isset($_POST['StartTOR'])) {
        echo "Attempting to start TOR";
        exec('sudo /etc/init.d/tor start', $return);
        foreach ($return as $line) {
            echo htmlspecialchars($line, ENT_QUOTES).'<br />' , PHP_EOL;
        }
    } elseif (isset($_POST['StopTOR'])) {
        echo "Attempting to stop TOR";
        exec('sudo /etc/init.d/tor stop', $return);
        foreach ($return as $line) {
            echo htmlspecialchars($line, ENT_QUOTES).'<br />' , PHP_EOL;
        }
    }
}

/**
 * Renders a simple PHP template
 */
function renderTemplate($name, $data = [])
{
    $file = realpath(dirname(__FILE__) . "/../templates/$name.php");
    if (!file_exists($file)) {
        return "template $name ($file) not found";
    }

    if (is_array($data)) {
        extract($data);
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
