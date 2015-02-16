<?php

include('phpincs.php');
$output = $return = 0;
$page = $_GET['page'];


echo '<html>
<head>

<!-- Bootstrap core CSS -->
 <link href="assets/css/bootstrap.css" rel="stylesheet">

<link href="assets/css/styles.css" rel="stylesheet" type="text/css"></link>
<script type="text/Javascript" src="assets/js/functions.js"></script>
<title>Raspbian WiFi Configuration Portal</title>
</head>
<body>
<div class="header">
<h2>Raspbian WiFi Configuration Portal</h2>
</div>
<div class="menu">
<input class="button" type="button" value="WiFi
Info" name="wlan0_info" onclick="document.location=\'?page=\'+this.name" />
<input class="button" type="button" value="Configure
Client" name="wpa_conf" onclick="document.location=\'?page=\'+this.name" />
<input class="button" type="button" value="Configure
Hotspot" name="hostapd_conf" onclick="document.location=\'?page=\'+this.name" />
<input class="button" type="button" value="Configure
DHCP Server" name="dhcpd_conf" onclick="document.location=\'?page=\'+this.name" />
</div>
<div class="content">';

switch($page) {
	case "dhcpd_conf":
		exec('cat /etc/dnsmasq.conf',$return);
		$conf = ParseConfig($return);
		$arrRange = explode(",",$conf['dhcp-range']);
		$RangeStart = $arrRange[0];
		$RangeEnd = $arrRange[1];
		$RangeMask = $arrRange[2];
		preg_match('/([0-9]*)([a-z])/i',$arrRange[3],$arrRangeLeaseTime);
		switch($arrRangeLeaseTime[2]) {
			case "h":
				$hselected = " selected";
			break;
			case "m":
				$mselected = " selected";
			break;
			case "d":
				$dselected = " selected";
			break;
		}
		exec('pidof dnsmasq | wc -l',$dnsmasq);
		if($dnsmasq[0] == 0) {
			$status = '<span class="red">dnsmasq not running</span>';
		} else {
			$status = '<span class="green">dnsmasq is running</span>';
		}
		echo 'DHCP Server Options<br />
<span id="dnsmasqstatus">Status : '.$status.'</span>
<form method="POST" action="?page=dhcpd_conf">
Interface : <select name="interface">';
		exec("cat /proc/net/dev | tail -n -3 | awk -F :\  ' { print $1 } ' | tr -d ' '",$interfaces);
		foreach($interfaces as $int) {
			$select = '';
			if($int == $conf['interface']) {
				$select = " selected";
			}
				echo '<option value="'.$int.'"'.$select.'>'.$int.'</option>';
		}

echo'</select><br />
Starting IP Address : <input type="text" name="RangeStart" value="'.$RangeStart.'" /> <br />
Ending IP Address : <input type="text" name="RangeEnd" value="'.$RangeEnd.'" /><br />
Lease Time <input type="text" name="RangeLeaseTime" value="'.$arrRangeLeaseTime[1].'" /><select name="RangeLeaseTimeUnits"><option value="m"'.$mselected.'>Minutes</option><option value="h"'.$hselected.'>Hours</option><option value="d"'.$dselected.'>Days</option><option value="infinite">Infinite</option>
</select> <br />
<input type="submit" value="Save" name="savedhcpdsettings" />';
		if($dnsmasq[0] == 0) {
			echo'<input type="submit" value="Start dnsmasq" name="startdhcpd" />';
		} else {
			echo '<input type="submit" value="Stop dnsmasq" name="stopdhcpd" />';
		}
		echo'
</form>
<hr>
Client list<br />
';
		exec('cat /var/lib/misc/dnsmasq.leases',$leases);
		foreach($leases as $lease) {
			echo $lease;
		}

		if(isset($_POST['savedhcpdsettings'])) {
			$config = 'interface='.$_POST['interface'].'
dhcp-range='.$_POST['RangeStart'].','.$_POST['RangeEnd'].',255.255.255.0,'.$_POST['RangeLeaseTime'].''.$_POST['RangeLeaseTimeUnits'];
			exec('echo "'.$config.'" > /tmp/dhcpddata',$temp);
			system('sudo cp /tmp/dhcpddata /etc/dnsmasq.conf',$return);
			if($return == 0) {
				echo "dnsmasq configuration updated successfully";
			} else {
				echo "Dnsmasq configuration failed to be updated";
			}
		}

		if(isset($_POST['startdhcpd'])) {
			$line = system('sudo /etc/init.d/dnsmasq start',$return);
			echo "Attempting to start dnsmasq";
		}

		if(isset($_POST['stopdhcpd'])) {
			$line = system('sudo /etc/init.d/dnsmasq stop',$return);
			echo "Stopping dnsmasq";
		}
	break;

	case "wlan0_info":
		exec('ifconfig wlan0',$return);
		exec('iwconfig wlan0',$return);
		$strWlan0 = implode(" ",$return);
		$strWlan0 = preg_replace('/\s\s+/', ' ', $strWlan0);
		preg_match('/HWaddr ([0-9a-f:]+)/i',$strWlan0,$result);
		$strHWAddress = $result[1];
		preg_match('/inet addr:([0-9.]+)/i',$strWlan0,$result);
		$strIPAddress = $result[1];
		preg_match('/Mask:([0-9.]+)/i',$strWlan0,$result);
		$strNetMask = $result[1];
		preg_match('/RX packets:(\d+)/',$strWlan0,$result);
		$strRxPackets = $result[1];
		preg_match('/TX packets:(\d+)/',$strWlan0,$result);
		$strTxPackets = $result[1];
		preg_match('/RX Bytes:(\d+ \(\d+.\d+ MiB\))/i',$strWlan0,$result);
		$strRxBytes = $result[1];
		preg_match('/TX Bytes:(\d+ \(\d+.\d+ [K|M|G]iB\))/i',$strWlan0,$result);
		$strTxBytes = $result[1];
		preg_match('/ESSID:\"([a-zA-Z0-9\s]+)\"/i',$strWlan0,$result);
		$strSSID = str_replace('"','',$result[1]);
		preg_match('/Access Point: ([0-9a-f:]+)/i',$strWlan0,$result);
		$strBSSID = $result[1];
		preg_match('/Bit Rate=([0-9]+ Mb\/s)/i',$strWlan0,$result);
		$strBitrate = $result[1];
		preg_match('/Tx-Power=([0-9]+ dBm)/i',$strWlan0,$result);
		$strTxPower = $result[1];
		preg_match('/Link Quality=([0-9]+\/[0-9]+)/i',$strWlan0,$result);
		$strLinkQuality = $result[1];
		preg_match('/Signal Level=(-[0-9]+ dBm)/i',$strWlan0,$result);
		$strSignalLevel = $result[1];
		if(strpos($strWlan0, "UP") !== false && strpos($strWlan0, "RUNNING") !== false) {
			$strStatus = '<span style="color:green">Interface is up</span>';
		} else {
			$strStatus = '<span style="color:red">Interface is down</span>';
		}
		if(isset($_POST['ifdown_wlan0'])) {
			exec('ifconfig wlan0 | grep -i running | wc -l',$test);
			if($test[0] == 1) {
				exec('sudo ifdown wlan0',$return);
			} else {
				echo 'Interface already down';
			}
		} elseif(isset($_POST['ifup_wlan0'])) {
			exec('ifconfig wlan0 | grep -i running | wc -l',$test);
			if($test[0] == 0) {
				exec('sudo ifup wlan0',$return);
			} else {
				echo 'Interface already up';
			}
		}
	echo '<div class="infobox">
<form action="/?page=wlan0_info" method="POST">
<input type="submit" value="ifdown wlan0" name="ifdown_wlan0" />
<input type="submit" value="ifup wlan0" name="ifup_wlan0" />
<input type="button" value="Refresh" onclick="document.location.reload(true)" />
</form>
<div class="infoheader">Wireless Information and Statistics</div>
<div id="intinfo"><div class="intheader">Interface Information</div>
Interface Name : wlan0<br />
Interface Status : ' . $strStatus . '<br />
IP Address : ' . $strIPAddress . '<br />
Subnet Mask : ' . $strNetMask . '<br />
Mac Address : ' . $strHWAddress . '<br />
<div class="intheader">Interface Statistics</div>
Received Packets : ' . $strRxPackets . '<br />
Received Bytes : ' . $strRxBytes . '<br /><br />
Transferred Packets : ' . $strTxPackets . '<br />
Transferred Bytes : ' . $strTxBytes . '<br />
</div>
<div id="wifiinfo">
<div class="intheader">Wireless Information</div>
Connected To : ' . $strSSID . '<br />
AP Mac Address : ' . $strBSSID . '<br />
Bitrate : ' . $strBitrate . '<br />
Transmit Power : ' . $strTxPower .'<br />
Link Quality : ' . $strLinkQuality . '<br />
Signal Level : ' . $strSignalLevel . '<br />
</div>
</div>
<div class="intfooter">Information provided by ifconfig and iwconfig</div>';
	break;

	case "wpa_conf":
		exec('sudo cat /etc/wpa_supplicant/wpa_supplicant.conf',$return);
		$ssid = array();
		$psk = array();
		foreach($return as $a) {
			if(preg_match('/SSID/i',$a)) {
				$arrssid = explode("=",$a);
				$ssid[] = str_replace('"','',$arrssid[1]);
			}
			if(preg_match('/\#psk/i',$a)) {
				$arrpsk = explode("=",$a);
				$psk[] = str_replace('"','',$arrpsk[1]);
			}
		}
		$numSSIDs = count($ssid);
		$output = '<form method="POST" action="/?page=wpa_conf" id="wpa_conf_form"><input type="hidden" id="Networks" name="Networks" /><div class="network" id="networkbox">';
		for($ssids = 0; $ssids < $numSSIDs; $ssids++) {
			$output .= '<div id="Networkbox'.$ssids.'" class="NetworkBoxes">Network '.$ssids.' <input type="button" value="Delete" onClick="DeleteNetwork('.$ssids.')" /></span><br />
<span class="tableft" id="lssid0">SSID :</span><input type="text" id="ssid0" name="ssid'.$ssids.'" value="'.$ssid[$ssids].'" onkeyup="CheckSSID(this)" /><br />
<span class="tableft" id="lpsk0">PSK :</span><input type="password" id="psk0" name="psk'.$ssids.'" value="'.$psk[$ssids].'" onkeyup="CheckPSK(this)" /></div>';
		}
		$output .= '</div><input type="submit" value="Scan for Networks" name="Scan" /><input type="button" value="Add Network" onClick="AddNetwork();" /><input type="submit" value="Save" name="SaveWPAPSKSettings" onmouseover="UpdateNetworks(this)" id="Save" disabled />
</form>';

	echo $output;
	echo '<script type="text/Javascript">UpdateNetworks()</script>';

	if(isset($_POST['SaveWPAPSKSettings'])) {
		$config = 'ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev
update_config=1

';
		$networks = $_POST['Networks'];
		for($x = 0; $x < $networks; $x++) {
			$network = '';
			$ssid = escapeshellarg($_POST['ssid'.$x]);
			$psk = escapeshellarg($_POST['psk'.$x]);
			exec('wpa_passphrase '.$ssid. ' ' . $psk,$network);
			foreach($network as $b) {
				$config .= "$b
";
			}
		}
		exec("echo '$config' > /tmp/wifidata",$return);
		system('sudo cp /tmp/wifidata /etc/wpa_supplicant/wpa_supplicant.conf',$returnval);
		if($returnval == 0) {
			echo "Wifi Settings Updated Successfully";
		} else {
			echo "Wifi settings failed to be updated";
		}
	} elseif(isset($_POST['Scan'])) {
		$return = '';
		exec('sudo wpa_cli scan',$return);
		sleep(5);
		exec('sudo wpa_cli scan_results',$return);
		for($shift = 0; $shift < 4; $shift++ ) {
			array_shift($return);
		}
		echo "Networks found : <br />";
		foreach($return as $network) {
			$arrNetwork = preg_split("/[\t]+/",$network);
			$bssid = $arrNetwork[0];
			$channel = ConvertToChannel($arrNetwork[1]);
			$signal = $arrNetwork[2] . " dBm";
			$security = $arrNetwork[3];
			$ssid = $arrNetwork[4];
			echo '<input type="button" value="Connect to This network" onClick="AddScanned(\''.$ssid.'\')" />' . $ssid . " on channel " . $channel . " with " . $signal . "(".ConvertToSecurity($security)." Security)<br />";

		}
	}

	break;

	case "hostapd_conf":
		exec('cat /etc/hostapd/hostapd.conf',$return);
		exec('pidof hostapd | wc -l',$hostapdstatus);
		if($hostapdstatus[0] == 0) {
			$status = '<span class="red">hostapd is not running</span>';
		} else {
			$status = '<span class="green">hostapd is running</span>';
		}

		$arrConfig = array();
		$arrChannel = array('a','b','g');
		$arrSecurity = array( 1 => 'WPA', 2 => 'WPA2',3=> 'WPA+WPA2');
		$arrEncType = array('TKIP' => 'TKIP', 'CCMP' => 'CCMP', 'TKIP CCMP' => 'TKIP+CCMP');

		foreach($return as $a) {
			if($a[0] != "#") {
				$arrLine = explode("=",$a);
				$arrConfig[$arrLine[0]]=$arrLine[1];
			}
		}
		echo '<form action="/?page=save_hostapd_conf" method="POST">
HostAPD status : ' . $status . '<br />
Interface : <select name="interface">';
		exec("cat /proc/net/dev | tail -n -3 | awk -F :\  ' { print $1 } ' | tr -d ' '",$interfaces);
		foreach($interfaces as $int) {
			$select = '';
			if($int == $arrConfig['interface']) {
				$select = " selected";
			}
				echo '<option value="'.$int.'"'.$select.'>'.$int.'</option>';
		}
		echo'</select><br />
SSID : <input type="text" name="ssid" value="'.$arrConfig['ssid'].'" /><br />
Wireless Mode : <select name="hw_mode">';
		foreach($arrChannel as $Mode) {
			$select = '';
			if($arrConfig['hw_mode'] == $Mode) {
				$select = ' selected';
			}
			echo '<option value="'.$Mode.'"'.$select.'>'.$Mode.'</option>';
		}
		echo '</select><br />
Channel : <select name="channel">';
		for($channel = 1; $channel < 14; $channel++) {
			$select = '';
			if($channel == $arrConfig['channel']) {
				$select = " selected";
			}
		echo '<option value="'.$channel.'"'.$select.'>'.$channel.'</option>';
		}
		echo '</select><br />

Security type : <select name="wpa">';
		foreach($arrSecurity as $SecVal => $SecMode) {
			$select = '';
			if($SecVal == $arrConfig['wpa']) {
				$select = ' selected';
			}
			echo '<option value="'.$SecVal.'"'.$select.'>'.$SecMode.'</option>';
		}
		echo'</select><br />
Encryption Type : <select name="wpa_pairwise">';
		foreach($arrEncType as $EncConf => $Enc) {
			$select = '';
			if($Enc == $arrConfig['wpa_pairwise']) {
				$select = ' selected';
			}
			echo '<option value="'.$EncConf.'"'.$select.'>'.$Enc.'</option>';
		}
		echo'</select><br />
PSK : <input type="text" name="wpa_passphrase" value="'.$arrConfig['wpa_passphrase'].'" /> <br />
<input type="submit" name="SaveHostAPDSettings" value="Save Hostapd settings" /> ';
		if($hostapdstatus[0] == 0) {
			echo '<input type="submit" name="StartHotspot" value="Start Hotspot" />';
		} else {
			echo '<input type="submit" name="StopHotspot" value="Stop hotspot" />';
		}
		echo'<hr>
Advanced Settings<br />
Country Code : <input type="text" name="country_code" value="'.$arrConfig['country_code'].'" />
</form>';
break;

case "save_hostapd_conf":
	if(isset($_POST['SaveHostAPDSettings'])) {
		$config = 'driver=nl80211
ctrl_interface=/var/run/hostapd
ctrl_interface_group=0
beacon_int=100
auth_algs=1
wpa_key_mgmt=WPA-PSK
';
		$config .= "interface=".$_POST['interface']."
";
		$config .= "ssid=".$_POST['ssid']."
";
		$config .= "hw_mode=".$_POST['hw_mode']."
";
		$config .= "channel=".$_POST['channel']."
";
$config .= "wpa=".$_POST['wpa']."
";
$config .='wpa_passphrase='.$_POST['wpa_passphrase'].'
';
$config .="wpa_pairwise=".$_POST['wpa_pairwise']."
";
$config .="country_code=".$_POST['country_code'];
	exec("echo '$config' > /tmp/hostapddata",$return);
	system("sudo cp /tmp/hostapddata /etc/hostapd/hostapd.conf",$return);
	if($return == 0) {
		echo "Wifi Hotspot settings saved";
	} else {
		echo "Wifi Hotspot settings failed to be saved";
	}

} elseif(isset($_POST['StartHotspot'])) {
	echo "Attempting to start hotspot";
	exec('sudo /etc/init.d/hostapd start',$return);
	foreach($return as $line) {
		echo $line."<br />";
	}
} elseif(isset($_POST['StopHotspot'])) {
	echo "Attempting to stop hotspot";
	exec('sudo /etc/init.d/hostapd stop',$return);
	foreach($return as $line) {
		echo $line."<br />";
	}
}
	break;

}


echo '
</div>
<div class="footer">
</div>
<!-- Bootstrap core JavaScript -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
</body>
</html>';
?>
