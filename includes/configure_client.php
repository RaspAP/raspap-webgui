<?php

/**
*
*
*/
function DisplayWPAConfig(){
  $status = new StatusMessages();
  $scanned_networks = array();

  // Find currently configured networks
  exec(' sudo cat ' . RASPI_WPA_SUPPLICANT_CONFIG, $known_return);

  $network = null;
  $ssid = null;

  foreach($known_return as $line) {
    if (preg_match('/network\s*=/', $line)) {
      $network = array('visible' => false, 'configured' => true, 'connected' => false);
    } elseif ($network !== null) {
      if (preg_match('/^\s*}\s*$/', $line)) {
        $networks[$ssid] = $network;
        $network = null;
        $ssid = null;
      } elseif ($lineArr = preg_split('/\s*=\s*/', trim($line))) {
        switch(strtolower($lineArr[0])) {
          case 'ssid':
            $ssid = trim($lineArr[1], '"');
            break;
          case 'psk':
            if (array_key_exists('passphrase', $network)) {
              break;
            }
          case '#psk':
            $network['protocol'] = 'WPA';
          case 'wep_key0': // Untested
            $network['passphrase'] = trim($lineArr[1], '"');
            break;
          case 'key_mgmt':
            if (! array_key_exists('passphrase', $network) && $lineArr[1] === 'NONE') {
              $network['protocol'] = 'Open';
            }
            break;
        }
      }
    }
  }

  if ( isset($_POST['client_settings']) && CSRFValidate() ) {
    $tmp_networks = $networks;
    if ($wpa_file = fopen('/tmp/wifidata', 'w')) {
      fwrite($wpa_file, 'ctrl_interface=DIR=' . RASPI_WPA_CTRL_INTERFACE . ' GROUP=netdev' . PHP_EOL);
      fwrite($wpa_file, 'update_config=1' . PHP_EOL);

      foreach(array_keys($_POST) as $post) {
        if (preg_match('/delete(\d+)/', $post, $post_match)) {
          unset($tmp_networks[$_POST['ssid' . $post_match[1]]]);
        } elseif (preg_match('/update(\d+)/', $post, $post_match)) {
          // NB, at the moment, the value of protocol from the form may
          // contain HTML line breaks
          $tmp_networks[$_POST['ssid' . $post_match[1]]] = array(
            'protocol' => ( $_POST['protocol' . $post_match[1]] === 'Open' ? 'Open' : 'WPA' ),
            'passphrase' => $_POST['passphrase' . $post_match[1]],
            'configured' => true
          );
        }
      }

      $ok = true;
      foreach($tmp_networks as $ssid => $network) {
        if ($network['protocol'] === 'Open') {
          fwrite($wpa_file, "network={".PHP_EOL);
          fwrite($wpa_file, "\tssid=\"".$ssid."\"".PHP_EOL);
          fwrite($wpa_file, "\tkey_mgmt=NONE".PHP_EOL);
          fwrite($wpa_file, "}".PHP_EOL);
        } else {
          if (strlen($network['passphrase']) >=8 && strlen($network['passphrase']) <= 63) {
	    unset($wpa_passphrase);
            unset($line);
	    exec( 'wpa_passphrase '.escapeshellarg($ssid). ' ' . escapeshellarg($network['passphrase']),$wpa_passphrase );
            foreach($wpa_passphrase as $line) {
              fwrite($wpa_file, $line.PHP_EOL);
            }
          } else {
            $status->addMessage('WPA passphrase must be between 8 and 63 characters', 'danger');
            $ok = false;

          }
        }

      }

      if ($ok) {
        system( 'sudo cp /tmp/wifidata ' . RASPI_WPA_SUPPLICANT_CONFIG, $returnval );
        if( $returnval == 0 ) {
          exec('sudo wpa_cli reconfigure', $reconfigure_out, $reconfigure_return );
          if ($reconfigure_return == 0) {
            $status->addMessage('Wifi settings updated successfully', 'success');
            $networks = $tmp_networks;
          } else {
            $status->addMessage('Wifi settings updated but cannot restart (cannon execute "wpa_cli reconfigure")', 'danger');
          }
        } else {
          $status->addMessage('Wifi settings failed to be updated', 'danger');
        }
      }
    } else {
      $status->addMessage('Failed to updated wifi settings', 'danger');
    }
  }

  exec( 'sudo wpa_cli scan' );
  sleep(3);
  exec( 'sudo wpa_cli scan_results',$scan_return );
  for( $shift = 0; $shift < 2; $shift++ ) {
    array_shift($scan_return);
  }
  // display output
  foreach( $scan_return as $network ) {
    $arrNetwork = preg_split("/[\t]+/",$network);
    if (array_key_exists($arrNetwork[4], $networks)) {
      $networks[$arrNetwork[4]]['visible'] = true;
      $networks[$arrNetwork[4]]['channel'] = ConvertToChannel($arrNetwork[1]);
      // TODO What if the security has changed?
    } else {
      $networks[$arrNetwork[4]] = array(
        'configured' => false,
        'protocol' => ConvertToSecurity($arrNetwork[3]),
        'channel' => ConvertToChannel($arrNetwork[1]),
        'passphrase' => '',
        'visible' => true,
        'connected' => false
      );
    }
  }

  exec( 'iwconfig ' . RASPI_WIFI_CLIENT_INTERFACE, $iwconfig_return );
  foreach ($iwconfig_return as $line) {
    if (preg_match( '/ESSID:\"([^"]+)\"/i',$line,$iwconfig_ssid )) {
      $networks[$iwconfig_ssid[1]]['connected'] = true;
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
          <h4>Client settings for interface <?php echo RASPI_WIFI_CLIENT_INTERFACE ?></h4>
	<div class="btn-group btn-block">
	<a href=".?<?php echo $_SERVER['QUERY_STRING']; ?>" style="padding:10px;float: right;display: block;position: relative;margin-top: -55px;" class="col-md-2 btn btn-info" id="update">Rescan</a>
	</div>
          <form method="POST" action="?page=wpa_conf" name="wpa_conf_form">
            <?php CSRFToken() ?>
            <input type="hidden" name="client_settings" ?>
            <table class="table table-responsive table-striped">
              <tr>
                <th></th>
                <th>SSID</th>
                <th>Channel</th>
                <th>Security</th>
                <th>Passphrase</th>
                <th></th>
              </tr>
            <?php $index = 0; ?>
            <?php foreach ($networks as $ssid => $network) { ?>
              <tr>
                <td>
                <?php if ($network['configured']) { ?>
                <i class="fa fa-check-circle fa-fw"></i>
                <?php } ?>
                <?php if ($network['connected']) { ?>
                <i class="fa fa-exchange fa-fw"></i>
                <?php } ?>
                </td>
                <td>
                  <input type="hidden" name="ssid<?php echo $index ?>" value="<?php echo htmlentities($ssid) ?>" />
                  <?php echo $ssid ?>
                </td>
              <?php if ($network['visible']) { ?>
                <td><?php echo $network['channel'] ?></td>
              <?php } else { ?>
                <td><span class="label label-warning">X</span></td>
              <?php } ?>
                <td><input type="hidden" name="protocol<?php echo $index ?>" value="<?php echo $network['protocol'] ?>" /><?php echo $network['protocol'] ?></td>
              <?php if ($network['protocol'] === 'Open') { ?>
                <td><input type="hidden" name="passphrase<?php echo $index ?>" value="" />---</td>
              <?php } else { ?>
                <td><input type="text" class="form-control" name="passphrase<?php echo $index ?>" value="<?php echo $network['passphrase'] ?>" onKeyUp="CheckPSK(this, 'update<?php echo $index?>')" />
              <?php } ?>
                <td>
                  <div class="btn-group btn-block">
                  <?php if ($network['configured']) { ?>
                    <input type="submit" class="col-md-6 btn btn-warning" value="Update" id="update<?php echo $index ?>" name="update<?php echo $index ?>"<?php echo ($network['protocol'] === 'Open' ? ' disabled' : '')?> />
                  <?php } else { ?>
                    <input type="submit" class="col-md-6 btn btn-info" value="Add" id="update<?php echo $index ?>" name="update<?php echo $index ?>" <?php echo ($network['protocol'] === 'Open' ? '' : ' disabled')?> />
                  <?php } ?>
                    <input type="submit" class="col-md-6 btn btn-danger" value="Delete" name="delete<?php echo $index ?>"<?php echo ($network['configured'] ? '' : ' disabled')?> />
                  </div>
                </td>
              </tr>
              <?php $index += 1; ?>
            <?php } ?>
            </table>
          </form>
        </div><!-- ./ Panel body -->
        <div class="panel-footer"><strong>Note,</strong> WEP access points appear as 'Open'. RaspAP does not currently support connecting to WEP.</div>
      </div><!-- /.panel-primary -->
    </div><!-- /.col-lg-12 -->
  </div><!-- /.row -->
<?php
}

?>
