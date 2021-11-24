<div class="row">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col">
            <i class="fas fa-tachometer-alt fa-fw mr-2"></i><?php echo _("Dashboard"); ?>
          </div>
          <div class="col">
            <button class="btn btn-light btn-icon-split btn-sm service-status float-right">
              <span class="icon"><i class="fas fa-circle service-status-<?php echo $ifaceStatus ?>"></i></span>
              <span class="text service-status"><?php echo $type_name; if ( $isClientConfigured ) echo ' '. _($ifaceStatus); ?></span>
            </button>
          </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->
      <div class="card-body">
        <div class="row">

          <div class="col-lg-12">
            <div class="card mb-3">
              <div class="card-body">
                <h4 class="card-title"><?php echo _("Hourly traffic amount"); ?></h4>
                <div id="divInterface" class="d-none"><?php echo $apInterface; ?></div>
                <div class="col-md-12">
                  <canvas id="divDBChartBandwidthhourly"></canvas>
                </div>
              </div><!-- /.card-body -->
            </div><!-- /.card-->
          </div>

          <div class="col-sm-6 align-items-stretch">
            <div class="card h-100">
              <div class="card-body wireless">
                <h4 class="card-title"><?php echo _("$client_title"); ?></h4>
                <div class="row ml-1">
                  <div class="col-sm">
                    <?php $valEcho=function($cl,$id) {$val = isset($cl[$id])&& !empty($cl[$id]) ? $cl[$id] : "-"; echo  htmlspecialchars($val,ENT_QUOTES);} ?>
                    <?php if ($clientinfo["type"] == "wlan") : // WIRELESS ?>
                      <div class="row mb-1">
                        <div class="info-item col-xs-3"><?php echo _("Connected To"); ?></div><div class="info-value col-xs-3"><?php $valEcho($clientinfo,"ssidutf8"); ?></div>
                      </div>
                      <div class="row mb-1">
                        <div class="info-item col-xs-3"><?php echo _("AP Mac Address"); ?></div><div class="info-value col-xs-3"><?php $valEcho($clientinfo,"ap-mac"); ?></div>
                      </div>
                      <div class="row mb-1">
                        <div class="info-item col-xs-3"><?php echo _("Bitrate"); ?></div><div class="info-value col-xs-3"><?php $valEcho($clientinfo,"bitrate"); ?></div>
                      </div>
                      <div class="row mb-1">
                        <div class="info-item col-xs-3"><?php echo _("Signal Level"); ?></div><div class="info-value col-xs-3"><?php $valEcho($clientinfo,"signal"); ?></div>
                      </div>
                      <div class="row mb-1">
                        <div class="info-item col-xs-3"><?php echo _("Transmit Power"); ?></div><div class="info-value col-xs-3"><?php echo htmlspecialchars($txPower, ENT_QUOTES); ?></div>
                      </div>
                      <div class="row mb-1">
                        <div class="info-item col-xs-3"><?php echo _("Frequency"); ?></div><div class="info-value col-xs-3"><?php $valEcho($clientinfo,"freq"); ?></div>
                      </div>
                    <?php elseif ($clientinfo["type"] == "phone" ) : // Smartphones (tethering over USB) ?>
                      <div class="row mb-1">
                        <div class="info-item col-xs-3"><?php echo _("Device"); ?></div><div class="info-value col-xs-3"><?php $valEcho($clientinfo,"vendor")." ". $valEcho($clientinfo,"model"); ?></div>
                      </div>
                      <div class="row mb-1">
                        <div class="info-item col-xs-3"><?php echo _("IP Address"); ?></div><div class="info-value col-xs-3"><?php $valEcho($clientinfo,"ipaddress"); ?></div>
                      </div>
                    <?php elseif ($clientinfo["type"] == "hilink" ) : // MOBILE DATA - ROUTER MODE (HILINK) ?>
                      <?php
                          exec('ip route list |  sed -rn "s/default via (([0-9]{1,3}\.){3}[0-9]{1,3}).*dev '.$clientinfo["name"].'.*/\1/p"',$gw); // get gateway
                          $gw=empty($gw) ? "" : $gw[0];
                      ?>
                      <div class="row mb-1">
                        <div class="info-item col-xs-3"><?php echo _("Device"); ?></div><div class="info-value col-xs-3"><?php $valEcho($clientinfo,"model")." (Hilink)"; ?></div>
                      </div>
                      <div class="row mb-1">
                        <div class="info-item col-xs-3"><?php echo _("Connection mode"); ?></div><div class="info-value col-xs-3"><?php $valEcho($clientinfo,"mode"); ?></div>
                      </div>
                      <div class="row mb-1">
                        <div class="info-item col-xs-3"><?php echo _("Signal quality"); ?></div><div class="info-value col-xs-3"><?php $valEcho($clientinfo,"signal"); ?></div>
                      </div>
                      <div class="row mb-1">
                        <div class="info-item col-xs-3"><?php echo _("Network"); ?></div><div class="info-value col-xs-3"><?php $valEcho($clientinfo,"operator"); ?></div>
                      </div>
                      <div class="row mb-1">
                        <div class="info-item col-xs-3"><?php echo _("WAN IP"); ?></div><div class="info-value col-xs-3"><?php $valEcho($clientinfo,"wan_ip"); ?></div>
                      </div>
                      <div class="row mb-1">
                        <div class="info-item col-xs-3"><?php echo _("Web-GUI"); ?></div><div class="info-value col-xs-3"><?php if(!empty($gw)) echo '<a href="http://'.$gw.'" >'.$gw."</a>"; ?></div>
                      </div>
                    <?php elseif ($clientinfo["type"] == "ppp" ) : // MOBILE DATA MODEM) ?>
                      <div class="row mb-1">
                        <div class="info-item col-xs-3"><?php echo _("Device"); ?></div><div class="info-value col-xs-3"><?php $valEcho($clientinfo,"model"); ?></div>
                      </div>
                      <div class="row mb-1">
                        <div class="info-item col-xs-3"><?php echo _("Connection mode"); ?></div><div class="info-value col-xs-3"><?php $valEcho($clientinfo,"mode"); ?></div>
                      </div>
                      <div class="row mb-1">
                        <div class="info-item col-xs-3"><?php echo _("Signal strength"); ?></div><div class="info-value col-xs-3"><?php $valEcho($clientinfo,"signal"); ?></div>
                      </div>
                      <div class="row mb-1">
                        <div class="info-item col-xs-3"><?php echo _("Network"); ?></div><div class="info-value col-xs-3"><?php  $valEcho($clientinfo,"operator"); ?></div>
                      </div>
                    <?php elseif  ($clientinfo["type"] == "eth" ) : // ETHERNET ?>
                      <div class="row mb-1">
                        <div class="info-item col-xs-3"><?php echo _("Device"); ?></div><div class="info-value col-xs-3"><?php $valEcho($clientinfo,"vendor")." ".$valEcho($clientinfo,"model"); ?></div>
                      </div>
                      <div class="row mb-1">
                        <div class="info-item col-xs-3"><?php echo _("IP Address"); ?></div><div class="info-value col-xs-3"><?php echo $valEcho($clientinfo,"ipaddress"); ?></div>
                      </div>
                    <?php else : // NO CLIENT ?>
                      <div class="row mb-1">
                        <div class="info-item col-xs-3"><?php echo _("No Client device or not yet configured"); ?></div>
                      </div>
                    <?php endif; ?>
                  </div>
                  <?php if ($isClientConfigured) : ?>
                     <div class="col-md d-flex">
                        <?php
                          preg_match("/.*\((\s*\d*)\s*%\s*\)/",$clientinfo["signal"],$match);
                          $strLinkQuality=array_key_exists(1,$match) ? $match[1] : 0;
                        ?>
                        <script>var linkQ = <?php echo json_encode($strLinkQuality); ?>;</script>
                        <div class="chart-container">
                           <canvas id="divChartLinkQ"></canvas>
                        </div>
                     </div>
                  <?php endif; ?>
                </div><!--row-->
              </div><!-- /.card-body -->
            </div><!-- /.card -->
          </div><!-- /.col-md-6 -->
          <div class="col-sm-6">
            <div class="card h-100 mb-3">
              <div class="card-body">
                <h4 class="card-title"><?php echo _("Connected Devices"); ?></h4>
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <?php if ($bridgedEnable == 1) : ?>
                          <th><?php echo _("MAC Address"); ?></th>
                        <?php else : ?>
                          <th><?php echo _("Host name"); ?></th>
                          <th><?php echo _("IP Address"); ?></th>
                          <th><?php echo _("MAC Address"); ?></th>
                        <?php endif; ?>
                      </tr>
                    </thead>
                    <tbody>
                        <?php if ($bridgedEnable == 1) : ?>
                          <tr>
                            <td><small class="text-muted"><?php echo _("Bridged AP mode is enabled. For Hostname and IP, see your router's admin page.");?></small></td>
                          </tr>
                        <?php endif; ?>
                        <?php foreach (array_slice($clients,0, 2) as $client) : ?>
                        <tr>
                          <?php if ($bridgedEnable == 1): ?>
                            <td><?php echo htmlspecialchars($client, ENT_QUOTES) ?></td>
                          <?php else : ?>
                            <?php $props = explode(' ', $client) ?>
                            <td><?php echo htmlspecialchars($props[3], ENT_QUOTES) ?></td>
                            <td><?php echo htmlspecialchars($props[2], ENT_QUOTES) ?></td>
                            <td><?php echo htmlspecialchars($props[1], ENT_QUOTES) ?></td>
                          <?php endif; ?>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                  </table>
                  <?php if (sizeof($clients) >2) : ?>
                      <div class="col-lg-12 float-right">
                        <a class="btn btn-outline-info" role="button" href="<?php echo $moreLink ?>"><?php echo _("More");?>  <i class="fas fa-chevron-right"></i></a>
                      </div>
                  <?php elseif (sizeof($clients) ==0) : ?>
                      <div class="col-lg-12 mt-3"><?php echo _("No connected devices");?></div>
                  <?php endif; ?>
                </div><!-- /.table-responsive -->
              </div><!-- /.card-body -->
            </div><!-- /.card -->
          </div><!-- /.col-md-6 -->
        </div><!-- /.row -->
        <div class="col-lg-12 mt-3">
          <div class="row">
            <form action="wlan0_info" method="POST">
                <?php echo CSRFTokenFieldTag(); ?>
                <?php if (!RASPI_MONITOR_ENABLED) : ?>
                    <?php if ($ifaceStatus == "down") : ?>
                    <input type="submit" class="btn btn-success" value="<?php echo _("Start").' '.$type_name ?>" name="ifup_wlan0" data-toggle="modal" data-target="#switchClientModal"/>
                    <?php elseif ($ifaceStatus == "up") : ?>
                    <input type="submit" class="btn btn-warning" value="<?php echo _("Stop").' '.$type_name ?>"  name="ifdown_wlan0" data-toggle="modal" data-target="#switchClientModal"/>
                    <?php endif ?>
                <?php endif ?>
              <button type="button" onClick="window.location.reload();" class="btn btn-outline btn-primary"><i class="fas fa-sync-alt"></i> <?php echo _("Refresh") ?></button>
            </form>
          </div>
        </div>
      </div><!-- /.card-body -->
      <div class="card-footer"><?php echo _("Information provided by ip and iw and from system"); ?></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->

<!-- Modal -->
<div class="modal fade" id="switchClientModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <div class="modal-title" id="ModalLabel">
            <i class="fas fa-sync-alt mr-2"></i>
            <?php if($ifaceStatus=="down") echo _("Waiting for the interface to start ..."); else  echo _("Stop the Interface"); ?>
        </div>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span>
      </div>
  </div>
</div>

<script type="text/javascript"<?php //echo ' nonce="'.$csp_page_nonce.'"'; ?>>
// js translations:
var t = new Array();
t['send'] = '<?php echo addslashes(_('Send')); ?>';
t['receive'] = '<?php echo addslashes(_('Receive')); ?>';
</script>
