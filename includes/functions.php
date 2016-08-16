<?php

/**
*
* Add CSRF Token to form
*
*/
function CSRFToken() {
?>
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
<?php
}

/**
*
* Validate CSRF Token
*
*/
function CSRFValidate() {
  if ( hash_equals($_POST['csrf_token'], $_SESSION['csrf_token']) ) {
    return true;
  } else {
    error_log('CSRF violation');
    return false;
  }
}

/**
* Test whether array is associative
*/
function isAssoc($arr) {
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
function SelectorOptions($name, $options, $selected = null) {
  echo "<select class=\"form-control\" name=\"$name\">";
  foreach ( $options as $opt => $label) {
    $select = '';
    $key = isAssoc($options) ? $opt : $label;
    if( $key == $selected ) {
      $select = " selected";
    }
    echo "<option value=\"$key\"$select>$label</options>";
  }
  echo "</select>";
}

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
		$line = trim($line);
		if( $line != "" && $line[0] != "#" ) {
			$arrLine = explode( "=",$line );
			$config[$arrLine[0]] = ( count($arrLine) > 1 ? $arrLine[1] : true );
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
  $channel = ($freq - 2407)/5;
  if ($channel > 0 && $channel < 14) {
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
function ConvertToSecurity( $security ) {
  $options = array();
  preg_match_all('/\[([^\]]+)\]/s', $security, $matches);
  foreach($matches[1] as $match) {
    if (preg_match('/^(WPA\d?)/', $match, $protocol_match)) {
      $protocol = $protocol_match[1];
      $matchArr = explode('-', $match);
      if (count($matchArr) > 2) {
        $options[] = $protocol . ' ('. $matchArr[2] .')';
      } else {
        $options[] = $protocol;
      }
    }
  }

  if (count($options) === 0) {
    // This could also be WEP but wpa_supplicant doesn't have a way to determine
    // this.
    // And you shouldn't be using WEP these days anyway.
    return 'Open';
  } else {
    return implode('<br />', $options);
  }
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
	preg_match( '/RX bytes:(\d+ \(\d+.\d+ [K|M|G]iB\))/i',$strWlan0,$result );
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
		$status = '<div class="alert alert-success alert-dismissable">Interface is up
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button></div>';
		$wlan0up = true;
	} else {
		$status =  '<div class="alert alert-warning alert-dismissable">Interface is down
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button></div>';
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
	            	<p><?php echo $status; ?></p>
	                <div class="row">
	                    	<div class="col-md-6">
	                    	<div class="panel panel-default">
		            	<div class="panel-body">
		                	<h4>Interface Information</h4>
					<div class="info-item">Interface Name</div> wlan0</br>
					<div class="info-item">IP Address</div>     <?php echo $strIPAddress ?></br>
					<div class="info-item">Subnet Mask</div>    <?php echo $strNetMask ?></br>
					<div class="info-item">Mac Address</div>    <?php echo $strHWAddress ?></br></br>

		                	<h4>Interface Statistics</h4>
					<div class="info-item">Received Packets</div>    <?php echo $strRxPackets ?></br>
					<div class="info-item">Received Bytes</div>      <?php echo $strRxBytes ?></br></br>
					<div class="info-item">Transferred Packets</div> <?php echo $strTxPackets ?></br>
					<div class="info-item">Transferred Bytes</div>   <?php echo $strTxBytes ?></br>
				</div><!-- /.panel-body -->
				</div><!-- /.panel-default -->
	                    	</div><!-- /.col-md-6 -->

				<div class="col-md-6">
	                	<div class="panel panel-default">
		        	<div class="panel-body wireless">
	                        	<h4>Wireless Information</h4>
					<div class="info-item">Connected To</div>   <?php echo $strSSID ?></br>
					<div class="info-item">AP Mac Address</div> <?php echo $strBSSID ?></br>
					<div class="info-item">Bitrate</div>        <?php echo $strBitrate ?></br>
					<div class="info-item">Transmit Power</div> <?php echo $strTxPower ?></br>
					<div class="info-item">Frequency</div>      <?php echo $strFrequency ?></br></br>
					<div class="info-item">Link Quality</div>
						<div class="progress">
						<div class="progress-bar progress-bar-info progress-bar-striped active"
							role="progressbar"
							aria-valuenow="<?php echo $strLinkQuality ?>" aria-valuemin="0" aria-valuemax="100"
							style="width: <?php echo $strLinkQuality ?>%;"><?php echo $strLinkQuality ?>%
						</div>
						</div>
					<div class="info-item">Signal Level</div>
						<div class="progress">
						<div class="progress-bar progress-bar-info progress-bar-striped active"
							role="progressbar"
							aria-valuenow="<?php echo $strSignalLevel ?>" aria-valuemin="0" aria-valuemax="100"
							style="width: <?php echo $strSignalLevel ?>%;"><?php echo $strSignalLevel ?>%
						</div>
						</div>
				</div><!-- /.panel-body -->
				</div><!-- /.panel-default -->
	                    	</div><!-- /.col-md-6 -->
			</div><!-- /.row -->

	                <div class="col-lg-12">
			        	 <div class="row">
				            <form action="?page=wlan0_info" method="POST">
				            <?php if ( !$wlan0up ) {
				            	echo '<input type="submit" class="btn btn-success" value="Start wlan0" name="ifup_wlan0" />';
				            } else {
								echo '<input type="submit" class="btn btn-warning" value="Stop wlan0" name="ifdown_wlan0" />';
							}
							?>
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
					<form role="form" action="?page=save_hostapd_conf" method="POST">

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
					<form role="form" action="?page=save_hostapd_conf" method="POST">
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
function SaveTORAndVPNConfig(){
  if( isset($_POST['SaveOpenVPNSettings']) ) {
    // TODO
  } elseif( isset($_POST['SaveTORProxySettings']) ) {
    // TODO
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
