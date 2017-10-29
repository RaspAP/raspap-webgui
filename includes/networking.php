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
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel panel-heading">
                <i class="fa fa-sitemap fa-fw"></i> Configure Networking
            </div>
            <div class="panel panel-body">
                <div id="msgNetworking"></div>
                <ul class="nav nav-tabs">
                    <li role="presentation" class="active"><a href="#summary" aria-controls="summary" role="tab" data-toggle="tab">Summary</a></li>
                    <?php
                    foreach($interfaces as $interface) {
                        echo '<li role="presentation"><a href="#'.$interface.'" aria-controls="'.$interface.'" role="tab" data-toggle="tab">'.$interface.'</a></li>';
                    }
                    ?>
                </ul>
                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane active" id="summary">
                    Current Settings:<br />
                    <?php
                        foreach($interfaces as $interface) {
                            echo '<div class="row">
                                <div class="col-md-6">
                                <div class="panel panel-primary">
                                    <div class="panel-heading">'.$interface.'</div>
                                    <div class="panel-body" id="'.$interface.'-summary">
                                    </div>
                                </div>
                                </div>
                            </div>';
                        }
                    ?>
                    <a href="#" class="btn btn-success btn-lg" id="btnSummaryRefresh"><i class="fa fa-refresh"></i> Refresh</a>
                    </div>
                    <?php
                        foreach($interfaces as $interface) {
                            echo '<div role="tabpanel" class="tab-pane" id="'.$interface.'">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <form id="frm-'.$interface.'">
                                                <div class="form-group">
                                                    <h4>Adapter IP Address Settings:</h4>
                                                        <div class="btn-group" data-toggle="buttons">
                                                            <label class="btn btn-primary">
                                                                <input type="radio" name="'.$interface.'-addresstype" id="'.$interface.'-dhcp" autocomplete="off">DHCP
                                                            </label>
                                                            <label class="btn btn-primary">
                                                                <input type="radio" name="'.$interface.'-addresstype" id="'.$interface.'-static" autocomplete="off">Static IP
                                                            </label>
                                                        </div>
                                                    <h4>Enable Fallback to Static Option:</h4>
                                                        <div class="btn-group" data-toggle="buttons">
                                                            <label class="btn btn-primary">
                                                                <input type="radio" name="'.$interface.'-dhcpfailover" id="'.$interface.'-failover" autocomplete="off">Enabled
                                                            </label>
                                                            <label class="btn btn-warning">
                                                                <input type="radio" name="'.$interface.'-dhcpfailover" id="'.$interface.'-nofailover" autocomplete="off">Disabled
                                                            </label>
                                                        </div>
                                                </div>
                                                <hr />
                                                <h4>Static IP Options</h4>
                                                <div class="form-group">
                                                    <label for="'.$interface.'-ipaddress">IP Address</label>
                                                    <input type="text" class="form-control" id="'.$interface.'-ipaddress" placeholder="0.0.0.0">
                                                </div>
                                                <div class="form-group">
                                                    <label for="'.$interface.'-netmask">Subnet Mask</label>
                                                    <input type="text" class="form-control" id="'.$interface.'-netmask" placeholder="255.255.255.0">
                                                </div>
                                                <div class="form-group">
                                                    <label for="'.$interface.'-gateway">Default Gateway</label>
                                                    <input type="text" class="form-control" id="'.$interface.'-gateway" placeholder="0.0.0.0">
                                                </div>
                                                <div class="form-group">
                                                    <label for="'.$interface.'-dnssvr">DNS Server</label>
                                                    <input type="text" class="form-control" id="'.$interface.'-dnssvr" placeholder="0.0.0.0">
                                                </div>
                                                <div class="form-group">
                                                    <label for="'.$interface.'-dnssvralt">Alternate DNS Server</label>
                                                    <input type="text" class="form-control" id="'.$interface.'-dnssvralt" placeholder="0.0.0.0">
                                                </div>
                                                <a href="#" class="btn btn-primary btn-lg active intsave" data-int="'.$interface.'">Save Settings</a>
                                                <a href="#" class="btn btn-success btn-lg active intapply" data-int="'.$interface.'">Apply Settings</a>
                                            </form>
                                        </div>
                                    </div>
                            </div>';
                        }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>



<?php
}
?>
