<?php

/**
*
*
*/
function DisplayDashboard(){

  $status = new StatusMessages();

  exec( 'ip a s ' . RASPI_WIFI_CLIENT_INTERFACE , $return );
  exec( 'iwconfig ' . RASPI_WIFI_CLIENT_INTERFACE, $return );

  $strWlan0 = implode( " ", $return );
  $strWlan0 = preg_replace( '/\s\s+/', ' ', $strWlan0 );

  // Parse results from ifconfig/iwconfig
  preg_match( '/link\/ether ([0-9a-f:]+)/i',$strWlan0,$result ) || $result[1] = 'No MAC Address Found';
  $strHWAddress = $result[1];
  preg_match_all( '/inet ([0-9.]+)/i',$strWlan0,$result ) || $result[1] = 'No IP Address Found';
  $strIPAddress = '';
  foreach($result[1] as $ip) {
      $strIPAddress .= $ip." ";
  }
  preg_match_all( '/[0-9.]+\/([0-3][0-9])/i',$strWlan0,$result ) || $result[1] = 'No Subnet Mask Found';
  $strNetMask = '';
  foreach($result[1] as $netmask) {
    $strNetMask .= long2ip(-1 << (32 -(int)$netmask))." ";
  }
  preg_match( '/RX packets:(\d+)/',$strWlan0,$result ) || $result[1] = 'No Data';
  $strRxPackets = $result[1];
  preg_match( '/TX packets:(\d+)/',$strWlan0,$result ) || $result[1] = 'No Data';
  $strTxPackets = $result[1];
  preg_match( '/RX bytes:(\d+ \(\d+.\d+ [K|M|G]iB\))/i',$strWlan0,$result ) || $result[1] = 'No Data';
  $strRxBytes = $result[1];
  preg_match( '/TX Bytes:(\d+ \(\d+.\d+ [K|M|G]iB\))/i',$strWlan0,$result ) || $result[1] = 'No Data';
  $strTxBytes = $result[1];
  preg_match( '/ESSID:\"([a-zA-Z0-9\s].+)\"/i',$strWlan0,$result ) || $result[1] = 'Not connected';
  $strSSID = str_replace( '"','',$result[1] );
  preg_match( '/Access Point: ([0-9a-f:]+)/i',$strWlan0,$result ) || $result[1] = '';
  $strBSSID = $result[1];
  preg_match( '/Bit Rate=([0-9\.]+ Mb\/s)/i',$strWlan0,$result ) || $result[1] = '';
  $strBitrate = $result[1];
  preg_match( '/Tx-Power=([0-9]+ dBm)/i',$strWlan0,$result ) || $result[1] = '';
  $strTxPower = $result[1];
  preg_match( '/Link Quality=([0-9]+)/i',$strWlan0,$result ) || $result[1] = '';
  $strLinkQuality = $result[1];
  preg_match( '/Signal level=(-?[0-9]+ dBm)/i',$strWlan0,$result ) || $result[1] = '';
  $strSignalLevel = $result[1];
  preg_match('/Frequency:(\d+.\d+ GHz)/i',$strWlan0,$result) || $result[1] = '';
  $strFrequency = $result[1];

  if(strpos( $strWlan0, "UP" ) !== false) {
    $status->addMessage('Interface is up', 'success');
    $wlan0up = true;
  } else {
    $status->addMessage('Interface is down', 'warning');
  }

  if( isset($_POST['ifdown_wlan0']) ) {
    exec( 'ifconfig ' . RASPI_WIFI_CLIENT_INTERFACE . ' | grep -i running | wc -l',$test );
    if($test[0] == 1) {
      exec( 'sudo ip link set ' . RASPI_WIFI_CLIENT_INTERFACE . ' down',$return );
    } else {
      echo 'Interface already down';
    }
  } elseif( isset($_POST['ifup_wlan0']) ) {
    exec( 'ifconfig ' . RASPI_WIFI_CLIENT_INTERFACE . ' | grep -i running | wc -l',$test );
    if($test[0] == 0) {
      exec( 'sudo ip link set ' . RASPI_WIFI_CLIENT_INTERFACE . ' up',$return );
      exec( 'sudo ip -s a f label ' . RASPI_WIFI_CLIENT_INTERFACE,$return);
    } else {
      echo 'Interface already up';
    }
  }
  ?>
  <div class="row">
      <div class="col-lg-12">
          <div class="panel panel-primary">
            <div class="panel-heading"><i class="fa fa-dashboard fa-fw"></i> Dashboard   </div>
              <div class="panel-body">
                <p><?php $status->showMessages(); ?></p>
                  <div class="row">
                        <div class="col-md-6">
                        <div class="panel panel-default">
                  <div class="panel-body">
                      <h4>Interface Information</h4>
		      <div class="info-item">Interface Name</div> <?php echo RASPI_WIFI_CLIENT_INTERFACE ?></br>
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
          <div class="info-item">Signal Level</div>        <?php echo $strSignalLevel ?></br>
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
        </div><!-- /.panel-body -->
        </div><!-- /.panel-default -->
                        </div><!-- /.col-md-6 -->
      </div><!-- /.row -->

                  <div class="col-lg-12">
                 <div class="row">
                    <form action="?page=wlan0_info" method="POST">
                    <?php if ( !$wlan0up ) {
                      echo '<input type="submit" class="btn btn-success" value="Start ' . RASPI_WIFI_CLIENT_INTERFACE . '" name="ifup_wlan0" />';
                    } else {
                echo '<input type="submit" class="btn btn-warning" value="Stop ' . RASPI_WIFI_CLIENT_INTERFACE . '" name="ifdown_wlan0" />';
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

?>
