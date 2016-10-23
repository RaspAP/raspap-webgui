<?php

include_once( 'includes/status_messages.php' );

/**
*
*
*/
function DisplayHostAPDConfig(){

  $status = new StatusMessages();

  $arrConfig = array();
  $arrChannel = array('a','b','g');
  $arrSecurity = array( 1 => 'WPA', 2 => 'WPA2',3=> 'WPA+WPA2');
  $arrEncType = array('TKIP' => 'TKIP', 'CCMP' => 'CCMP', 'TKIP CCMP' => 'TKIP+CCMP');
  exec("ip -o link show | awk -F': ' '{print $2}'", $interfaces);


  if( isset($_POST['SaveHostAPDSettings']) ) {
    if (CSRFValidate()) {
      SaveHostAPDConfig($arrSecurity, $arrEncType, $arrChannel, $interfaces, $status);
    } else {
      error_log('CSRF violation');
    }
  } elseif( isset($_POST['StartHotspot']) ) {
    if (CSRFValidate()) {
      $status->addMessage('Attempting to start hotspot', 'info');
      exec( 'sudo /etc/init.d/hostapd start', $return );
      foreach( $return as $line ) {
        $status->addMessage($line, 'info');
      }
    } else {
      error_log('CSRF violation');
    }
  } elseif( isset($_POST['StopHotspot']) ) {
    if (CSRFValidate()) {
      $status->addMessage('Attempting to stop hotspot', 'info');
      exec( 'sudo /etc/init.d/hostapd stop', $return );
      foreach( $return as $line ) {
        $status->addMessage($line, 'info');
      }
    } else {
      error_log('CSRF violation');
    }
  }

  exec( 'cat '. RASPI_HOSTAPD_CONFIG, $return );
  exec( 'pidof hostapd | wc -l', $hostapdstatus);

  if( $hostapdstatus[0] == 0 ) {
    $status->addMessage('HostAPD is not running', 'warning');
  } else {
    $status->addMessage('HostAPD is running', 'success');
  }

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
        <div class="panel-heading"><i class="fa fa-dot-circle-o fa-fw"></i> Configure hotspot</div>
        <!-- /.panel-heading -->
        <div class="panel-body">
          <form role="form" action="?page=hostapd_conf" method="POST">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs">
              <li class="active"><a href="#basic" data-toggle="tab">Basic</a></li>
              <li><a href="#security" data-toggle="tab">Security</a></li>
              <li><a href="#advanced" data-toggle="tab">Advanced</a></li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content">
              <p><?php $status->showMessages(); ?></p>
              <div class="tab-pane fade in active" id="basic">

                <h4>Basic settings</h4>
                <?php CSRFToken() ?>
                <div class="row">
                  <div class="form-group col-md-4">
                    <label for="code">Interface</label>
                    <?php
                      SelectorOptions('interface', $interfaces, $arrConfig['interface']);
                    ?>
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
                    <?php SelectorOptions('hw_mode', $arrChannel, $arrConfig['hw_mode']); ?>
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-4">
                    <label for="code">Channel</label>
                    <?php SelectorOptions('channel', range(1, 14), intval($arrConfig['channel'])) ?>
                  </div>
                </div>
              </div>
              <div class="tab-pane fade" id="security">
                <h4>Security settings</h4>
                <div class="row">
                  <div class="form-group col-md-4">
                    <label for="code">Security type</label>
                    <?php SelectorOptions('wpa', $arrSecurity, $arrConfig['wpa']); ?>
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-4">
                    <label for="code">Encryption Type</label>
                    <?php SelectorOptions('wpa_pairwise', $arrEncType, $arrConfig['wpa_pairwise']); ?>
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-4">
                    <label for="code">PSK</label>
                    <input type="text" class="form-control" name="wpa_passphrase" value="<?php echo $arrConfig['wpa_passphrase'] ?>" />
                  </div>
                </div>
              </div>
              <div class="tab-pane fade" id="advanced">
                <h4>Advanced settings</h4>
                <div class="row">
                  <div class="form-group col-md-4">
                  <label for="code">Country Code</label>
                  <input type="text" class="form-control" name="country_code" value="<?php echo $arrConfig['country_code'] ?>" />
                </div>
              </div>
            </div><!-- ./ Panel body -->

            <input type="submit" class="btn btn-outline btn-primary" name="SaveHostAPDSettings" value="Save settings" />
            <?php
              if($hostapdstatus[0] == 0) {
                echo '<input type="submit" class="btn btn-success" name="StartHotspot" value="Start hotspot" />';
              } else {
                echo '<input type="submit" class="btn btn-warning" name="StopHotspot" value="Stop hotspot" />';
              };
            ?>
          </form>
        </div><!-- /.panel-primary -->
      <div class="panel-footer"> Information provided by hostapd</div>
    </div><!-- /.col-lg-12 -->
  </div><!-- /.row -->
<?php 
}

