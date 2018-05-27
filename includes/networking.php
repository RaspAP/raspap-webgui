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
                      echo '<li role="presentation"><a href="#'.$interface.'" aria-controls="'.$interface.'" role="tab" data-toggle="tab">'.$interface.'</a></li>';
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
                                <div class="panel-heading">'.$interface.'</div>
                                <div class="panel-body" id="'.$interface.'-summary"></div>
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
                      <div role="tabpanel" class="tab-pane fade in" id="'.$interface.'">
                        <div class="row">
                          <div class="col-lg-6">
                            <form id="frm-'.$interface.'">
                              <div class="form-group">
                                <h4>' . _("Adapter IP Address Settings") . '</h4>
                                <div class="btn-group" data-toggle="buttons">
                                  <label class="btn btn-primary">
                                    <input type="radio" name="'.$interface.'-addresstype" id="'.$interface.'-dhcp" autocomplete="off">' . _("DHCP") . '
                                  </label>
                                  <label class="btn btn-primary">
                                    <input type="radio" name="'.$interface.'-addresstype" id="'.$interface.'-static" autocomplete="off">' . _("Static IP") . '
                                  </label>
                                </div><!-- /.btn-group -->
                                <h4>' . _("Enable Fallback to Static Option") . '</h4>
                                <div class="btn-group" data-toggle="buttons">
                                  <label class="btn btn-primary">
                                    <input type="radio" name="'.$interface.'-dhcpfailover" id="'.$interface.'-failover" autocomplete="off">' . _("Enabled") . '
                                  </label>
                                  <label class="btn btn-warning">
                                    <input type="radio" name="'.$interface.'-dhcpfailover" id="'.$interface.'-nofailover" autocomplete="off">' . _("Disabled") . '
                                  </label>
                                </div><!-- /.btn-group -->
                              </div><!-- /.form-group -->
                              <hr />
                              <h4>' . _("Static IP Options") . '</h4>
                              <div class="form-group">
                                <label for="'.$interface.'-ipaddress">' . _("IP Address") . '</label>
                                <input type="text" class="form-control" id="'.$interface.'-ipaddress" placeholder="0.0.0.0">
                              </div>
                              <div class="form-group">
                                <label for="'.$interface.'-netmask">' . _("Subnet Mask") . '</label>
                                <input type="text" class="form-control" id="'.$interface.'-netmask" placeholder="255.255.255.0">
                              </div>
                              <div class="form-group">
                                <label for="'.$interface.'-gateway">' . _("Default Gateway") . '</label>
                                <input type="text" class="form-control" id="'.$interface.'-gateway" placeholder="0.0.0.0">
                              </div>
                              <div class="form-group">
                                <label for="'.$interface.'-dnssvr">' . _("DNS Server") . '</label>
                                <input type="text" class="form-control" id="'.$interface.'-dnssvr" placeholder="0.0.0.0">
                              </div>
                              <div class="form-group">
                                <label for="'.$interface.'-dnssvralt">' . _("Alternate DNS Server") . '</label>
                                <input type="text" class="form-control" id="'.$interface.'-dnssvralt" placeholder="0.0.0.0">
                              </div>
                              <a href="#" class="btn btn-outline btn-primary intsave" data-int="'.$interface.'">' . _("Save settings") . '</a>
                              <a href="#" class="btn btn-warning intapply" data-int="'.$interface.'">' . _("Apply settings") . '</a>
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
