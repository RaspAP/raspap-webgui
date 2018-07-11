<?php

/**
*
*
*/
function DisplayPPPDashboard(){

  $status = new StatusMessages();

  exec( 'ifconfig ppp0', $return );

  $strWlan0 = implode( " ", $return );
  $strWlan0 = preg_replace( '/\s\s+/', ' ', $strWlan0 );

  $return = '';
  exec( 'sudo /var/www/html/includes/querymodem.sh', $return );
  $strPpp0 = implode( " ", $return ) ;
  $strPpp0 = preg_replace( '/\s\s+/', ' ', $strPpp0 );

 // echo $strPpp0;

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
  $result = '';

  // Parse results from Cellular Modem
  preg_match('/\+CSQ: ([0-9.]+)/i', $strPpp0,$result );
  $strCSQ = ConvertDbmToPercentage(ConvertToDbm($result[1]));
  $strDBM = ConvertToDbm($result[1]);

  // Cellular Network Name
  preg_match( '/\+COPS: 0,0,\"([0-9a-zA-Z\s\&]+)\"/i', $strPpp0,$result );
  $strCOPS = $result[1];
  preg_match( '/\+CPSI: ([0-9a-zA-Z]+)/i',$strPpp0,$result );
  $strCPSI = $result[1];


  if(strpos( $strWlan0, "UP" ) !== false && strpos( $strWlan0, "RUNNING" ) !== false ) {
    $status->addMessage('Interface is up', 'success');
    $wlan0up = true;
  } else {
    $status->addMessage('Interface is down', 'warning');
  }

  if( isset($_POST['ifdown_ppp0']) ) {
    exec( 'ifconfig ppp0 | grep -i running | wc -l',$test );
    if($test[0] == 1) {
      exec( 'sudo ifconfig ppp0 down',$return );
    } else {
      echo 'Interface already down';
    }
  } elseif( isset($_POST['ifup_ppp0']) ) {
    exec( 'ifconfig ppp0 | grep -i running | wc -l',$test );
    if($test[0] == 0) {
      exec( 'sudo ifconfig ppp0 up',$return );
    } else {
      echo 'Interface already up';
    }
  }  elseif( isset($_POST['reset_ppp0']) ) {
    exec( 'sudo /var/www/html/includes/resetmodem.sh',$test );
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
          <div class="info-item">Interface Name</div> ppp0</br>
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
                            <h4>Cellular Information</h4>
          <div class="info-item">Connected To</div>   <?php echo $strCOPS ?></br>
          <div class="info-item">Connection Type</div> <?php echo $strCPSI ?></br>
          <div class="info-item">Link dBm</div> <?php echo $strDBM ?></br>
          <div class="info-item">Link Quality</div>
            <div class="progress">
            <div class="progress-bar progress-bar-info progress-bar-striped active"
              role="progressbar"
              aria-valuenow="<?php echo $strCSQ ?>" aria-valuemin="0" aria-valuemax="100"
              style="width: <?php echo $strCSQ ?>%;"><?php echo $strCSQ ?>%
            </div>
          </div>
        </div><!-- /.panel-body -->
        </div><!-- /.panel-default -->
       </div><!-- /.col-md-6 -->

  </div><!-- /.row -->

             <div class="col-lg-12">
                 <div class="row">
                    <form action="?page=ppp0_info" method="POST">
                      <?php if ( !$wlan0up ) {
                          echo '<input type="submit" class="btn btn-success" value="Start ppp0" name="ifup_ppp0" />';
                          } else {
                            echo '<input type="submit" class="btn btn-warning" value="Stop ppp0" name="ifdown_ppp0" />';
                          }

                      echo '<input type="submit" class="btn btn-warning" value="Reset ppp0" name="reset_ppp0" />';
                      ?>
                      <input type="button" class="btn btn-outline btn-primary" value="Refresh" onclick="document.location.reload(true)" />
                    </form>
                  </div>
            </div>

                </div><!-- /.panel-body -->
                <div class="panel-footer">Information provided by ifconfig</div>
            </div><!-- /.panel-default -->
        </div><!-- /.col-lg-12 -->
    </div><!-- /.row -->
  <?php 
}

?>
