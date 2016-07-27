<?php
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
              exec("ip -o link show | awk -F': ' '{print $2}'", $interfaces);
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
?>
<?php

/**
*
*
*/
function SaveHostAPDConfig(){
  if( isset($_POST['SaveHostAPDSettings']) ) {
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

