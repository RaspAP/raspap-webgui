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
          <?php if (!$bridgedEnabled) : // no interface details when bridged ?>
            <li role="presentation" class="nav-item"><a class="nav-link" href="#netdevices" aria-controls="netdevices" role="tab" data-toggle="tab"><?php echo _("Network Devices"); ?></a></li>
            <li role="presentation" class="nav-item"><a class="nav-link" href="#mobiledata" aria-controls="mobiledata" role="tab" data-toggle="tab"><?php echo _("Mobile Data Settings"); ?></a></li>
          <?php endif ?>
        </ul>
        <div class="tab-content">
          <div role="tabpanel" class="tab-pane active" id="summary">
            <h4 class="mt-3"><?php echo _("Internet connection"); ?></h4>
            <div class="row">
              <div class="col-sm-12">
                <div class="table-responsive">
                  <table class="table">
                    <thead>
                      <tr>
                        <th><?php echo _("Interface"); ?></th>
                        <th><?php echo _("IP Address"); ?></th>
                        <th><?php echo _("Gateway"); ?></th>
                        <th colspan="2"><?php echo _("Internet Access"); ?></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (isset($routeInfo["error"]) || empty($routeInfo)): ?>
                      <tr><td colspan=5>No route to the internet found</td></tr>
                      <?php else: ?>
                        <?php foreach($routeInfo as $route): ?>
                          <tr>
                            <td><?php echo $route['interface'] ?></td>
                            <td><?php echo $route['ip-address'] ?></td>
                            <td><?php echo $route['gateway'] ?><br><?php $route['gw-name'] ?></td>
                            <td>
                              <p class="m-0">
                                <i class="fas <?php echo $route["access-ip"] ? "fa-check" : "fa-times"; ?>"></i> <?php echo RASPI_ACCESS_CHECK_IP ?>
                              </p>
                              <p class="m-0">
                                <i class="fas <?php echo $route["access-dns"] ? "fa-check" : "fa-times"; ?>"></i> <?php echo RASPI_ACCESS_CHECK_DNS ?>
                              </p>
                            </td>
                          </tr>
                        <?php endforeach ?>
                      <?php endif ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <h4 class="mt-3"><?php echo _("Current settings") ?></h4>
            <div class="row">
              <?php if (!$bridgedEnabled) : // No interface details when bridged ?>
                <?php foreach ($interfaces as $if): ?>
                  <?php $if_quoted = htmlspecialchars($if, ENT_QUOTES) ?>
                  <div class="col-md mb-3">
                    <div class="card h-100 w-100">
                      <div class="card-header"><?php echo $if_quoted ?></div>
                      <div class="card-body">
                        <pre class="unstyled" id="<?php echo $if_quoted ?>-summary"></pre>
                      </div>
                    </div>
                  </div>
                <?php endforeach ?>
              <?php endif ?>
            </div><!-- /.row -->

            <button type="button" onClick="window.location.reload();" class="btn btn-outline btn-primary"><i class="fas fa-sync-alt"></i> <?php echo _("Refresh") ?></a>

          </div>
          <?php $arrMD = file_exists(($f = RASPI_MOBILEDATA_CONFIG)) ? parse_ini_file($f) : false;
                if ($arrMD==false) { $arrMD=[]; $arrMD["pin"]=$arrMD["apn"]=$arrMD["apn_user"]=$arrMD["apn_pw"]=$arrMD["router_user"]=$arrMD["router_pw"]=""; }
          ?>
          <div role="tabpanel" class="tab-pane fade in" id="netdevices">
            <h4 class="mt-3"><?php echo _("Properties of network devices") ?></h4>
            <div class="row">
             <div class="col-sm-12">
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
                            <th></th>
                            <th><?php echo _("MAC"); ?></th>
                            <th><?php echo _("USB vid/pid"); ?></th>
                            <th><?php echo _("Device type"); ?></th>
                            <th style="min-width:6em"><?php echo _("Fixed name"); ?></th>
                            <th></th>
                          </tr>
                        </thead>
                        <tbody>
                        <?php
                           if(!empty($clients)) {
                              $ncl=$clients["clients"];
                              if($ncl > 0) {
                                 foreach($clients["device"] as $id => $dev) {
                                   echo "<tr>";
                                   echo "<td>".$dev["vendor"]." ".$dev["model"]."</td>\n";
                                   echo "<td>".$dev["name"]."</td>\n";
                                   $ty="Client";
                                   if(isset($dev["isAP"]) && $dev["isAP"]) $ty="Access Point";
                                   echo "<td>".$ty."</td>\n";
                                   echo "<td>".$dev["mac"]."</td>\n";
                                   if(isset($dev["vid"]) && !empty($dev["vid"])) echo "<td>".$dev["vid"]."/".$dev["pid"]."</td>\n";
                                   else echo "<td> - </td>\n";
                                   $udevfile=$_SESSION["udevrules"]["udev_rules_file"];
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
                                   $isStatic = $isStatic || in_array($dev["type"],array("ppp","tun"));
                                   $txtdisabled=$isStatic ? "disabled":"";
                                   echo '<td><select '.$txtdisabled.' class="selectpicker form-control" id="int-new-type-'.$dev["name"].'">';
                                   foreach($_SESSION["net-device-types"] as $i => $type) {
                                     $txt=$_SESSION["net-device-types-info"][$i];
                                     $txtdisabled =   in_array($type,array("ppp","tun")) ? "disabled":"";
                                     if(preg_match("/^".$_SESSION["net-device-name-prefix"][$i].".*$/",$dev["type"])===1) echo '<option '.$txtdisabled.' selected value="'.$type.'">'.$txt.'</option>';
                                     else echo '<option '.$txtdisabled.' value="'.$type.'">'.$txt.'</option>';
                                   }
                                   echo "</select></td>";
                                   echo '<td>';
                                   if (! $isStatic ) echo '<input type="text" class="form-control" id="int-name-'.$dev["name"].'" value="'.$devname.'" >'."\n";
                                   else echo $dev["name"];
                                   echo '<input type="hidden" class="form-control" id="int-vid-'.$dev["name"].'" value="'.$dev["vid"].'" >'."\n";
                                   echo '<input type="hidden" class="form-control" id="int-pid-'.$dev["name"].'" value="'.$dev["pid"].'" >'."\n";
                                   echo '<input type="hidden" class="form-control" id="int-mac-'.$dev["name"].'" value="'.$dev["mac"].'" >'."\n";
                                   echo '<input type="hidden" class="form-control" id="int-type-'.$dev["name"].'" value="'.$dev["type"].'" >'."\n";
                                   echo '</td>'."\n";
                                   echo '<td>';
                                   if (! $isStatic) echo '<a href="#" class="btn btn-secondary intsave" data-opts="'.$dev["name"].'" data-int="netdevices">' ._("Change").'</a>';
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
            </div>
          </div><!-- /.tab-panel -->
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
		    </div>
          </div><!-- /.tab-panel -->
        </div>
      </div><!-- /.card-body -->

      <div class="card-footer"><?php echo _("Information provided by /sys/class/net"); ?></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->

