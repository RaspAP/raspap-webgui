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
          <?php if (!$bridgedEnabled) : // no interface details when bridged ?>
                <?php foreach ($interfaces as $if): ?>
                    <?php $if_quoted = htmlspecialchars($if, ENT_QUOTES) ?>
          <li role="presentation" class="nav-item"><a class="nav-link" href="#<?php echo $if_quoted ?>" aria-controls="<?php echo $if_quoted ?>" role="tab" data-toggle="tab"><?php echo $if_quoted ?></a></li>
		  <?php endforeach ?>
          <li role="presentation" class="nav-item"><a class="nav-link" href="#mobiledata" aria-controls="mobiledata" role="tab" data-toggle="tab">Mobile Data Settings</a></li>
          <li role="presentation" class="nav-item"><a class="nav-link" href="#netdevices" aria-controls="netdevices" role="tab" data-toggle="tab">Network Devices</a></li>
          <?php endif ?>
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
                  <?php if (!RASPI_MONITOR_ENABLED) : ?>
                      <a href="#" class="btn btn-outline btn-primary intsave" data-int="<?php echo $if_quoted ?>"><?php echo _("Save settings") ?></a>
              <a href="#" class="btn btn-warning intapply" data-int="<?php echo $if_quoted ?>"><?php echo _("Apply settings") ?></a>
                  <?php endif ?>
                </form>

              </div>
            </div><!-- /.tab-panel -->
          </div>
          <?php endforeach ?>
          <?php $arrMD = parse_ini_file('/etc/raspap/networking/mobiledata.ini');
                if ($arrMD==false) { $arrMD=[]; $arrMD["pin"]=$arrMD["apn"]=$arrMD["apn_user"]=$arrMD["apn_pw"]=$arrMD["router_user"]=$arrMD["router_pw"]=""; }
          ?>
          <div role="tabpanel" class="tab-pane fade in" id="mobiledata">
            <div class="row">
              <div class="col-lg-6">
                <h4 class="mt-3"><?php echo _("Settings for Mobile Data Devices") ?></h4>
                  <hr />
                <form id="frm-mobiledata">
                  <?php echo CSRFTokenFieldTag() ?>
                  <div class="form-group">
                    <label for="pin-mobile"><?php echo _("PIN of SIM card") ?></label>
                    <input type="number" class="form-control" id="pin-mobile" placeholder="1234" value="<?php echo $arrMD["pin"]?>" >
                  </div>
                  <h4 class="mt-3"><?php echo _("APN Settings (Modem device ppp0)") ?></h4>
                  <div class="form-group">
                    <label for="apn-mobile"><?php echo _("Access Point Name (APN)") ?></label>
                    <input type="text" class="form-control" id="apn-mobile" placeholder="web.myprovider.com" value="<?php echo $arrMD["apn"]?>" >
                    <label for="apn-user-mobile"><?php echo _("Username") ?></label>
                    <input type="text" class="form-control" id="apn-user-mobile" value="<?php echo $arrMD["apn_user"]?>" >
                    <label for="apn-pw-mobile"><?php echo _("Password") ?></label>
                    <input type="text" class="form-control" id="apn-pw-mobile"  value="<?php echo $arrMD["apn_pw"]?>" >
                  </div>
                  <a href="#" class="btn btn-outline btn-primary intsave" data-int="mobiledata"><?php echo _("Save settings") ?></a>
                </form>

              </div>
            </div><!-- /.tab-panel -->
          </div>
          <div role="tabpanel" class="tab-pane fade in" id="netdevices">
            <h4 class="mt-3"><?php echo _("Properties of network devices") ?></h4>
            <div class="row">
             <div class="col-sm-12"">
              <div class="card ">
                <div class="card-body">
                  <form id="frm-netdevices">
                    <?php echo CSRFTokenFieldTag() ?>
                    <div class="table-responsive">
                      <table class="table table-hover">
                        <thead>
                          <tr>
                            <th><?php echo _("Device"); ?></th>
                            <th><?php echo _("Interface"); ?></th>
                            <th><?php echo _("Type"); ?></th>
                            <th><?php echo _("MAC"); ?></th>
                            <th><?php echo _("USB vid/pid"); ?></th>
                            <th style="min-width:6em"><?php echo _("Fixed name"); ?></th>
                            <th></th>
                          </tr>
                        </thead>
                        <tbody>
                        <?php
                           exec('/usr/local/sbin/getClients.sh all', $clients);
                           if(!empty($clients)) {
                              $clients=json_decode($clients[0],true);
                              $ncl=$clients["clients"];
                              if($ncl > 0) {
                                 foreach($clients["device"] as $dev) {
                                   echo "<tr>";
                                   echo "<td>".$dev["vendor"]." ".$dev["model"]."</td>\n";
                                   echo "<td>".$dev["name"]."</td>\n";
                                   $ty="Client";
                                   if($dev["type"] == 30) $ty="Access Point";
                                   echo "<td>".$ty."</td>\n";
                                   echo "<td>".$dev["mac"]."</td>\n";
                                   echo "<td>".$dev["vid"]."/".$dev["pid"]."</td>\n";
                                   $udevfile="/etc/udev/rules.d/80-net-devices.rules";
                                   $isStatic=array();
                                   exec('find /etc/udev/rules.d/  -type f \( -iname "*.rules" ! -iname "'.basename($udevfile).'" \) -exec grep -i '.$dev["mac"].' {} \; ',$isStatic);
                                   if(empty($isStatic))
                                     exec('find /etc/udev/rules.d/  -type f \( -iname "*.rules" ! -iname "'.basename($udevfile).'" \) -exec grep -i '.$dev["vid"].' {} \; | grep -i '.$dev["pid"].' ',$isStatic);
                                   $isStatic = empty($isStatic) ? false : true; 
                                   $devname=array();
                                   exec('grep -i '.$dev["vid"].' '.$udevfile.' | grep -i '.$dev["pid"].' | sed -rn \'s/.*name=\"(\w*)\".*/\1/ip\' ',$devname);
                                   if(!empty($devname)) $devname=$devname[0];
                                   else {
                                      exec('grep -i '.$dev["mac"].' '.$udevfile.' | sed -rn \'s/.*name=\"(\w*)\".*/\1/ip\' ',$devname);
                                      if(!empty($devname)) $devname=$devname[0];
                                   }
                                   if(empty($devname)) $devname="";
                                   echo '<td>';
                                   if (! $isStatic) echo '<input type="text" class="form-control" id="int-name-'.$dev["name"].'" size=10 value="'.$devname.'" >'."\n";
                                   else echo $dev["name"];
                                   echo '<input type="hidden" class="form-control" id="int-vid-'.$dev["name"].'" value="'.$dev["vid"].'" >'."\n";
                                   echo '<input type="hidden" class="form-control" id="int-pid-'.$dev["name"].'" value="'.$dev["pid"].'" >'."\n";
                                   echo '<input type="hidden" class="form-control" id="int-mac-'.$dev["name"].'" value="'.$dev["mac"].'" >'."\n";
                                   echo '</td>'."\n";
                                   echo '<td>';
                                   if (! $isStatic) echo '<a href="#" class="btn btn-secondary intsave" data-opts="'.$dev["name"].'" data-int="netdevices">Change</a>';
                                   echo "</td>\n";
                                   echo "</tr>\n";
                                 }
                              }
                           } else echo "<tr><td colspan=4>No network devices found</td></tr>";
                        ?>
                        </tbody>
                     </table>
                   </div>
                  </form>
                </div>
               </div>
             </div>
            </div><!-- /.tab-panel -->
          </div>

        </div><!-- /.tab-content -->
      </div><!-- /.card-body -->
      <div class="card-footer"><?php echo _("Information provided by /sys/class/net"); ?></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div>
