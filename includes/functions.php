<?

/**
*
* @param string $input
* @param string $string
* @param int $offset
* @param string $separator
* @return $string
*/
function GetDistString( $input,$string,$offset,$separator ) {
	$string = substr( $input,strpos( $input,$string )+$offset,strpos( substr( $input,strpos( $input,$string )+$offset ), $separator ) );
	return $string;
}

/**
*
* @param array $arrConfig
* @return $config
*/
function ParseConfig( $arrConfig ) {
	$config = array();
	foreach( $arrConfig as $line ) {
		if( $line[0] != "#" ) {
			$arrLine = explode( "=",$line );
			$config[$arrLine[0]] = $arrLine[1];
		}
	}
	return $config;
}

/**
*
* @param string $freq
* @return $channel
*/
function ConvertToChannel( $freq ) {

	$base = 2412;
	$channel = 1;
	for( $x = 0; $x < 13; $x++ ) {
		if( $freq != $base ) {
			$base = $base + 5;
			$channel++;
		} else {
			return $channel;
		}
	}
	return "Invalid Channel";
}

/**
*
* @param string $security
* @return string
*/
function ConvertToSecurity( $security ) {

	switch( $security ) {
		case "[WPA2-PSK-CCMP][ESS]":
			return "WPA2-PSK (AES)";
		break;
		case "[WPA2-PSK-TKIP][ESS]":
			return "WPA2-PSK (TKIP)";
		break;
		case "[WPA-PSK-TKIP+CCMP][WPS][ESS]":
			return "WPA-PSK (TKIP/AES) with WPS";
		break;
		case "[WPA-PSK-TKIP+CCMP][WPA2-PSK-TKIP+CCMP][ESS]":
			return "WPA/WPA2-PSK (TKIP/AES)";
		break;
		case "[WPA-PSK-TKIP][ESS]":
			return "WPA-PSK (TKIP)";
		break;
		case "[WEP][ESS]":
			return "WEP";
		break;
	}
}

