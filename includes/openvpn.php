<?php

include_once('includes/status_messages.php');
require_once 'config.php';

/**
 *
 * Manage OpenVPN configuration
 *
 */
function DisplayOpenVPNConfig()
{
    $status = new StatusMessages();
    $serviceStatus="down";
    if (!RASPI_MONITOR_ENABLED) {
        if (isset($_POST['SaveOpenVPNSettings'])) {
            if (isset($_POST['authUser'])) {
                $authUser = strip_tags(trim($_POST['authUser']));
            }
            if (isset($_POST['authPassword'])) {
                $authPassword = strip_tags(trim($_POST['authPassword']));
            }
            $return = SaveOpenVPNConfig($status, $_FILES['customFile'], $authUser, $authPassword);
        } elseif (isset($_POST['StartOpenVPN'])) {
            $status->addMessage('Attempting to start OpenVPN', 'info');
            exec('sudo /bin/systemctl start openvpn-client@client', $return);
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        } elseif (isset($_POST['StopOpenVPN'])) {
            $status->addMessage('Attempting to stop OpenVPN', 'info');
            exec('sudo /bin/systemctl stop openvpn-client@client', $return);
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        }
    } elseif (isset($_POST['GetVPNGateServers'])) {
        $status->addMessage('Retrieving servers for AU CN GB JP KR MY RU SG TH US VN', 'info');
        exec('sudo /etc/raspap/openvpn/vpn.sh JP AU CN GB KR MY RU SG TH US VN', $return);
   } elseif (isset($_POST['EnableNAT'])) {
        $status->addMessage('Enabling NAT', 'info');
	exec('sudo /etc/raspap/openvpn/setIptables.sh',$return);

   } elseif (isset($_POST['DisableNAT'])) {
        $status->addMessage('Disabling NAT', 'info');
        exec('sudo /etc/raspap/openvpn/unSetIptables.sh',$return);

   } elseif (isset($_POST['UseVPNGateServer'])) {
	$fileName=$_POST['Copy'];
        system("sudo /etc/raspap/openvpn/cpOpenVPNFile.sh $fileName " . RASPI_OPENVPN_CLIENT_CONFIG, $return);
        $status->addMessage('Copy config ' . $fileName . " to " . RASPI_OPENVPN_CLIENT_CONFIG, 'info');
//VN-2weeks-tcp-183.80.95.225.ovpn
//	preg_match("/(.*)\-(.*)\-(.*)\-(.*)\.(.*)/", $fileName, $matches);
//	array_shift($matches);
//	list($ctry, $logPeriod, $protocol, $ip, $fileExt) = $matches;
	exec('sudo /etc/raspap/openvpn/unSetIptables.sh',$return);
	$status->addMessage('Disabled NAT, stopping OpenVPN...', 'info');
	exec('sudo /bin/systemctl stop openvpn-client@client', $return);
        $serviceStatus="?";
        $totalTries=0;
	sleep(2);
        while(true){
                sleep(1);
                exec('sudo /bin/systemctl status openvpn-client@client', $return);
                foreach ($return as $line) {
                        if (strpos($line, 'Stopped OpenVPN tunnel') !==false) {
                                $serviceStatus="down";
                        }
                }
                if($totalTries>10 | $serviceStatus === "down"){
                        break;
                }
                $totalTries++;
        }
        exec('sudo /bin/systemctl start openvpn-client@client', $return);
	$serviceStatus="down";
	$totalTries=0;
	sleep(3);
	while(true){
		sleep(1);
		exec('sudo /bin/systemctl status openvpn-client@client', $return2);
	        foreach ($return2 as $line) {
			if (strpos($line, 'Initialization Sequence Completed') !==false) {
				$serviceStatus="up";
			}else if(strpos($line,"TLS error: Unsupported protocol")!==false 
				|| strpos($line,"Connection refused")!==false
				|| strpos($line,"AUTH: Received control message: AUTH_FAILED")!==false
                                || strpos($line,"No route to host")!==false){
				$status->addMessage($line, 'warning');
				$totalTries=100;
				break;
                        }
		}
		if($serviceStatus === "up"){
			$status->addMessage('VPN Started, enabling NAT', 'success');
                        exec('sudo /etc/raspap/openvpn/setIptables.sh',$return);
			break;
		}else if($totalTries>30){
                        $status->addMessage('Unable to start VPN, NAT remains disabled', 'warning');
		        exec('sudo /bin/systemctl stop openvpn-client@client', $return);

			exec('sudo /etc/raspap/openvpn/mvTimedOutOpenVPNFile.sh '.$fileName.' '.$fileName.'-timeout',$return);
 			break;
		}
		$totalTries++;
	}
    }
    exec('wget https://ipinfo.io/ip -qO -', $return3);

    $auth = file(RASPI_OPENVPN_CLIENT_LOGIN, FILE_IGNORE_NEW_LINES);
    $public_ip = $return3[0];
	exec('sudo /bin/systemctl status openvpn-client@client', $return4);
	foreach ($return4 as $line) {
	        if (strpos($line, 'Status: "Initialization Sequence Completed"') !==false) {
	                $serviceStatus="up";
			break;
	        }
	}
    // parse client auth credentials
    if (!empty($auth)) {
        $authUser = $auth[0];
        $authPassword = $auth[1];
    }

    echo renderTemplate("openvpn", compact(
        "status",
        "serviceStatus",
        "openvpnstatus",
        "public_ip",
        "authUser",
        "authPassword"
    ));
}

