<?php

/**
*
*
*/
function DisplayWPAConfig(){
  $status = new StatusMessages();
  $scanned_networks = array();

  if( isset($_POST['SaveWPAPSKSettings']) ) {

    $config = 'ctrl_interface=DIR='. RASPI_WPA_CTRL_INTERFACE .' GROUP=netdev
update_config=1
';
    $networks = $_POST['Networks'];
    for( $x = 0; $x < $networks; $x++ ) {
      $network = '';
      $ssid = escapeshellarg( $_POST['ssid'.$x] );
      $protocol = $_POST['protocol'.$x];
      if ($protocol === 'Open') {
        $config .= "network={".PHP_EOL;
        $config .= "\tssid=\"".$ssid."\"".PHP_EOL;
        $config .= "\tkey_mgmt=NONE".PHP_EOL;
        $config .= "}".PHP_EOL;
      } else {
        $psk = escapeshellarg( $_POST['psk'.$x] );

        if ( strlen($psk) >2 ) {  
          exec( 'wpa_passphrase '.$ssid. ' ' . $psk,$network );
          foreach($network as $b) {
            $config .= "$b
";
          }
        }
      }
      error_log($config);
    }
    exec( "echo '$config' > /tmp/wifidata", $return );
    system( 'sudo cp /tmp/wifidata ' . RASPI_WPA_SUPPLICANT_CONFIG, $returnval );
    if( $returnval == 0 ) {
      $status->addMessage('Wifi settings updated successfully', 'success');
    } else {
      $status->addMessage('Wifi settings failed to be updated', 'danger');
    }
  } elseif( isset($_POST['Scan']) ) {
    $return = '';
    exec( 'sudo wpa_cli scan',$return );
    sleep(3);
    exec( 'sudo wpa_cli scan_results',$return );
    for( $shift = 0; $shift < 4; $shift++ ) {
      array_shift($return);
    }
    // display output
    foreach( $return as $network ) {
      $arrNetwork = preg_split("/[\t]+/",$network);
      $scanned_networks[] = array(
        'bssid' => $arrNetwork[0],
        'channel' => ConvertToChannel($arrNetwork[1]),
        'signal' => $arrNetwork[2] . " dBm",
        'security' => ConvertToSecurity($arrNetwork[3]),
        'ssid' => $arrNetwork[4]
      );
    }
    echo '</tbody></table>';
  }

//  // default action, output configured network(s)
//  exec(' sudo cat ' . RASPI_WPA_SUPPLICANT_CONFIG, $supplicant_return);
//  $ssid = array();
//  $psk = array();
//
//  foreach($supplicant_return as $a) {
//    if(preg_match('/SSID/i',$a)) {
//      $arrssid = explode("=",$a);
//      $ssid[] = str_replace('"','',$arrssid[1]);
//    }
//    if(preg_match('/psk/i',$a)) {
//      $arrpsk = explode("=",$a);
//      $psk[] = str_replace('"','',$arrpsk[1]);
//    }
//  }
//
//  $numSSIDs = count($ssid);




  // Find currently configured networks$
  exec(' sudo cat ' . RASPI_WPA_SUPPLICANT_CONFIG, $known_return);

  $known_networks = array();
  $network_id = null;

  foreach($known_return as $line) {
    error_log($line);
    if (preg_match('/network\s*=/', $line)) {
      $known_networks[] = array();
      $network_id = count($known_networks) - 1;
    } elseif ($network_id !== null) {
      if (preg_match('/^\s*}\s*$/', $line)) {
        $network_id = null;
      } elseif ($lineArr = preg_split('/\s*=\s*/', trim($line))) {
        switch(strtolower($lineArr[0])) {
          case 'ssid':
            $known_networks[$network_id]['ssid'] = trim($lineArr[1], '"');
            break;
          case 'psk':
            if (array_key_exists('passphrase', $known_networks[$network_id])) {
              break;
            }
          case '#psk':
            $known_networks[$network_id]['protocol'] = 'WPA';
          case 'wep_key0': // Untested
            $known_networks[$network_id]['passphrase'] = trim($lineArr[1], '"');
            break;
          case 'key_mgmt':
            if (! array_key_exists('passphrase', $known_networks[$network_id]) &&$lineArr[1] === 'NONE') {
              $known_networks[$network_id]['protocol'] = 'Open';
              $known_networks[$network_id]['passphrase'] = '(Open)';
            }
            break;
        }
      }
    }
  }
?>

  <div class="row">
    <div class="col-lg-12">
      <div class="panel panel-primary">           
        <div class="panel-heading"><i class="fa fa-signal fa-fw"></i> Configure client</div>
        <!-- /.panel-heading -->
        <div class="panel-body">
          <p><?php $status->showMessages(); ?></p>
          <h4>Client settings</h4>
          <div class="row">
            <div class="col-lg-12">

              <form method="POST" action="?page=wpa_conf" id="wpa_conf_form">
                <input type="hidden" id="Networks" name="Networks" />
                <div class="network" id="networkbox"></div>
                <div class="row">
                  <div class="col-lg-6">
                    <input type="submit" class="btn btn-primary" value="Scan for networks" name="Scan" />
                    <input type="button" class="btn btn-primary" value="Add network" onClick="AddNetwork();" />
                    <input type="submit" class="btn btn-primary" value="Save" name="SaveWPAPSKSettings" onmouseover="UpdateNetworks(this)" id="Save" disabled />
                  </div>
                </div>
                <h4>Networks found</h4>
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th></th>
                        <th>SSID</th>
                        <th>Channel</th>
                        <th>Signal</th>
                        <th>Security</th>
                      </tr>
                    </thead>
                    <tbody>
                    <?php foreach( $scanned_networks as $network ) { ?>
                      <tr>
                        <td><input type="button" class="btn btn-outline btn-primary" value="Connect" onClick="AddScanned('<?php echo $network['ssid'] ?>',<?php echo ($network['security'] === 'Open' ? 'true' : 'false')?>)" /></td>
                        <td><strong><?php echo $network['ssid'] ?></strong></td>
                        <td><?php echo $network['channel'] ?></td>
                        <td><?php echo $network['signal'] ?></td>
                        <td><?php echo $network['security'] ?></td>
                      </tr>
                    <?php } ?>
                    </tbody>
                  </table>
                </div>
              </form>
            </div>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <form method="POST" action="?page=wpa_conf" id="wpa_conf_form">
                <input type="hidden" id="Networks" name="Networks" />
                <div class="network" id="networkbox">
                <?php for ($ssids = 0; $ssids < count($known_networks); $ssids++) { ?>
                  <div id="Networkbox<?php echo $ssids ?>" class="NetworkBoxes">
                    <div class="row">
                      <div class="form-group col-md-4">
                        <label for="code">Network <?php echo $ssids ?></label>
                      </div>
                    </div>
                  <div class="row">
                    <div class="form-group col-md-4">
                      <label for="code" id="lssid0">SSID</label>
                      <input type="text" class="form-control" id="ssid0" name="ssid<?php echo $ssids ?>" value="<?php echo $known_networks[$ssids]['ssid'] ?>" onkeyup="CheckSSID(this)" />
                    </div>
                  </div>
                  <div class="row">
                    <div class="form-group col-md-4">
                      <label for="code" id="lpsk0">Protocol</label>
                      <input type="text" class="form-control" id="protocol0" name="protocol<?php echo $ssids ?>" value="<?php echo $known_networks[$ssids]['protocol'] ?>" />
                    </div>
                  </div>
                <?php if ($known_networks[$ssids]['protocol'] !== 'Open') { ?>
                  <div class="row">
                    <div class="form-group col-md-4">
                      <label for="code" id="lpsk0">PSK</label>
                      <input type="text" class="form-control" id="psk0" name="psk<?php echo $ssids ?>" value="<?php echo $known_networks[$ssids]['passphrase'] ?>" onkeyup="CheckPSK(this)" />
                    </div>
                  </div>
                <?php } ?>
                  <div class="row">
                    <div class="form-group col-md-4">
                      <input type="button" class="btn btn-outline btn-primary" value="Delete" onClick="DeleteNetwork('<?php echo $ssids?>')" />
                    </div>
                  </div>
                <?php } ?>
                </div><!-- /#Networkbox -->
            <div class="row">
              <div class="col-lg-6">
                <input type="submit" class="btn btn-primary" value="Scan for networks" name="Scan" />
                <input type="button" class="btn btn-primary" value="Add network" onClick="AddNetwork();" />
                <input type="submit" class="btn btn-primary" value="Save" name="SaveWPAPSKSettings" onmouseover="UpdateNetworks(this)" id="Save" disabled />
              </form>
              </div>
            </div>
            <script type="text/Javascript">UpdateNetworks(this)</script>
          </div>
        </div><!-- ./ Panel body -->
      </div><!-- /.panel-primary -->
    </div><!-- /.col-lg-12 -->
  </div><!-- /.row -->
<?php
}

?>