/**
*
*
*/
function DisplayDHCPConfig() {

	exec( 'cat '. RASPI_DNSMASQ_CONFIG, $return );
	$conf = ParseConfig($return);
	$arrRange = explode( ",", $conf['dhcp-range'] );
	$RangeStart = $arrRange[0];
	$RangeEnd = $arrRange[1];
	$RangeMask = $arrRange[2];
	preg_match( '/([0-9]*)([a-z])/i', $arrRange[3], $arrRangeLeaseTime );

	switch( $arrRangeLeaseTime[2] ) {
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

	exec( 'pidof dnsmasq | wc -l',$dnsmasq );

	if( $dnsmasq[0] == 0 ) {
		$status = '<div class="alert alert-warning alert-dismissable">Dnsmasq is not running<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button></div>';
	} else {
		$status = '<div class="alert alert-success alert-dismissable">Dnsmasq is running<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button></div>';
	}

	echo '
	<div class="row">
	<div class="col-lg-12">
    	<div class="panel panel-primary">           
			<div class="panel-heading"><i class="fa fa-exchange fa-fw"></i> Configure DHCP
            </div>
        <!-- /.panel-heading -->
        <div class="panel-body">
        <!-- Nav tabs -->
            <ul class="nav nav-tabs">
                <li class="active"><a href="#server-settings" data-toggle="tab">Server settings</a>
                </li>
                <li><a href="#client-list" data-toggle="tab">Client list</a>
                </li>
            </ul>
        <!-- Tab panes -->
        <div class="tab-content">
		<p>' .$status. '</p>
		<div class="tab-pane fade in active" id="server-settings">
		<h4>DHCP server settings</h4>
		<form method="POST" action="?page=dhcpd_conf">
		<div class="row">
			<div class="form-group col-md-4">
				<label for="code">Interface</label>
				<select class="form-control" name="interface">';
				
				exec( "cat /proc/net/dev | tail -n -3 | awk -F :\  ' { print $1 } ' | tr -d ' '", $interfaces );
				
				foreach( $interfaces as $int ) {
					$select = '';
					if( $int == $conf['interface'] ) {
						$select = " selected";
					}
						echo '<option value="'.$int.'"'.$select.'>'.$int.'</option>';
				}
				echo'</select>
			</div>
		</div>
		<div class="row">
			<div class="form-group col-md-4">
				<label for="code">Starting IP Address</label>
				<input type="text" class="form-control"name="RangeStart" value="'.$RangeStart.'" />
			</div>
		</div>

		<div class="row">
			<div class="form-group col-md-4">
				<label for="code">Ending IP Address</label>
				<input type="text" class="form-control" name="RangeEnd" value="'.$RangeEnd.'" />
			</div>
		</div>

		<div class="row">
			<div class="form-group col-xs-2 col-sm-2">
				<label for="code">Lease Time</label>
				<input type="text" class="form-control" name="RangeLeaseTime" value="'.$arrRangeLeaseTime[1].'" />
			</div>
			<div class="col-xs-2 col-sm-2">
				<label for="code">Interval</label>
				<select name="RangeLeaseTimeUnits" class="form-control" ><option value="m"'.$mselected.'>Minutes</option><option value="h"'.$hselected.'>Hours</option><option value="d"'.$dselected.'>Days</option><option value="infinite">Infinite</option></select> 
			</div>
		</div>

		<input type="submit" class="btn btn-outline btn-primary" value="Save settings" name="savedhcpdsettings" /> ';
		
		if ( $dnsmasq[0] == 0 ) {
			echo'<input type="submit" class="btn btn-success" value="Start dnsmasq" name="startdhcpd" />';
		} else {
			echo '<input type="submit" class="btn btn-warning" value="Stop dnsmasq" name="stopdhcpd" />';
		}
		
		echo'
		</form>
		</div><!-- /.tab-pane -->
		
		<div class="tab-pane fade in" id="client-list">
		<h4>Client list</h4>
		<div class="col-lg-12">
			<div class="panel panel-default">
			<div class="panel-heading">
				Active DHCP leases
			</div>
			<!-- /.panel-heading -->
			<div class="panel-body">
				<div class="table-responsive">
					<table class="table table-hover">
						<thead>
							<tr>
								<th>Expire time</th>
								<th>MAC Address</th>
								<th>IP Address</th>
								<th>Host name</th>
								<th>Client ID</th>
								</tr>
						</thead>
						<tbody>
							<tr>';
								exec( 'cat ' . RASPI_DNSMASQ_LEASES, $leases );
								foreach( $leases as $lease ) {
									$lease_items = explode(' ', $lease);
									foreach( $lease_items as $lease_item ) {
										echo '<td>' . $lease_item . '</td>';
									}
									echo '</tr>';
								};
							echo '
							</tr>
						</tbody>
					</table>
				</div><!-- /.table-responsive -->
			</div><!-- /.panel-body -->
			</div><!-- /.panel -->
		</div><!-- /.col-lg-6 -->';

		if( isset( $_POST['savedhcpdsettings'] ) ) {
			$config = 'interface='.$_POST['interface'].'
			dhcp-range='.$_POST['RangeStart'].','.$_POST['RangeEnd'].',255.255.255.0,'.$_POST['RangeLeaseTime'].''.$_POST['RangeLeaseTimeUnits'];
			exec( 'echo "'.$config.'" > /tmp/dhcpddata',$temp );
			system( 'sudo cp /tmp/dhcpddata '. RASPI_DNSMASQ_CONFIG, $return );
			
			if( $return == 0 ) {
				echo "Dnsmasq configuration updated successfully";
			} else {
				echo "Dnsmasq configuration failed to be updated";
			}
		}

		if( isset( $_POST['startdhcpd'] ) ) {
			$line = system('sudo /etc/init.d/dnsmasq start',$return);
			echo "Attempting to start dnsmasq";
		}

		if( isset($_POST['stopdhcpd'] ) ) {
			$line = system('sudo /etc/init.d/dnsmasq stop',$return);
			echo "Stopping dnsmasq";
		}
		echo '
		</div><!-- /.tab-pane -->
		</div><!-- /.tab-content -->
		</div><!-- ./ Panel body -->
		<div class="panel-footer"> Information provided by Dnsmasq</div>
        </div><!-- /.panel-primary -->    
		</div><!-- /.col-lg-12 -->
	</div><!-- /.row -->
	';
}

/**
*
*
*/
function DisplayDashboard(){

	exec( 'ifconfig wlan0', $return );
	exec( 'iwconfig wlan0', $return );

	$strWlan0 = implode( " ", $return );
	$strWlan0 = preg_replace( '/\s\s+/', ' ', $strWlan0 );

	// Parse results from ifconfig/iwconfig
	preg_match( '/HWaddr ([0-9a-f:]+)/i',$strWlan0,$result );
	$strHWAddress = $result[1];
	preg_match( '/inet addr:([0-9.]+)/i',$strWlan0,$result );
	$strIPAddress = $result[1];
	preg_match( '/Mask:([0-9.]+)/i',$strWlan0,$result );
	$strNetMask = $result[1];
	preg_match( '/RX packets:(\d+)/',$strWlan0,$result );
	$strRxPackets = $result[1];
	preg_match( '/TX packets:(\d+)/',$strWlan0,$result );
	$strTxPackets = $result[1];
	preg_match( '//RX bytes:(\d+)/i',$strWlan0,$result );
	$strRxBytes = $result[1];
	preg_match( '/TX Bytes:(\d+ \(\d+.\d+ [K|M|G]iB\))/i',$strWlan0,$result );
	$strTxBytes = $result[1];
	preg_match( '/ESSID:\"([a-zA-Z0-9\s]+)\"/i',$strWlan0,$result );
	$strSSID = str_replace( '"','',$result[1] );
	preg_match( '/Access Point: ([0-9a-f:]+)/i',$strWlan0,$result );
	$strBSSID = $result[1];
	preg_match( '/Bit Rate=([0-9]+ Mb\/s)/i',$strWlan0,$result );
	$strBitrate = $result[1];
	preg_match( '/Tx-Power=([0-9]+ dBm)/i',$strWlan0,$result );
	$strTxPower = $result[1];
	preg_match( '/Link Quality=([0-9]+)/i',$strWlan0,$result );
	$strLinkQuality = $result[1];
	preg_match( '/Signal Level=([0-9]+)/i',$strWlan0,$result );
	$strSignalLevel = $result[1];
	preg_match('/Frequency:(\d+.\d+ GHz)/i',$strWlan0,$result);
	$strFrequency = $result[1];

	if(strpos( $strWlan0, "UP" ) !== false && strpos( $strWlan0, "RUNNING" ) !== false ) {
		$strStatus = '<span style="color:green">Interface is <strong>up</strong></span>';
	} else {
		$strStatus = '<span style="color:red">Interface is <strong>down</strong></span>';
	}
	if( isset($_POST['ifdown_wlan0']) ) {
		exec( 'ifconfig wlan0 | grep -i running | wc -l',$test );
		if($test[0] == 1) {
			exec( 'sudo ifdown wlan0',$return );
		} else {
			echo 'Interface already down';
		}
	} elseif( isset($_POST['ifup_wlan0']) ) {
		exec( 'ifconfig wlan0 | grep -i running | wc -l',$test );
		if($test[0] == 0) {
			exec( 'sudo ifup wlan0',$return );
		} else {
			echo 'Interface already up';
		}
	}
	?>
	<div class="row">
	    <div class="col-lg-12">
	        <div class="panel panel-primary">
	        	<div class="panel-heading"><i class="fa fa-dashboard fa-fw"></i> Dashboard 	</div>
	            <div class="panel-body">
	                <div class="row">
	                    <div class="col-md-6">
	                        <div class="panel panel-default">
		                        <div class="panel-body">

		                        <h4>Interface Information</h4>
		                        Interface Name : wlan0<br />
								Interface Status : <?php echo $strStatus ?><br />
								IP Address : <?php echo $strIPAddress ?><br />
								Subnet Mask : <?php echo $strNetMask ?><br />
								Mac Address : <?php echo $strHWAddress ?><br />

		                        <h4>Interface Statistics</h4>
		                        Received Packets : <?php echo $strRxPackets ?><br />
								Received Bytes : <?php echo $strRxBytes ?><br /><br />
								Transferred Packets : <?php echo $strTxPackets ?><br />
								Transferred Bytes : <?php echo $strTxBytes ?><br />

								</div><!-- /.panel-body -->
							</div><!-- /.panel-default -->
	                    </div><!-- /.col-md-6 -->

	                    <div class="col-md-6">
	                        <div class="panel panel-default">
		                        <div class="panel-body wireless">
	                        	<h4>Wireless Information</h4>
	                        	Connected To : <?php echo $strSSID ?><br />
								AP Mac Address : <?php echo $strBSSID ?><br />
								Bitrate : <?php echo $strBitrate ?><br />
								Transmit Power : <?php echo $strTxPower ?><br />
								Frequency : <?php echo $strFrequency ?><br />
								Link Quality : 
								<div class="progress">
								  <div class="progress-bar progress-bar-info progress-bar-striped active" role="progressbar" aria-valuenow="<?php echo $strLinkQuality ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $strLinkQuality ?>%;">
								    <?php echo $strLinkQuality ?>%
								  </div>
								</div>
								Signal Level :
								<div class="progress">
								  <div class="progress-bar progress-bar-info progress-bar-striped active" role="progressbar" aria-valuenow="<?php echo $strSignalLevel ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $strSignalLevel ?>%;">
								    <?php echo $strSignalLevel ?>%
								  </div>
								</div>
	                        	</div><!-- /.panel-body -->
							</div><!-- /.panel-default -->

	                    </div><!-- /.col-md-6 -->
	                </div>

	                <div class="col-lg-12">
			        	 <div class="row">
				            <form action="/?page=wlan0_info" method="POST">
				            <input type="submit" class="btn btn-success" value="Start wlan0" name="ifup_wlan0" />
							<input type="submit" class="btn btn-warning" value="Stop wlan0" name="ifdown_wlan0" />
							<input type="button" class="btn btn-outline btn-primary" value="Refresh" onclick="document.location.reload(true)" />
							</form>
						</div>
			        </div>

		            </div><!-- /.panel-body -->
		            <div class="panel-footer">Information provided by ifconfig and iwconfig</div>
		        </div><!-- /.panel-default -->
		    </div><!-- /.col-lg-12 -->
		</div><!-- /.row -->
		<?php 
}

/**
*
*
*/
function DisplayWPAConfig(){
	exec('sudo cat ' . RASPI_WPA_SUPPLICANT_CONFIG, $return);
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
		$output .= '</div><input type="submit" class="btn btn-primary" value="Scan for Networks" name="Scan" /> <input type="button" class="btn btn-primary" value="Add Network" onClick="AddNetwork();" /> <input type="submit" class="btn btn-primary" value="Save" name="SaveWPAPSKSettings" onmouseover="UpdateNetworks(this)" id="Save" disabled />
		</form>';

	echo $output;
	echo '<script type="text/Javascript">UpdateNetworks()</script>';

	if( isset($_POST['SaveWPAPSKSettings']) ) {
		$config = 'ctrl_interface=DIR='. RASPI_WPA_CTRL_INTERFACE .' GROUP=netdev update_config=1';
		$networks = $_POST['Networks'];
		for( $x = 0; $x < $networks; $x++ ) {
			$network = '';
			$ssid = escapeshellarg( $_POST['ssid'.$x] );
			$psk = escapeshellarg( $_POST['psk'.$x] );
			exec( 'wpa_passphrase '.$ssid. ' ' . $psk,$network );
			foreach( $network as $b ) {
				$config .= "$b
			";
			}
		}

		exec( "echo '$config' > /tmp/wifidata", $return );
		system( 'sudo cp /tmp/wifidata ' . RASPI_WPA_SUPPLICANT_CONFIG, $returnval );
		if( $returnval == 0 ) {
			echo "Wifi settings updated successfully";
		} else {
			echo "Wifi settings failed to be updated";
		}

	} elseif( isset($_POST['Scan']) ) {
		$return = '';
		exec( 'sudo wpa_cli scan',$return );
		sleep(5);
		exec( 'sudo wpa_cli scan_results',$return );
		for( $shift = 0; $shift < 4; $shift++ ) {
			array_shift($return);
		}
		echo "Networks found : <br />";
		foreach( $return as $network ) {
			$arrNetwork = preg_split("/[\t]+/",$network);
			$bssid = $arrNetwork[0];
			$channel = ConvertToChannel($arrNetwork[1]);
			$signal = $arrNetwork[2] . " dBm";
			$security = $arrNetwork[3];
			$ssid = $arrNetwork[4];
			echo '<input type="button" value="Connect to This network" onClick="AddScanned(\''.$ssid.'\')" />' . $ssid . " on channel " . $channel . " with " . $signal . "(".ConvertToSecurity($security)." Security)<br />";

		}
	}
}

/**
*
*
*/
function DisplayHostAPDConfig(){

	exec( 'cat '. RASPI_HOSTAPD_CONFIG, $return );
	exec( 'pidof hostapd | wc -l', $hostapdstatus);

	if( $hostapdstatus[0] == 0 ) {
		$status = '<div class="alert alert-warning alert-dismissable">HostAPD is not running
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button></div>';
	} else {
		$status = '<div class="alert alert-success alert-dismissable">HostAPD is running
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button></div>';
	}

	$arrConfig = array();
	$arrChannel = array('a','b','g');
	$arrSecurity = array( 1 => 'WPA', 2 => 'WPA2',3=> 'WPA+WPA2');
	$arrEncType = array('TKIP' => 'TKIP', 'CCMP' => 'CCMP', 'TKIP CCMP' => 'TKIP+CCMP');

	foreach( $return as $a ) {
		if( $a[0] != "#" ) {
			$arrLine = explode( "=",$a) ;
			$arrConfig[$arrLine[0]]=$arrLine[1];
		}
	};
	?>
	<div class="row">
	<div class="col-lg-12">
    	<div class="panel panel-primary">           
			<div class="panel-heading"><i class="fa fa-dot-circle-o fa-fw"></i> Configure hotspot
            </div>
        <!-- /.panel-heading -->
        <div class="panel-body">
        	<!-- Nav tabs -->
            <ul class="nav nav-tabs">
                <li class="active"><a href="#basic" data-toggle="tab">Basic</a>
                </li>
                <li><a href="#security" data-toggle="tab">Security</a>
                </li>
                <li><a href="#advanced" data-toggle="tab">Advanced</a>
                </li>
            </ul>

            <!-- Tab panes -->
           	<div class="tab-content">
           		<p><?php echo $status; ?></p>
            	<div class="tab-pane fade in active" id="basic">
            		
            		<h4>Basic settings</h4>
					<form role="form" action="/?page=save_hostapd_conf" method="POST">
					<div class="row">
							<div class="form-group col-md-4">
						<label for="code">Interface</label>
						<select class="form-control" name="interface">
						<?php
							exec("cat /proc/net/dev | tail -n -3 | awk -F :\  ' { print $1 } ' | tr -d ' '", $interfaces);
							foreach( $interfaces as $int ) {
								$select = '';
								if( $int == $arrConfig['interface'] ) {
									$select = " selected";
								}
								echo '<option value="'.$int.'"'.$select.'>'.$int.'</option>';
							}
						?>
						</select>
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md-4">
							<label for="code">SSID</label>
							<input type="text" class="form-control" name="ssid" value="<?php echo $arrConfig['ssid']; ?>" />
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md-4">
							<label for="code">Wireless Mode</label>
							<select class="form-control" name="hw_mode">
							<?php
								foreach( $arrChannel as $Mode ) {
									$select = '';
									if( $arrConfig['hw_mode'] == $Mode ) {
										$select = ' selected';
									}
									echo '<option value="'.$Mode.'"'.$select.'>'.$Mode.'</option>';
								}
							?>
							</select>
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md-4">
						<label for="code">Channel</label>
							<select class="form-control" name="channel">'
							<?php
								for( $channel = 1; $channel < 14; $channel++ ) {
									$select = '';
									if( $channel == $arrConfig['channel'] ) {
										$select = " selected";
									}
									echo '<option value="'.$channel.'"'.$select.'>'.$channel.'</option>';
								}
							?>
							</select>
						</div>
					</div>	
				</div>
				<div class="tab-pane fade" id="security">
            		<h4>Security settings</h4>
            		<div class="row">
						<div class="form-group col-md-4">
            			<label for="code">Security type</label>
                		<select class="form-control" name="wpa">
                			<?php 
							foreach( $arrSecurity as $SecVal => $SecMode ) {
								$select = '';
								if( $SecVal == $arrConfig['wpa'] ) {
									$select = ' selected';
								}
								echo '<option value="'.$SecVal.'"'.$select.'>'.$SecMode.'</option>';
							}
							?>
						</select>
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md-4">
						<label for="code">Encryption Type</label>
						<select class="form-control" name="wpa_pairwise">
						<?php
							foreach( $arrEncType as $EncConf => $Enc ) {
								$select = '';
								if( $Enc == $arrConfig['wpa_pairwise'] ) {
									$select = ' selected';
								}
								echo '<option value="'.$EncConf.'"'.$select.'>'.$Enc.'</option>';
							} ?>
						</select>
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md-4">
						<label for="code">PSK</label>
						<input type="text" class="form-control" name="wpa_passphrase" value="<?php echo $arrConfig['wpa_passphrase'] ?>" />
						</div>
					</div>
            	</div>
				<div class="tab-pane fade in" id="advanced">
					<h4>Advanced settings</h4>
					<div class="row">
						<div class="form-group col-md-4">
						<label for="code">Country Code</label>
						<input type="text" class="form-control" name="country_code" value="<?php echo $arrConfig['country_code'] ?>" />
						</div>
					</div>
				</div>

				<input type="submit" class="btn btn-outline btn-primary" name="SaveHostAPDSettings" value="Save settings" />
				<?php 
				if($hostapdstatus[0] == 0) {
					echo '<input type="submit" class="btn btn-success" name="StartHotspot" value="Start hotspot" />';
				} else {
					echo '<input type="submit" class="btn btn-warning" name="StopHotspot" value="Stop hotspot" />';
				};
				?>
				</form>
		</div><!-- ./ Panel body -->
    </div><!-- /.panel-primary -->
    <div class="panel-footer"> Information provided by hostapd</div>
</div><!-- /.col-lg-12 -->
</div><!-- /.row -->
<?php 
}


/**
*
*
*/
function DisplayOpenVPNConfig() {

	exec( 'cat '. RASPI_OPENVPN_CLIENT_CONFIG, $returnClient );
	exec( 'cat '. RASPI_OPENVPN_SERVER_CONFIG, $returnServer );
	exec( 'pidof openvpn | wc -l', $openvpnstatus);

	if( $openvpnstatus[0] == 0 ) {
		$status = '<div class="alert alert-warning alert-dismissable">OpenVPN is not running
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button></div>';
	} else {
		$status = '<div class="alert alert-success alert-dismissable">OpenVPN is running
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button></div>';
	}

	// parse client settings
	foreach( $returnClient as $a ) {
		if( $a[0] != "#" ) {
			$arrLine = explode( " ",$a) ;
			$arrClientConfig[$arrLine[0]]=$arrLine[1];
		}
	}

	// parse server settings
	foreach( $returnServer as $a ) {
		if( $a[0] != "#" ) {
			$arrLine = explode( " ",$a) ;
			$arrServerConfig[$arrLine[0]]=$arrLine[1];
		}
	}
	?>
	<div class="row">
	<div class="col-lg-12">
    	<div class="panel panel-primary">           
			<div class="panel-heading"><i class="fa fa-lock fa-fw"></i> Configure OpenVPN 
            </div>
        <!-- /.panel-heading -->
        <div class="panel-body">
        	<!-- Nav tabs -->
            <ul class="nav nav-tabs">
                <li class="active"><a href="#openvpnclient" data-toggle="tab">Client settings</a>
                </li>
                <li><a href="#openvpnserver" data-toggle="tab">Server settings</a>
                </li>
            </ul>
            <!-- Tab panes -->
           	<div class="tab-content">
           		<p><?php echo $status; ?></p>
            	<div class="tab-pane fade in active" id="openvpnclient">
            		
            		<h4>Client settings</h4>
					<form role="form" action="/?page=save_hostapd_conf" method="POST">

					<div class="row">
						<div class="form-group col-md-4">
	                        <label>Select OpenVPN configuration file (.ovpn)</label>
	                        <input type="file" name="openvpn-config">
	                    </div>
					</div>
					<div class="row">
						<div class="form-group col-md-4">
							<label for="code">Client Log</label>
							<input type="text" class="form-control" id="disabledInput" name="log-append" type="text" placeholder="<?php echo $arrClientConfig['log-append']; ?>" disabled />
						</div>
					</div>
				</div>
				<div class="tab-pane fade" id="openvpnserver">
            		<h4>Server settings</h4>
            		<div class="row">
						<div class="form-group col-md-4">
            			<label for="code">Port</label> 
            			<input type="text" class="form-control" name="openvpn_port" value="<?php echo $arrServerConfig['port'] ?>" />
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md-4">
						<label for="code">Protocol</label>
						<input type="text" class="form-control" name="openvpn_proto" value="<?php echo $arrServerConfig['proto'] ?>" />
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md-4">
						<label for="code">Root CA certificate</label>
						<input type="text" class="form-control" name="openvpn_rootca" placeholder="<?php echo $arrServerConfig['ca']; ?>" disabled />
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md-4">
						<label for="code">Server certificate</label>
						<input type="text" class="form-control" name="openvpn_cert" placeholder="<?php echo $arrServerConfig['cert']; ?>" disabled />
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md-4">
						<label for="code">Diffie Hellman parameters</label>
						<input type="text" class="form-control" name="openvpn_dh" placeholder="<?php echo $arrServerConfig['dh']; ?>" disabled />
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md-4">
						<label for="code">KeepAlive</label>
						<input type="text" class="form-control" name="openvpn_keepalive" value="<?php echo $arrServerConfig['keepalive']; ?>" />
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md-4">
						<label for="code">Server log</label>
						<input type="text" class="form-control" name="openvpn_status" placeholder="<?php echo $arrServerConfig['status']; ?>" disabled />
						</div>
					</div>
            	</div>
				<input type="submit" class="btn btn-outline btn-primary" name="SaveOpenVPNSettings" value="Save settings" />
				<?php
				if($hostapdstatus[0] == 0) {
					echo '<input type="submit" class="btn btn-success" name="StartOpenVPN" value="Start OpenVPN" />';
				} else {
					echo '<input type="submit" class="btn btn-warning" name="StopOpenVPN" value="Stop OpenVPN" />';
				}
				?>
				</form>
		</div><!-- /.panel-body -->
    </div><!-- /.panel-primary -->
    <div class="panel-footer"> Information provided by openvpn</div>
</div><!-- /.col-lg-12 -->
</div><!-- /.row -->
<?php
}

/**
*
*
*/
function DisplayTorProxyConfig(){

	exec( 'cat '. RASPI_TORPROXY_CONFIG, $return );
	exec( 'pidof tor | wc -l', $torproxystatus);

	if( $torproxystatus[0] == 0 ) {
		$status = '<div class="alert alert-warning alert-dismissable">TOR is not running
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button></div>';
	} else {
		$status = '<div class="alert alert-success alert-dismissable">TOR is running
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button></div>';
	}

	foreach( $return as $a ) {
		if( $a[0] != "#" ) {
			$arrLine = explode( " ",$a) ;
			$arrConfig[$arrLine[0]]=$arrLine[1];
		}
	}

	?>
	<div class="row">
	<div class="col-lg-12">
    	<div class="panel panel-primary">           
			<div class="panel-heading"><i class="fa fa-eye-slash fa-fw"></i> Configure TOR proxy
            </div>
        <!-- /.panel-heading -->
        <div class="panel-body">
        	<!-- Nav tabs -->
            <ul class="nav nav-tabs">
                <li class="active"><a href="#basic" data-toggle="tab">Basic</a>
                </li>
                <li><a href="#relay" data-toggle="tab">Relay</a>
                </li>
            </ul>

            <!-- Tab panes -->
           	<div class="tab-content">
           		<p><?php echo $status; ?></p>

            	<div class="tab-pane fade in active" id="basic">
            		<h4>Basic settings</h4>
					<form role="form" action="/?page=save_hostapd_conf" method="POST">
					<div class="row">
						<div class="form-group col-md-4">
							<label for="code">VirtualAddrNetwork</label>
							<input type="text" class="form-control" name="virtualaddrnetwork" value="<?php echo $arrConfig['VirtualAddrNetwork']; ?>" />
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md-4">
							<label for="code">AutomapHostsSuffixes</label>
							<input type="text" class="form-control" name="automaphostssuffixes" value="<?php echo $arrConfig['AutomapHostsSuffixes']; ?>" />
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md-4">
							<label for="code">AutomapHostsOnResolve</label>
							<input type="text" class="form-control" name="automaphostsonresolve" value="<?php echo $arrConfig['AutomapHostsOnResolve']; ?>" />
						</div>
					</div>	
					<div class="row">
						<div class="form-group col-md-4">
							<label for="code">TransListenAddress</label>
							<input type="text" class="form-control" name="translistenaddress" value="<?php echo $arrConfig['TransListenAddress']; ?>" />
						</div>
					</div>	
					<div class="row">
						<div class="form-group col-md-4">
							<label for="code">DNSPort</label>
							<input type="text" class="form-control" name="dnsport" value="<?php echo $arrConfig['DNSPort']; ?>" />
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md-4">
							<label for="code">DNSListenAddress</label>
							<input type="text" class="form-control" name="dnslistenaddress" value="<?php echo $arrConfig['DNSListenAddress']; ?>" />
						</div>
					</div>
				</div>
				<div class="tab-pane fade" id="relay">
            		<h4>Relay settings</h4>
            		<div class="row">
						<div class="form-group col-md-4">
							<label for="code">ORPort</label>
							<input type="text" class="form-control" name="orport" value="<?php echo $arrConfig['ORPort']; ?>" />
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md-4">
							<label for="code">ORListenAddress</label>
							<input type="text" class="form-control" name="orlistenaddress" value="<?php echo $arrConfig['ORListenAddress']; ?>" />
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md-4">
							<label for="code">Nickname</label>
							<input type="text" class="form-control" name="nickname" value="<?php echo $arrConfig['Nickname']; ?>" />
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md-4">
							<label for="code">Address</label>
							<input type="text" class="form-control" name="address" value="<?php echo $arrConfig['Address']; ?>" />
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md-4">
							<label for="code">RelayBandwidthRate</label>
							<input type="text" class="form-control" name="relaybandwidthrate" value="<?php echo $arrConfig['RelayBandwidthRate']; ?>" />
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md-4">
							<label for="code">RelayBandwidthBurst</label>
							<input type="text" class="form-control" name="relaybandwidthburst" value="<?php echo $arrConfig['RelayBandwidthBurst']; ?>" />
						</div>
					</div>
            	</div>
		
				<input type="submit" class="btn btn-outline btn-primary" name="SaveTORProxySettings" value="Save settings" />
				<?php 
				if( $torproxystatus[0] == 0 ) {
					echo '<input type="submit" class="btn btn-success" name="StartTOR" value="Start TOR" />';
				} else {
					echo '<input type="submit" class="btn btn-warning" name="StopTOR" value="Stop TOR" />';
				};
				?>
				</form>
			</div><!-- /.tab-content -->
		</div><!-- /.panel-body -->
		<div class="panel-footer"> Information provided by tor</div>
    </div><!-- /.panel-primary -->
</div><!-- /.col-lg-12 -->
</div><!-- /.row -->
<?php 
}

/**
*
*
*/
function SaveHostAPDConfig(){
	if( isset($_POST['SaveHostAPDSettings']) ) {
		$config = 'driver=nl80211
		ctrl_interface='. RASPI_HOSTAPD_CTRL_INTERFACE .'
		ctrl_interface_group=0
		beacon_int=100
		auth_algs=1
		wpa_key_mgmt=WPA-PSK';

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

		exec( "echo '$config' > /tmp/hostapddata", $return );
		system( "sudo cp /tmp/hostapddata " . RASPI_HOSTAPD_CONFIG, $return );
		
			if( $return == 0 ) {
			echo "Wifi Hotspot settings saved";
		} else {
			echo "Wifi Hotspot settings failed to be saved";
		}
	} elseif( isset($_POST['SaveOpenVPNSettings']) ) {
		// TODO
	} elseif( isset($_POST['SaveTORProxySettings']) ) {
		// TODO
	} elseif( isset($_POST['StartHotspot']) ) {
		echo "Attempting to start hotspot";
		exec( 'sudo /etc/init.d/hostapd start', $return );
		foreach( $return as $line ) {
			echo $line."<br />";
		}
	} elseif( isset($_POST['StopHotspot']) ) {
		echo "Attempting to stop hotspot";
		exec( 'sudo /etc/init.d/hostapd stop', $return );
		foreach( $return as $line ) {
			echo $line."<br />";
		}
	} elseif( isset($_POST['StartOpenVPN']) ) {
		echo "Attempting to start openvpn";
		exec( 'sudo /etc/init.d/openvpn start', $return );
		foreach( $return as $line ) {
			echo $line."<br />";
		}
	} elseif( isset($_POST['StopOpenVPN']) ) {
		echo "Attempting to stop openvpn";
		exec( 'sudo /etc/init.d/openvpn stop', $return );
		foreach( $return as $line ) {
			echo $line."<br />";
		}
	} elseif( isset($_POST['StartTOR']) ) {
		echo "Attempting to start TOR";
		exec( 'sudo /etc/init.d/tor start', $return );
		foreach( $return as $line ) {
			echo $line."<br />";
		}
	} elseif( isset($_POST['StopTOR']) ) {
		echo "Attempting to stop TOR";
		exec( 'sudo /etc/init.d/tor stop', $return );
		foreach( $return as $line ) {
			echo $line."<br />";
		}
	}
}
?>