/**
 *
 * Validates uploaded .ovpn file, adds auth-user-pass and
 * stores auth credentials in login.conf. Copies files from
 * tmp to OpenVPN
 *
 * @param object $status
 * @param object $file
 * @param string $authUser
 * @param string $authPassword
 * @return object $status
 */
function SaveOpenVPNConfig($status, $file, $authUser, $authPassword)
{
    $tmp_ovpnclient = '/tmp/ovpnclient.ovpn';
    $tmp_authdata = '/tmp/authdata';
    $auth_flag = 0;

    try {
        // If undefined or multiple files, treat as invalid
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new RuntimeException('Invalid parameters');
        }

        // Parse returned errors
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new RuntimeException('OpenVPN configuration file not sent');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new RuntimeException('Exceeded filesize limit');
            default:
                throw new RuntimeException('Unknown errors');
        }

        // Validate extension
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if ($ext != 'ovpn') {
            throw new RuntimeException('Invalid file extension');
        }

        // Validate MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        if (false === $ext = array_search(
            $finfo->file($file['tmp_name']),
            array(
                'ovpn' => 'text/plain'
            ),
            true
        )) {
            throw new RuntimeException('Invalid file format');
        }

        // Validate filesize
        define('KB', 1024);
        if ($file['size'] > 64*KB) {
            throw new RuntimeException('File size limit exceeded');
        }

        // Use safe filename, save to /tmp
        if (!move_uploaded_file(
            $file['tmp_name'],
            sprintf(
                '/tmp/%s.%s',
                'ovpnclient',
                $ext
            )
        )) {
            throw new RuntimeException('Unable to move uploaded file');
        }
        // Good file upload, update auth credentials if present
        if (!empty($authUser) && !empty($authPassword)) {
            $auth_flag = 1;
            // Move tmp authdata to /etc/openvpn/login.conf
            $auth = $authUser .PHP_EOL . $authPassword .PHP_EOL;
            file_put_contents($tmp_authdata, $auth);
            system("sudo cp $tmp_authdata " . RASPI_OPENVPN_CLIENT_LOGIN, $return);
            if ($return !=0) {
                $status->addMessage('Unable to save client auth credentials', 'danger');
            }
        }

        // Set iptables rules and, optionally, auth-user-pass
        exec("sudo /etc/raspap/openvpn/configauth.sh $tmp_ovpnclient $auth_flag " .RASPI_WIFI_CLIENT_INTERFACE, $return);
        foreach ($return as $line) {
            $status->addMessage($line, 'info');
        }

        // Copy tmp client config to /etc/openvpn/client
        system("sudo cp $tmp_ovpnclient " . RASPI_OPENVPN_CLIENT_CONFIG, $return);
        if ($return ==0) {
            $status->addMessage('OpenVPN client.conf uploaded successfully', 'info');
        } else {
            $status->addMessage('Unable to save OpenVPN client config', 'danger');
        }

        return $status;
    } catch (RuntimeException $e) {
        $status->addMessage($e->getMessage(), 'danger');
        return $status;
    }
}
function listdir_by_date($path){
    $dir = opendir($path);
    $list = array();
    while($file = readdir($dir)){
        if ($file != '.' and $file != '..'){
            // add the filename, to be sure not to
            // overwrite a array key
            $ctime = filectime($dir . $file) . ',' . $file;
            $list[$ctime] = $file;
        }
    }
    closedir($dir);
    krsort($list);
    return $list;
}
function scan_dir($dir) {
    $ignored = array('.', '..', '.svn', '.htaccess');

    $files = array();
    foreach (scandir($dir) as $file) {
        if (in_array($file, $ignored)) continue;
        $files[$file] = filemtime($dir . '/' . $file);
    }

    arsort($files,SORT_NUMERIC);
    $files = array_keys($files);

    return ($files) ? $files : false;
}
