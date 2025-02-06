<div class="row">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col">
            <i class="fas fa-tachometer-alt fa-fw me-2"></i>
            <?php echo _("Dashboard"); ?>
          </div>
          <div class="col">
            <button class="btn btn-light btn-icon-split btn-sm service-status float-end">
              <span class="icon"><i class="fas fa-circle service-status-<?php echo $ifaceStatus ?>"></i></span>
              <span class="text service-status">
                <?php echo strtolower($apInterface) .' '. _($ifaceStatus) ?>
              </span>
            </button>
          </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->
      <div class="card-wrapper">

        <div class="card">

          <div class="card-body">
            <h4 class="card-title">
              <?php echo _('Current status'); ?>
            </h4>

            <div class="dashboard-container row">
              <div class="connections-left col-lg-4">
                <div class="connection-item active">
                  <span>Ethernet</span>
                  <i class="fas fa-ethernet fa-2xl"></i>
                </div>
                <div class="connection-item">
                  <span>Repeater</span>
                  <i class="fas fa-wifi fa-2xl"></i>
                </div>
                <div class="connection-item">
                  <span>Tethering</span>
                  <i class="fas fa-mobile-alt fa-2xl"></i>
                </div>
                <div class="connection-item">
                  <span>Cellular</span>
                  <i class="fas fa-broadcast-tower fa-2xl"></i>
                </div>
                <img src="app/img/dashed.svg" class="dashed-lines" alt="">
                <img src="app/img/solid.php?joint&device-1&out&device-2" class="solid-lines" alt="">
              </div>

              <div class="center-device col-12 col-lg-4">
                <div class="top" style="margin-bottom: 50px">
                  <img class="device-illustration" src="app/img/device.svg" alt="Raspberry Pi">
                  <div class="device-label">Raspberry Pi 3 Model B+</div>
                </div>

                <div class="bottom">
                  <div class="device-status ">
                    <div class="status-item active">
                      <i class="fas fa-bullseye fa-2xl"></i>
                      <span>
                        <?php echo _('AP'); ?>
                      </span>
                    </div>
                    <div class="status-item">
                      <i class="fas fa-bridge fa-2xl"></i>
                      <span>
                        <?php echo _('Bridged'); ?>
                      </span>
                    </div>
                    <div class="status-item">
                      <i class="fas fa-hand-paper fa-2xl"></i>
                      <span>
                        <?php echo _('Adblock'); ?>
                      </span>
                    </div>
                    <div class="status-item">
                      <i class="fas fa-shield-alt fa-2xl"></i>
                      <span>
                        <?php echo _('VPN'); ?>
                      </span>
                    </div>
                    <div class="status-item disabled">
                      <span class="fa-stack fa-2xl" style="line-height: 0!important;height: 100%!important;">
                        <i class="fas fa-fire-flame-curved fa-stack-1x"></i>
                        <i class="fas fa-slash fa-stack-1x"></i>
                      </span>
                      <span>
                        <?php echo _('Firewall'); ?>
                      </span>
                    </div>
                  </div>

                  <div class="wifi-bands">
                    <span class="band active">5G</span>
                    <span class="band">2.4G</span>
                  </div>
                </div>

                <div class="clients-mobile">
                  <div class="client-type">
                    <i class="fas fa-globe"></i>
                    <div class="client-count">
                      <i class="fas fa-ethernet badge-icon"></i>
                    </div>
                  </div>
                  <div class="client-type">
                    <i class="fas fa-laptop"></i>
                    <span class="client-count">3</span>
                  </div>
                </div>
              </div>

              <div class="connections-right col-lg-4">
                <div class="d-flex flex-column justify-content-around h-100">
                  <div class="connection-item connection-right">
                    <span class="fa-stack">
                      <i class="fas fa-laptop fa-stack-1x fa-2xl"></i>
                      <i class="fas fa-wifi fa-stack-1x fa-xl" style="line-height: 0!important;"></i>
                    </span>
                    <span>3 WLAN Clients</span>
                  </div>
                  <div class="connection-item connection-right">
                    <span class="fa-stack">
                      <i class="fas fa-laptop fa-stack-1x fa-2xl"></i>
                      <i class="fas fa-wifi fa-stack-1x fa-xl" style="line-height: 0!important;"></i>
                    </span>
                    <span>1 LAN Client</span>
                  </div>
                </div>

                <img src="app/img/right-solid.php?device-1&out" class="solid-lines solid-lines-right" alt="">
                <img src="app/img/right-dashed.svg" class="dashed-lines dashed-lines-right" alt="">
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-12 mt-3">

        </div>
        <div class="row">
          <form action="wlan0_info" method="POST">
            <?php echo CSRFTokenFieldTag() ?>
            <?php if (!RASPI_MONITOR_ENABLED) : ?>
            <?php if (!$wlan0up) : ?>
            <input type="submit" class="btn btn-success" value="<?php echo _(" Start").' '.$apInterface ?>" name="ifup_wlan0" />
                  <?php else : ?>
                  <input type="submit" class="btn btn-warning" value="<?php echo _("Stop").' '.$apInterface ?>"  name="ifdown_wlan0" />
                  <?php endif ?>
              <?php endif ?>
            <button type="button" onClick="window.location.reload();" class="btn btn-outline btn-primary"><i class="fas fa-sync-alt"></i> <?php echo _("Refresh") ?></a>
          </form>
        </div>
      </div>



      <div class="card-footer"><?php echo _("Information provided by ip and iw and from system"); ?></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->
<script type="text/javascript"<?php //echo ' nonce="'.$csp_page_nonce.'"'; ?>>
// js translations:
var t = new Array();
  t[' send'] = '<?php echo addslashes(_(' Send')); ?>';
  t['receive'] = '
    <? php echo addslashes(_('Receive')); ?> ';
</script>