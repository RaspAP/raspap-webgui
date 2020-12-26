<div class="row">
  <div class="col-lg-12">
   <div class="card">
    <div class="card-header">
      <div class="row">
        <div class="col">
          <i class="fas fa-network-wired mr-2"></i><?php echo _("Networking"); ?>
        </div>
      </div><!-- ./row -->
     </div><!-- ./card-header -->
      <div class="card-body">
        <div id="msgNetworking"></div>
        <ul class="nav nav-tabs">
          <li role="presentation" class="nav-item"><a class="nav-link active" href="#summary" aria-controls="summary" role="tab" data-toggle="tab"><?php echo _("Summary"); ?></a></li>
          <?php
            // Get Bridged AP mode status
            $arrHostapdConf = parse_ini_file('/etc/raspap/hostapd.ini');
            // defaults to false
            $bridgedEnabled = $arrHostapdConf['BridgedEnable'];
            ?>
        </ul>
        <div class="tab-content">
          <div role="tabpanel" class="tab-pane active" id="summary">
            <h4 class="mt-3"><?php echo _("Internet connection"); ?></h4>
            <div class="row">
             <div class="col-sm-12"">
              <div class="card ">
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-hover">
                      <thead>
                        <tr>
                          <th><?php echo _("Interface"); ?></th>
                          <th><?php echo _("IP Address"); ?></th>
                          <th><?php echo _("Gateway"); ?></th>
                          <th colspan="2"><?php echo _("Internet Access"); ?></th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                         $checkAccess=true;
                         require "includes/internetRoute.php";
                        if (isset($rInfo["error"]) || empty($rInfo)) {
                            echo "<tr><td colspan=5>No route to the internet found</td></tr>";
                        } else {
                            foreach($rInfo as $route) {
                                echo "<tr>";
                                echo "<td>".$route["interface"]."</td>";
                                echo "<td>".$route["ip-address"]."</td>";
                                echo "<td>".$route["gateway"]."<br>".$route["gw-name"]."</td>";
                                $status = $route["access-ip"] ? "fa-check" : "fa-times";
                                echo '<td><i class="fas '.$status.'"></i><br>'.RASPI_ACCESS_CHECK_IP.'</td>';
                                $status = $route["access-dns"] ? "fa-check" : "fa-times";
                                echo '<td><i class="fas '.$status.'"></i><br>'.RASPI_ACCESS_CHECK_DNS.'</td>';
                                echo "</tr>";
                            }
                        }
                        ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
             </div>
            </div>
            <h4 class="mt-3"><?php echo _("Current settings") ?></h4>
            <div class="row">
              <?php foreach ($interfaces as $if): ?>
                    <?php $if_quoted = htmlspecialchars($if, ENT_QUOTES) ?>
              <div class="col-md-6 mb-3">
                <div class="card">
                  <div class="card-header"><?php echo $if_quoted ?></div>
                  <div class="card-body">
                    <pre class="unstyled" id="<?php echo $if_quoted ?>-summary"></pre>
                  </div>
                </div>
              </div>
              <?php endforeach ?>
            </div><!-- /.row -->
            <div class="col-lg-12">
              <div class="row">
                <a href="#" class="btn btn-outline btn-primary" id="btnSummaryRefresh"><i class="fas fa-sync-alt"></i> <?php echo _("Refresh"); ?></a>
              </div><!-- /.row -->
            </div><!-- /.col-lg-12 -->
          </div><!-- /.tab-pane -->

          <?php foreach ($interfaces as $if): ?>
                <?php $if_quoted = htmlspecialchars($if, ENT_QUOTES) ?>
          <div role="tabpanel" class="tab-pane fade in" id="<?php echo $if_quoted ?>">
            <div class="row">
              <div class="col-lg-6">

                <form id="frm-<?php echo $if_quoted ?>">
                  <?php echo CSRFTokenFieldTag() ?>
                  <div class="form-group">
                    <h4 class="mt-3"><?php echo _("Adapter IP Address Settings") ?></h4>
                    <div class="btn-group" role="group" data-toggle="buttons">
                      <label class="btn btn-primary">
                        <input class="mr-2" type="radio" name="<?php echo $if_quoted ?>-addresstype" id="<?php echo $if_quoted ?>-dhcp" autocomplete="off"><?php echo _("DHCP") ?>
                      </label>
                      <label class="btn btn-primary">
                        <input class="mr-2" type="radio" name="<?php echo $if_quoted ?>-addresstype" id="<?php echo $if_quoted ?>-static" autocomplete="off"><?php echo _("Static IP") ?>
                      </label>
                    </div><!-- /.btn-group -->
                    <h4 class="mt-3"><?php echo _("Enable Fallback to Static Option") ?></h4>
                    <div class="btn-group" role="group" data-toggle="buttons">
                      <label class="btn btn-primary">
                        <input class="mr-2" type="radio" name="<?php echo $if_quoted ?>-dhcpfailover" id="<?php echo $if_quoted ?>-failover" autocomplete="off"><?php echo _("Enabled") ?>
                      </label>
                      <label class="btn btn-warning">
                        <input class="mr-2" type="radio" name="<?php echo $if_quoted ?>-dhcpfailover" id="<?php echo $if_quoted ?>-nofailover" autocomplete="off"><?php echo _("Disabled") ?>
                      </label>
                    </div><!-- /.btn-group -->
                  </div><!-- /.form-group -->

                  <hr />

                  <h4><?php echo _("Static IP Options") ?></h4>
                  <div class="form-group">
                    <label for="<?php echo $if_quoted ?>-ipaddress"><?php echo _("IP Address") ?></label>
                    <input type="text" class="form-control" id="<?php echo $if_quoted ?>-ipaddress" placeholder="0.0.0.0">
                  </div>
                  <div class="form-group">
                    <label for="<?php echo $if_quoted ?>-netmask"><?php echo _("Subnet Mask") ?></label>
                    <input type="text" class="form-control" id="<?php echo $if_quoted ?>-netmask" placeholder="255.255.255.0">
                  </div>
                  <div class="form-group">
                    <label for="<?php echo $if_quoted ?>-gateway"><?php echo _("Default Gateway") ?></label>
                    <input type="text" class="form-control" id="<?php echo $if_quoted ?>-gateway" placeholder="0.0.0.0">
                  </div>
                  <div class="form-group">
                    <label for="<?php echo $if_quoted ?>-dnssvr"><?php echo _("DNS Server") ?></label>
                    <input type="text" class="form-control" id="<?php echo $if_quoted ?>-dnssvr" placeholder="0.0.0.0">
                  </div>
                  <div class="form-group">
                    <label for="<?php echo $if_quoted ?>-dnssvralt"><?php echo _("Alternate DNS Server") ?></label>
                    <input type="text" class="form-control" id="<?php echo $if_quoted ?>-dnssvralt" placeholder="0.0.0.0">
                  </div>
                  <div class="form-group">
                    <label for="<?php echo $if_quoted ?>-metric"><?php echo _("Metric") ?></label>
                    <input type="text" class="form-control" id="<?php echo $if_quoted ?>-metric" placeholder="0">
                  </div>
                  <?php if (!RASPI_MONITOR_ENABLED) : ?>
                      <a href="#" class="btn btn-outline btn-primary intsave" data-int="<?php echo $if_quoted ?>"><?php echo _("Save settings") ?></a>
                      <a href="#" class="btn btn-warning intapply" data-int="<?php echo $if_quoted ?>"><?php echo _("Apply settings") ?></a>
                  <?php endif ?>
                </form>

              </div>
            </div><!-- /.tab-panel -->
          </div>
          <?php endforeach ?>

        </div><!-- /.tab-content -->
      </div><!-- /.card-body -->
      <div class="card-footer"><?php echo _("Information provided by /sys/class/net"); ?></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div>