function SaveHostAPDConfig($wpa_array, $enc_types, $modes, $interfaces, $status) {
  // It should not be possible to send bad data for these fields so clearly
  // someone is up to something if they fail. Fail silently.
  if (!(array_key_exists($_POST['wpa'], $wpa_array) && array_key_exists($_POST['wpa_pairwise'], $enc_types) && in_array($_POST['hw_mode'], $modes))) {
    error_log("Attempting to set hostapd config with wpa='".$_POST['wpa']."', wpa_pairwise='".$_POST['wpa_pairwise']."' and hw_mode='".$_POST['hw_mode']."'");
    return false;
  }
  if ((!filter_var($_POST['channel'], FILTER_VALIDATE_INT)) || intval($_POST['channel']) < 1 || intval($_POST['channel']) > 14) {
    error_log("Attempting to set channel to '".$_POST['channel']."'");
    return false;
  }

  $good_input = true;

  // Verify input
  if (strlen($_POST['ssid']) == 0 || strlen($_POST['ssid']) > 32) {
    // Not sure of all the restrictions of SSID
    $status->addMessage('SSID must be between 1 and 32 characters', 'danger');
    $good_input = false;
  }
  if (strlen($_POST['wpa_passphrase']) < 8 || strlen($_POST['wpa_passphrase']) > 63) {
    $status->addMessage('WPA passphrase must be between 8 and 63 characters', 'danger');
    $good_input = false;
  }
  if (! in_array($_POST['interface'], $interfaces)) {
    // The user is probably up to something here but it may also be a
    // genuine error.
    $status->addMessage('Unknown interface '.$_POST['interface'], 'danger');
    $good_input = false;
  }
  if (strlen($_POST['country_code']) != 0 && strlen($_POST['country_code']) != 2) {
    $status->addMessage('Country code must be blank or two characters', 'danger');
    $good_input = false;
  }

  if ($good_input) {
    if ($tmp_file = fopen('/tmp/hostapddata', 'w')) {
      // Fixed values
      fwrite($tmp_file, 'driver=nl80211'.PHP_EOL);
      fwrite($tmp_file, 'ctrl_interface='.RASPI_HOSTAPD_CTRL_INTERFACE.PHP_EOL);
      fwrite($tmp_file, 'ctrl_interface_group=0'.PHP_EOL);
      fwrite($tmp_file, 'beacon_int=100'.PHP_EOL);
      fwrite($tmp_file, 'auth_algs=1'.PHP_EOL);
      fwrite($tmp_file, 'wpa_key_mgmt=WPA-PSK'.PHP_EOL);

      fwrite($tmp_file, 'ssid='.$_POST['ssid'].PHP_EOL);
      fwrite($tmp_file, 'channel='.$_POST['channel'].PHP_EOL);
      fwrite($tmp_file, 'hw_mode='.$_POST['hw_mode'].PHP_EOL);
      fwrite($tmp_file, 'wpa_passphrase='.$_POST['wpa_passphrase'].PHP_EOL);
      fwrite($tmp_file, 'interface='.$_POST['interface'].PHP_EOL);
      fwrite($tmp_file, 'wpa='.$_POST['wpa'].PHP_EOL);
      fwrite($tmp_file, 'wpa_pairwise='.$_POST['wpa_pairwise'].PHP_EOL);
      fwrite($tmp_file, 'country_code='.$_POST['country_code'].PHP_EOL);
      fclose($tmp_file);

      system( "sudo cp /tmp/hostapddata " . RASPI_HOSTAPD_CONFIG, $return );
      if( $return == 0 ) {
        $status->addMessage('Wifi Hotspot settings saved', 'success');
      } else {
        $status->addMessage('Unable to save wifi hotspot settings', 'danger');
      }
    } else {
      $status->addMessage('Unable to save wifi hotspot settings', 'danger');
      return false;
    }
  }
  return true;
}
?>
