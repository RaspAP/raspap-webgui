<?php

/**
*
*
*/
function DisplayWPAConfig(){
	$status = '';
	?>
	<div class="row">
		<div class="col-lg-12">
	    	<div class="panel panel-primary">           
				<div class="panel-heading"><i class="fa fa-signal fa-fw"></i> Configure client
	            </div>
		        <!-- /.panel-heading -->
		        <div class="panel-body">
		        	<?php echo $status; ?>
            		<h4>Client settings</h4>
					<div class="row">
						<div class="col-lg-12">

					<?php 	
					// save WPA settings
					if( isset($_POST['SaveWPAPSKSettings']) ) {

						$config = 'ctrl_interface=DIR='. RASPI_WPA_CTRL_INTERFACE .' GROUP=netdev
update_config=1
';
						$networks = $_POST['Networks'];
						for( $x = 0; $x < $networks; $x++ ) {
							$network = '';
							$ssid = escapeshellarg( $_POST['ssid'.$x] );
							$psk = escapeshellarg( $_POST['psk'.$x] );

							if ( strlen($psk) >2 ) {	
								exec( 'wpa_passphrase '.$ssid. ' ' . $psk,$network );
								foreach($network as $b) {
				$config .= "$b
";
								}
							}
						}
						exec( "echo '$config' > /tmp/wifidata", $return );
						system( 'sudo cp /tmp/wifidata ' . RASPI_WPA_SUPPLICANT_CONFIG, $returnval );
						if( $returnval == 0 ) {
							echo '<div class="alert alert-success alert-dismissable">Wifi settings updated successfully
									<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button></div>';
						} else {
							echo '<div class="alert alert-danger alert-dismissable">Wifi settings failed to be updated
									<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button></div>';
						}

					// scan networks
					} elseif( isset($_POST['Scan']) ) {
						$return = '';
						exec( 'sudo wpa_cli scan',$return );
						sleep(3);
						exec( 'sudo wpa_cli scan_results',$return );
						for( $shift = 0; $shift < 4; $shift++ ) {
							array_shift($return);
						}
						// display output
						echo '<form method="POST" action="?page=wpa_conf" id="wpa_conf_form"><input type="hidden" id="Networks" name="Networks" /><div class="network" id="networkbox"></div>';
						echo '<div class="row"><div class="col-lg-6"><input type="submit" class="btn btn-primary" value="Scan for networks" name="Scan" /> <input type="button" class="btn btn-primary" value="Add network" onClick="AddNetwork();" /> <input type="submit" class="btn btn-primary" value="Save" name="SaveWPAPSKSettings" onmouseover="UpdateNetworks(this)" id="Save" disabled /></div></div>';
						echo '<h4>Networks found</h4><div class="table-responsive"><table class="table table-hover">';
						echo '<thead><tr><th></th><th>SSID</th><th>Channel</th><th>Signal</th><th>Security</th></tr></thead><tbody>';
						foreach( $return as $network ) {
							$arrNetwork = preg_split("/[\t]+/",$network);
							$bssid = $arrNetwork[0];
							$channel = ConvertToChannel($arrNetwork[1]);
							$signal = $arrNetwork[2] . " dBm";
							$security = $arrNetwork[3];
							$ssid = $arrNetwork[4];
							echo '<tr><td><input type="button" class="btn btn-outline btn-primary" value="Connect" onClick="AddScanned(\''.$ssid.'\')" /></td> <td><strong>' . $ssid . "</strong></td> <td>" . $channel . "</td><td>" . $signal . "</td><td>". ConvertToSecurity($security) ."</td></tr>";
						}
						echo '</tbody></table>';

					} else {

						// default action, output configured network(s)
						exec(' sudo cat ' . RASPI_WPA_SUPPLICANT_CONFIG, $return);
						$ssid = array();
						$psk = array();

						foreach($return as $a) {
							if(preg_match('/SSID/i',$a)) {
								$arrssid = explode("=",$a);
								$ssid[] = str_replace('"','',$arrssid[1]);
							}
							if(preg_match('/psk/i',$a)) {
								$arrpsk = explode("=",$a);
								$psk[] = str_replace('"','',$arrpsk[1]);
							}
						}

						$numSSIDs = count($ssid);
						$output = '<form method="POST" action="?page=wpa_conf" id="wpa_conf_form"><input type="hidden" id="Networks" name="Networks" /><div class="network" id="networkbox">';
						
						if ( $numSSIDs > 0 ) {
							for( $ssids = 0; $ssids < $numSSIDs; $ssids++ ) {
								$output .= '<div id="Networkbox'.$ssids.'" class="NetworkBoxes">
									<div class="row"><div class="form-group col-md-4"><label for="code">Network '.$ssids.'</label></div></div>
									<div class="row"><div class="form-group col-md-4"><label for="code" id="lssid0">SSID</label><input type="text" class="form-control" id="ssid0" name="ssid'.$ssids.'" value="'.$ssid[$ssids].'" onkeyup="CheckSSID(this)" /></div></div>
									<div class="row"><div class="form-group col-md-4"><label for="code" id="lpsk0">PSK</label><input type="password" class="form-control" id="psk0" name="psk'.$ssids.'" value="'.$psk[$ssids].'" onkeyup="CheckPSK(this)" /></div></div>
									<div class="row"><div class="form-group col-md-4"><input type="button" class="btn btn-outline btn-primary" value="Delete" onClick="DeleteNetwork('.$ssids.')" /></div></div>';
						}
							$output .= '</div><!-- /#Networkbox -->';
						} else {
							$status = '<div class="alert alert-warning alert-dismissable">Not connected
									<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button></div>';
						}
						$output .= '<div class="row"><div class="col-lg-6"><input type="submit" class="btn btn-primary" value="Scan for networks" name="Scan" /> <input type="button" class="btn btn-primary" value="Add network" onClick="AddNetwork();" /> <input type="submit" class="btn btn-primary" value="Save" name="SaveWPAPSKSettings" onmouseover="UpdateNetworks(this)" id="Save" disabled />';
						$output .= '</form>'; 
						echo $output;
					}
					?>
					<script type="text/Javascript">UpdateNetworks(this)</script>
				</form>
				</div><!-- ./ Panel body -->
		    </div><!-- /.panel-primary -->
		</div><!-- /.col-lg-12 -->
	</div><!-- /.row -->
<?php
}

?>
