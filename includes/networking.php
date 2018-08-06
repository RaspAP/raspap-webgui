<?php

include_once( 'includes/status_messages.php' );

/**
*
*
*/
function DisplayNetworkingConfig(){

  $status = new StatusMessages();

  exec("ls /sys/class/net | grep -v lo", $interfaces);

  foreach($interfaces as $interface) {
    exec("ip a show $interface",$$interface);
  }

  CSRFToken();
?>

<div class="row">
    <div class="col-lg-12">
       <div class="panel panel-primary">
          <div class="panel panel-heading">
	  <i class="fa fa-sitemap fa-fw"></i> <?php echo _("Configure networking"); ?></div>
          <div class="panel-body">
            <div id="msgNetworking"></div>
              <ul class="nav nav-tabs">
	      <li role="presentation" class="active"><a href="#summary" aria-controls="summary" role="tab" data-toggle="tab"><?php echo _("Summary"); ?></a></li>
                <?php
                  foreach($interfaces as $interface) {
                      echo '<li role="presentation"><a href="#'.htmlspecialchars($interface, ENT_QUOTES).'" aria-controls="'.htmlspecialchars($interface, ENT_QUOTES).'" role="tab" data-toggle="tab">'.htmlspecialchars($interface, ENT_QUOTES).'</a></li>';
                  }
                ?>
              </ul>
                <div class="tab-content">
                  <div role="tabpanel" class="tab-pane active" id="summary">
		    <h4><?php echo _("Current settings"); ?></h4>
                      <div class="row">
                      <?php
                        foreach($interfaces as $interface) {
                          echo '<div class="col-md-6">
                            <div class="panel panel-default">
                                <div class="panel-heading">'.htmlspecialchars($interface, ENT_QUOTES).'</div>
                                <div class="panel-body" id="'.htmlspecialchars($interface, ENT_QUOTES).'-summary"></div>
                            </div>
                            </div>';
                        }
                      ?>
                      </div><!-- /.row -->
                    <div class="col-lg-12">
                      <div class="row">
		      <a href="#" class="btn btn-outline btn-primary" id="btnSummaryRefresh"><i class="fa fa-refresh"></i> <?php echo _("Refresh"); ?></a>
                      </div><!-- /.row -->
                    </div><!-- /.col-lg-12 -->
                  </div><!-- /.tab-pane -->
<?php
                  foreach($interfaces as $interface) {
                      echo '
                      <div role="tabpanel" class="tab-pane fade in" id="'.htmlspecialchars($interface, ENT_QUOTES).'">
                        <div class="row">
                          <div class="col-lg-6">
                            <form id="frm-'.htmlspecialchars($interface, ENT_QUOTES).'">
                              <div class="form-group">
                                <h4>' . _("Adapter IP Address Settings") . '</h4>
                                <div class="btn-group" data-toggle="buttons">
                                  <label class="btn btn-primary">
                                    <input type="radio" name="'.htmlspecialchars($interface, ENT_QUOTES).'-addresstype" id="'.htmlspecialchars($interface, ENT_QUOTES).'-dhcp" autocomplete="off">' . _("DHCP") . '
                                  </label>
                                  <label class="btn btn-primary">
                                    <input type="radio" name="'.htmlspecialchars($interface, ENT_QUOTES).'-addresstype" id="'.htmlspecialchars($interface, ENT_QUOTES).'-static" autocomplete="off">' . _("Static IP") . '
                                  </label>
                                </div><!-- /.btn-group -->
                                <h4>' . _("Enable Fallback to Static Option") . '</h4>
                                <div class="btn-group" data-toggle="buttons">
                                  <label class="btn btn-primary">
                                    <input type="radio" name="'.htmlspecialchars($interface, ENT_QUOTES).'-dhcpfailover" id="'.htmlspecialchars($interface, ENT_QUOTES).'-failover" autocomplete="off">' . _("Enabled") . '
                                  </label>
                                  <label class="btn btn-warning">
                                    <input type="radio" name="'.htmlspecialchars($interface, ENT_QUOTES).'-dhcpfailover" id="'.htmlspecialchars($interface, ENT_QUOTES).'-nofailover" autocomplete="off">' . _("Disabled") . '
                                  </label>
                                </div><!-- /.btn-group -->
                              </div><!-- /.form-group -->
                              <hr />
                              <h4>' . _("Static IP Options") . '</h4>
                              <div class="form-group">
                                <label for="'.htmlspecialchars($interface, ENT_QUOTES).'-ipaddress">' . _("IP Address") . '</label>
                                <input type="text" class="form-control" id="'.htmlspecialchars($interface, ENT_QUOTES).'-ipaddress" placeholder="0.0.0.0">
                              </div>
                              <div class="form-group">
                                <label for="'.htmlspecialchars($interface, ENT_QUOTES).'-netmask">' . _("Subnet Mask") . '</label>
                                <input type="text" class="form-control" id="'.htmlspecialchars($interface, ENT_QUOTES).'-netmask" placeholder="255.255.255.0">
                              </div>
                              <div class="form-group">
                                <label for="'.htmlspecialchars($interface, ENT_QUOTES).'-gateway">' . _("Default Gateway") . '</label>
                                <input type="text" class="form-control" id="'.htmlspecialchars($interface, ENT_QUOTES).'-gateway" placeholder="0.0.0.0">
                              </div>
                              <div class="form-group">
                                <label for="'.htmlspecialchars($interface, ENT_QUOTES).'-dnssvr">' . _("DNS Server") . '</label>
                                <input type="text" class="form-control" id="'.htmlspecialchars($interface, ENT_QUOTES).'-dnssvr" placeholder="0.0.0.0">
                              </div>
                              <div class="form-group">
                                <label for="'.htmlspecialchars($interface, ENT_QUOTES).'-dnssvralt">' . _("Alternate DNS Server") . '</label>
                                <input type="text" class="form-control" id="'.htmlspecialchars($interface, ENT_QUOTES).'-dnssvralt" placeholder="0.0.0.0">
                              </div>
                              <a href="#" class="btn btn-outline btn-primary intsave" data-int="'.htmlspecialchars($interface, ENT_QUOTES).'">' . _("Save settings") . '</a>
                              <a href="#" class="btn btn-warning intapply" data-int="'.htmlspecialchars($interface, ENT_QUOTES).'">' . _("Apply settings") . '</a>
                              </form>
                            </div>
                      </div><!-- /.tab-panel -->
                    </div>';
                  }
?>
              </div><!-- /.tab-content -->
            </div><!-- /.panel-body -->
	    <div class="panel-footer"><?php echo _("Information provided by /sys/class/net"); ?></div>
        </div><!-- /.panel-primary -->
      </div><!-- /.col-lg-12 -->
    </div>
    
<?php
}
?>
