<?php

include_once( 'includes/status_messages.php' );

/**
*
* Manage DHCP configuration
*
*/
function DisplayDHCPConfig() {

  $status = new StatusMessages();
  if( isset( $_POST['savedhcpdsettings'] ) ) {
    if (CSRFValidate()) {
      $config = 'interface='.$_POST['interface'].PHP_EOL
        .'dhcp-range='.$_POST['RangeStart'].','.$_POST['RangeEnd'].',255.255.255.0,'.$_POST['RangeLeaseTime'].''.$_POST['RangeLeaseTimeUnits'];
      exec( 'echo "'.$config.'" > /tmp/dhcpddata',$temp );
      system( 'sudo cp /tmp/dhcpddata '. RASPI_DNSMASQ_CONFIG, $return );

      if( $return == 0 ) {
        $status->addMessage('Dnsmasq configuration updated successfully', 'success');
      } else {
        $status->addMessage('Dnsmasq configuration failed to be updated', 'danger');
      }
    } else {
      error_log('CSRF violation');
    }
  }

  exec( 'pidof dnsmasq | wc -l',$dnsmasq );
  $dnsmasq_state = ($dnsmasq[0] > 0);

  if( isset( $_POST['startdhcpd'] ) ) {
    if (CSRFValidate()) {
      if ($dnsmasq_state) {
        $status->addMessage('dnsmasq already running', 'info');
      } else {
        exec('sudo /etc/init.d/dnsmasq start', $dnsmasq, $return);
        if ($return == 0) {
          $status->addMessage('Successfully started dnsmasq', 'success');
          $dnsmasq_state = true;
        } else {
          $status->addMessage('Failed to start dnsmasq', 'danger');
        }
      }
    } else {
      error_log('CSRF violation');
    }
  } elseif( isset($_POST['stopdhcpd'] ) ) {
    if (CSRFValidate()) {
      if ($dnsmasq_state) {
        exec('sudo /etc/init.d/dnsmasq stop', $dnsmasq, $return);
        if ($return == 0) {
          $status->addMessage('Successfully stopped dnsmasq', 'success');
          $dnsmasq_state = false;
        } else {
          $status->addMessage('Failed to stop dnsmasq', 'danger');
        }
      } else {
        $status->addMessage('dnsmasq already stopped', 'info');
      }
    } else {
      error_log('CSRF violation');
    }
  } else {
    if( $dnsmasq_state ) {
      $status->addMessage('Dnsmasq is running', 'success');
    } else {
      $status->addMessage('Dnsmasq is not running', 'warning');
    }
  }

  exec( 'cat '. RASPI_DNSMASQ_CONFIG, $return );
  $conf = ParseConfig($return);
  $arrRange = explode( ",", $conf['dhcp-range'] );
  $RangeStart = $arrRange[0];
  $RangeEnd = $arrRange[1];
  $RangeMask = $arrRange[2];
  preg_match( '/([0-9]*)([a-z])/i', $arrRange[3], $arrRangeLeaseTime );

  switch( $arrRangeLeaseTime[2] ) {
    case "h":
      $hselected = " selected";
    break;
    case "m":
      $mselected = " selected";
    break;
    case "d":
      $dselected = " selected";
    break;
  }

  ?>
  <div class="row">
  <div class="col-lg-12">
      <div class="panel panel-primary">
      <div class="panel-heading"><i class="fa fa-exchange fa-fw"></i> Configure DHCP
            </div>
        <!-- /.panel-heading -->
        <div class="panel-body">
        <p><?php $status->showMessages(); ?></p>
        <!-- Nav tabs -->
            <ul class="nav nav-tabs">
                <li class="active"><a href="#server-settings" data-toggle="tab">Server settings</a>
                </li>
                <li><a href="#client-list" data-toggle="tab">Client list</a>
                </li>
            </ul>
        <!-- Tab panes -->
        <div class="tab-content">
    <div class="tab-pane fade in active" id="server-settings">
    <h4>DHCP server settings</h4>
    <form method="POST" action="?page=dhcpd_conf">
    <?php CSRFToken() ?>
    <div class="row">
      <div class="form-group col-md-4">
        <label for="code">Interface</label>
        <select class="form-control" name="interface">
        <?php 
        exec("ip -o link show | awk -F': ' '{print $2}'", $interfaces);

        foreach( $interfaces as $int ) {
          $select = '';
          if( $int == $conf['interface'] ) {
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
        <label for="code">Starting IP Address</label>
        <input type="text" class="form-control"name="RangeStart" value="<?php echo $RangeStart; ?>" />
      </div>
    </div>

    <div class="row">
      <div class="form-group col-md-4">
        <label for="code">Ending IP Address</label>
        <input type="text" class="form-control" name="RangeEnd" value="<?php echo $RangeEnd; ?>" />
      </div>
    </div>

    <div class="row">
      <div class="form-group col-xs-2 col-sm-2">
        <label for="code">Lease Time</label>
        <input type="text" class="form-control" name="RangeLeaseTime" value="<?php echo $arrRangeLeaseTime[1]; ?>" />
      </div>
      <div class="col-xs-2 col-sm-2">
        <label for="code">Interval</label>
        <select name="RangeLeaseTimeUnits" class="form-control" ><option value="m" <?php echo $mselected; ?>>Minutes</option><option value="h" <?php echo $hselected; ?>>Hours</option><option value="d" <?php echo $dselected; ?>>Days</option><option value="infinite">Infinite</option></select> 
      </div>
    </div>

    <input type="submit" class="btn btn-outline btn-primary" value="Save settings" name="savedhcpdsettings" />
    <?php

    if ( $dnsmasq_state ) {
      echo '<input type="submit" class="btn btn-warning" value="Stop dnsmasq" name="stopdhcpd" />';
    } else {
      echo'<input type="submit" class="btn btn-success" value="Start dnsmasq" name="startdhcpd" />';
    }
    ?>
    </form>
    </div><!-- /.tab-pane -->

    <div class="tab-pane fade in" id="client-list">
    <h4>Client list</h4>
    <div class="col-lg-12">
      <div class="panel panel-default">
      <div class="panel-heading">
        Active DHCP leases
      </div>
      <!-- /.panel-heading -->
      <div class="panel-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Expire time</th>
                <th>MAC Address</th>
                <th>IP Address</th>
                <th>Host name</th>
                <th>Client ID</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <?php
                exec( 'cat ' . RASPI_DNSMASQ_LEASES, $leases );
                foreach( $leases as $lease ) {
                  $lease_items = explode(' ', $lease);
                  foreach( $lease_items as $lease_item ) {
                    echo '<td>' . $lease_item . '</td>';
                  }
                  echo '</tr>';
                };
                ?>
              </tr>
            </tbody>
          </table>
        </div><!-- /.table-responsive -->
      </div><!-- /.panel-body -->
      </div><!-- /.panel -->
    </div><!-- /.col-lg-6 -->
    </div><!-- /.tab-pane -->
    </div><!-- /.tab-content -->
    </div><!-- ./ Panel body -->
    <div class="panel-footer"> Information provided by Dnsmasq</div>
        </div><!-- /.panel-primary -->
    </div><!-- /.col-lg-12 -->
  </div><!-- /.row -->
<?php
}

?>

