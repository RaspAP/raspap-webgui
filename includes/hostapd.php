<?php

include_once( 'includes/status_messages.php' );

/**
*
*
*/
function DisplayHostAPDConfig(){

  $status = new StatusMessages();

  if( isset($_POST['SaveHostAPDSettings']) ) {
    if (CSRFValidate()) {
      $config = 'driver=nl80211'.PHP_EOL
        .'ctrl_interface='.RASPI_HOSTAPD_CTRL_INTERFACE.PHP_EOL
        .'ctrl_interface_group=0'.PHP_EOL
        .'beacon_int=100'.PHP_EOL
        .'auth_algs=1'.PHP_EOL
        .'wpa_key_mgmt=WPA-PSK'.PHP_EOL;

      $config .= "interface=".$_POST['interface'].PHP_EOL;
      $config .= "ssid=".$_POST['ssid'].PHP_EOL;
      $config .= "hw_mode=".$_POST['hw_mode'].PHP_EOL;
      $config .= "channel=".$_POST['channel'].PHP_EOL;
      $config .= "wpa=".$_POST['wpa'].PHP_EOL;
      $config .='wpa_passphrase='.$_POST['wpa_passphrase'].PHP_EOL;
      $config .="wpa_pairwise=".$_POST['wpa_pairwise'].PHP_EOL;
      $config .="country_code=".$_POST['country_code'];

      exec( "echo '$config' > /tmp/hostapddata", $return );
      system( "sudo cp /tmp/hostapddata " . RASPI_HOSTAPD_CONFIG, $return );

      if( $return == 0 ) {
        $status->addMessage('Wifi Hotspot settings saved', 'success');
      } else {
        $status->addMessage('Wifi Hotspot settings failed to be saved', 'danger');
      }
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
        <div class="panel-heading"><i class="fa fa-dot-circle-o fa-fw"></i> Configure hotspot</div>
        <!-- /.panel-heading -->
        <div class="panel-body">
          <form role="form" action="/?page=hostapd_conf" method="POST">
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
                      exec("ip -o link show | awk -F': ' '{print $2}'", $interfaces);
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
                    <?php
                      /*
                      * NB, the original tests $arrConfig['wpa_pairwise'] against
                      * the value in the $arrEncType array rather than the key. I
                      * think there must be something wrong in the case of
                      * 'TKIP CCMP' => 'TKIP+CCMP' but I am not yet sure what
                      * exactly is correct.
                      * At I read it, 'TKIP CCMP' would get written to the
                      * hostapd.conf file when it is saved but the correct option
                      * would only be selected if it reads 'TKIP+CCMP'. This is
                      * clearly broken.
                      * Now it is consistent, albeit possibly still broken.
                      */
                    ?>
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
?>
<?php

/**
*
* NB This function is also used for TOR and VPN so don't completely delete
*
*/
function SaveHostAPDConfig(){
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

