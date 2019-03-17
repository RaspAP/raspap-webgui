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
        $errors = '';
        define('IFNAMSIZ', 16);
        if (!preg_match('/^[a-zA-Z0-9]+$/', $_POST['interface']) ||
            strlen($_POST['interface']) >= IFNAMSIZ) {
            $errors .= _('Invalid interface name.').'<br />'.PHP_EOL;
        }

        if (!preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/', $_POST['RangeStart']) &&
            !empty($_POST['RangeStart'])) {  // allow ''/null ?
            $errors .= _('Invalid DHCP range start.').'<br />'.PHP_EOL;
        }

        if (!preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/', $_POST['RangeEnd']) &&
            !empty($_POST['RangeEnd'])) {  // allow ''/null ?
            $errors .= _('Invalid DHCP range end.').'<br />'.PHP_EOL;
        }

        if (!ctype_digit($_POST['RangeLeaseTime']) && $_POST['RangeLeaseTimeUnits'] !== 'infinite') {
            $errors .= _('Invalid DHCP lease time, not a number.').'<br />'.PHP_EOL;
        }

        if (!in_array($_POST['RangeLeaseTimeUnits'], array('m', 'h', 'd', 'infinite'))) {
            $errors .= _('Unknown DHCP lease time unit.').'<br />'.PHP_EOL;
        }

        $return = 1;
        if (empty($errors)) {
            $config = 'interface='.$_POST['interface'].PHP_EOL.
                      'dhcp-range='.$_POST['RangeStart'].','.$_POST['RangeEnd'].
                      ',255.255.255.0,';
            if ($_POST['RangeLeaseTimeUnits'] !== 'infinite') {
                $config .= $_POST['RangeLeaseTime'];
            }

            $config .= $_POST['RangeLeaseTimeUnits'];
            exec('echo "'.$config.'" > /tmp/dhcpddata', $temp);
            system('sudo cp /tmp/dhcpddata '.RASPI_DNSMASQ_CONFIG, $return);
        } else {
            $status->addMessage($errors, 'danger');
        }

        if ($return == 0) {
            $status->addMessage('Dnsmasq configuration updated successfully', 'success');
        } else {
            $status->addMessage('Dnsmasq configuration failed to be updated.', 'danger');
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
  $leaseTime = $arrRange[3];

  $hselected = '';
  $mselected = '';
  $dselected = '';
  $infiniteselected = '';
  preg_match( '/([0-9]*)([a-z])/i', $leaseTime, $arrRangeLeaseTime );
  if ($leaseTime === 'infinite') {
    $infiniteselected = ' selected="selected"';
  } else {
    switch( $arrRangeLeaseTime[2] ) {
      case 'h':
        $hselected = ' selected="selected"';
        break;
      case 'm':
        $mselected = ' selected="selected"';
        break;
      case 'd':
        $dselected = ' selected="selected"';
        break;
    }
  }

?>
  <div class="row">
  <div class="col-lg-12">
      <div class="panel panel-primary">
      <div class="panel-heading"><i class="fa fa-exchange fa-fw"></i> <?php echo _("Configure DHCP"); ?></div>
        <!-- /.panel-heading -->
        <div class="panel-body">
        <p><?php $status->showMessages(); ?></p>
        <!-- Nav tabs -->
            <ul class="nav nav-tabs">
                <li class="active"><a href="#server-settings" data-toggle="tab"><?php echo _("Server settings"); ?></a>
                </li>
                <li><a href="#client-list" data-toggle="tab"><?php echo _("Client list"); ?></a>
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

        foreach( $interfaces as $inet ) {
          $select = '';
          if( $inet === $conf['interface'] ) {
            $select = ' selected="selected"';
          }

          echo '        <option value="'.htmlspecialchars($inet, ENT_QUOTES).'"'.
                $select.'>'.htmlspecialchars($inet, ENT_QUOTES).'</option>' , PHP_EOL;
        }
?>
        </select>
      </div>
    </div>
    <div class="row">
      <div class="form-group col-md-4">
        <label for="code"><?php echo _("Starting IP Address"); ?></label>
        <input type="text" class="form-control"name="RangeStart" value="<?php echo htmlspecialchars($RangeStart, ENT_QUOTES); ?>" />
      </div>
    </div>

    <div class="row">
      <div class="form-group col-md-4">
        <label for="code"><?php echo _("Ending IP Address"); ?></label>
        <input type="text" class="form-control" name="RangeEnd" value="<?php echo htmlspecialchars($RangeEnd, ENT_QUOTES); ?>" />
      </div>
    </div>

    <div class="row">
      <div class="form-group col-xs-2 col-sm-2">
        <label for="code"><?php echo _("Lease Time"); ?></label>
        <input type="text" class="form-control" name="RangeLeaseTime" value="<?php echo htmlspecialchars($arrRangeLeaseTime[1], ENT_QUOTES); ?>" />
      </div>
      <div class="col-xs-2 col-sm-2">
        <label for="code"><?php echo _("Interval"); ?></label>
        <select name="RangeLeaseTimeUnits" class="form-control" >
          <option value="m"<?php echo $mselected; ?>><?php echo _("Minute(s)"); ?></option>
          <option value="h"<?php echo $hselected; ?>><?php echo _("Hour(s)"); ?></option>
          <option value="d"<?php echo $dselected; ?>><?php echo _("Day(s)"); ?></option>
          <option value="infinite"<?php echo $infiniteselected; ?>><?php echo _("Infinite"); ?></option>
        </select> 
      </div>
    </div>

    <input type="submit" class="btn btn-outline btn-primary" value="<?php echo _("Save settings"); ?>" name="savedhcpdsettings" />
    <?php

    if ( $dnsmasq_state ) {
      echo '<input type="submit" class="btn btn-warning" value="' . _("Stop dnsmasq") . '" name="stopdhcpd" />';
    } else {
      echo'<input type="submit" class="btn btn-success" value="' .  _("Start dnsmasq") . '" name="startdhcpd" />';
    }
?>
    </form>
    </div><!-- /.tab-pane -->

    <div class="tab-pane fade in" id="client-list">
    <h4>Client list</h4>
    <div class="col-lg-12">
      <div class="panel panel-default">
      <div class="panel-heading"><?php echo _("Active DHCP leases"); ?></div>
      <!-- /.panel-heading -->
      <div class="panel-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th><?php echo _("Expire time"); ?></th>
                <th><?php echo _("MAC Address"); ?></th>
                <th><?php echo _("IP Address"); ?></th>
                <th><?php echo _("Host name"); ?></th>
                <th><?php echo _("Client ID"); ?></th>
              </tr>
            </thead>
            <tbody>
<?php
exec( 'cat ' . RASPI_DNSMASQ_LEASES, $leases );
foreach( $leases as $lease ) {
    echo '              <tr>'.PHP_EOL;
    $lease_items = explode(' ', $lease);
    foreach( $lease_items as $lease_item ) {
        echo '                <td>'.htmlspecialchars($lease_item, ENT_QUOTES).'</td>'.PHP_EOL;
    }
    echo '              </tr>'.PHP_EOL;
};
?>
            </tbody>
          </table>
        </div><!-- /.table-responsive -->
      </div><!-- /.panel-body -->
      </div><!-- /.panel -->
    </div><!-- /.col-lg-6 -->
    </div><!-- /.tab-pane -->
    </div><!-- /.tab-content -->
    </div><!-- ./ Panel body -->
    <div class="panel-footer"> <?php echo _("Information provided by Dnsmasq"); ?></div>
        </div><!-- /.panel-primary -->
    </div><!-- /.col-lg-12 -->
  </div><!-- /.row -->
<?php
}

