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
