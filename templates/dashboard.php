<?php ob_start() ?>
    <?php if (!RASPI_MONITOR_ENABLED) : ?>
      <?php if ($state === "down") : ?>
        <input type="submit" class="btn btn-success" value="<?php echo _("Start").' '.$interface ?>" name="ifup_wlan0" />
      <?php else : ?>
        <input type="submit" class="btn btn-warning" value="<?php echo _("Stop").' '.$interface ?>"  name="ifdown_wlan0" />
      <?php endif ?>
    <?php endif ?>
    <button type="button" onClick="window.location.reload();" class="btn btn-outline btn-primary"><i class="fas fa-sync-alt"></i> <?php echo _("Refresh") ?></a>
<?php $buttons = ob_get_clean(); ob_end_clean() ?>

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
              <span class="icon"><i class="fas fa-circle service-status-<?php echo $state ?>"></i></span>
              <span class="text service-status">
                <?php echo strtolower($interface) .' '. _($state) ?>
              </span>
            </button>
          </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->
      <div class="card-wrapper">

        <div class="card">
          <div class="card-body">
            <?php $status->showMessages(); ?>
            <h4 class="card-title">
              <?php echo _('Current status'); ?>
            </h4>
            <div class="dashboard-container row">
              <div class="connections-left col-lg-4">
                <div class="connection-item">
                  <a href="/network_conf" class="<?php echo $ethernetActive; ?>"><span><?php echo _("Ethernet"); ?></span></a>
                  <a href="/network_conf" class="<?php echo $ethernetActive; ?>"><i class="fas fa-ethernet fa-2xl"></i></a>
                </div>
                <div class="connection-item">
                  <a href="/network_conf" class="<?php echo $wirelessActive; ?>"><span><?php echo _("Repeater"); ?></span>
                  <a href="/network_conf" class="<?php echo $wirelessActive; ?>"><i class="fas fa-wifi fa-2xl"></i></a>
                </div>
                <div class="connection-item">
                  <a href="/network_conf" class="<?php echo $tetheringActive; ?>"><span><?php echo _("Tethering"); ?></span></a>
                  <a href="/network_conf" class="<?php echo $tetheringActive; ?>"><i class="fas fa-mobile-alt fa-2xl"></i></a>
                </div>
                <div class="connection-item">
                  <a href="/network_conf" class="<?php echo $cellularActive; ?>"><span><?php echo _("Cellular"); ?></span></a>
                  <a href="/network_conf" class="<?php echo $cellularActive; ?>"><i class="fas fa-broadcast-tower fa-2xl"></i></a>
                </div>
                <img src="app/img/dashed.svg" class="dashed-lines" alt="">
                <img src="<?php echo htmlspecialchars(renderConnection($connectionType)); ?>" class="solid-lines" alt="Network connection">
              </div>
              <div class="center-device col-12 col-lg-4">
                <div class="center-device-top">
                  <a href="/system_info"><img class="device-illustration" src="app/img/device.php" alt="<?php echo htmlspecialchars($revision, ENT_QUOTES); ?>"></a>
                  <div class="device-label"><a href="/system_info"><?php echo htmlspecialchars($revision, ENT_QUOTES); ?></a></div>
                  <div class="mt-1 small">IP address: <a href="/dhcpd_conf"><?php echo htmlspecialchars($ipv4Address, ENT_QUOTES); ?></a></div>
                  <div class="small">Netmask: <a href="/dhcpd_conf"><?php echo htmlspecialchars($ipv4Netmask, ENT_QUOTES); ?></a></div>
                  <div class="small">MAC address: <a href="/dhcpd_conf"><?php echo htmlspecialchars($macAddress, ENT_QUOTES); ?></a></div>
                  <div class="small">SSID: <a href="/hostapd_conf"><?php echo htmlspecialchars($ssid, ENT_QUOTES); ?></a></div>
                </div>

                <div class="bottom">
                  <div class="device-status">
                    <a href="/hostapd_conf">
                    <div class="status-item <?php echo $hostapdStatus; ?>">
                      <i class="fas fa-bullseye fa-2xl"></i>
                      <span><?php echo _('AP'); ?></span>
                    </div>
                    </a>
                    <a href="/hostapd_conf">
                    <div class="status-item <?php echo $bridgedStatus; ?>">
                      <i class="fas fa-bridge fa-2xl"></i>
                      <span><?php echo _('Bridged'); ?></span>
                    </div>
                    </a>
                    <a href="/adblock_conf">
                    <div class="status-item <?php echo $adblockStatus; ?>">
                      <i class="far fa-hand-paper fa-2xl"></i>
                      <span><?php echo _('Adblock'); ?></span>
                    </div>
                    </a>
                    <a href="<?php echo $vpnManaged; ?>">
                    <div class="status-item <?php echo $vpnStatus; ?>">
                      <i class="fas fa-shield-alt fa-2xl"></i>
                      <span><?php echo _('VPN'); ?></span>
                    </div>
                    </a>
                    <div class="status-item <?php echo $firewallStatus; ?>">
                      <span class="fa-stack fa-2xl" style="line-height: 0!important;height: 100%!important;">
                        <i class="fas fa-fire-flame-curved fa-stack-1x"></i>
                        <?php echo $firewallUnavailable; ?>
                      </span>
                      <span><?php echo _('Firewall'); ?></span>
                    </div>
                  </div>

                  <div class="wifi-bands">
                    <a href="/hostapd_conf"><span class="band <?php echo $freq5active; ?>">5G</span></a>
                    <a href="/hostapd_conf"><span class="band <?php echo $freq24active; ?>">2.4G</span></a>
                  </div>
                </div>

                <div class="clients-mobile">
                  <div class="client-type">
                    <a href="/network_conf">
                      <i class="fas fa-globe"></i>
                      <div class="client-count">
                        <i class="fas <?php echo $connectionIcon; ?> badge-icon"></i>
                      </div>
                    </a>
                  </div>
                  <div class="client-type">
                    <a href="/dhcpd_conf">
                      <i class="fas fa-laptop <?php echo $totalClientsActive; ?>"></i>
                      <span class="client-count"><?php echo $totalClients; ?></span>
                    </a>
                  </div>
                </div>
              </div>

              <div class="connections-right col-lg-4">
                <div class="d-flex flex-column justify-content-around h-100">
                  <div class="connection-item connection-right">
                    <a href="/dhcpd_conf" class="<?php echo $wirelessClientActive; ?>">
                      <span class="fa-stack">
                        <i class="fas fa-laptop fa-stack-1x fa-2xl"></i>
                        <i class="fas fa-wifi fa-stack-1x fa-xs"></i>
                      </span>
                    </a>
                    <a href="/dhcpd_conf"><span class="text-nowrap <?php echo $wirelessClientActive; ?>"><?php echo $wirelessClientLabel; ?></span></a>
                  </div>
                  <div class="connection-item connection-right">
                    <a href="/dhcpd_conf" class="<?php echo $ethernetClientActive; ?>">
                      <span class="fa-stack">
                        <i class="fas fa-laptop fa-stack-1x fa-2xl"></i>
                        <i class="fas fa-ethernet fa-stack-1x fa-xs"></i>
                      </span>
                    </a>
                    <a href="/dhcpd_conf"><span class="text-nowrap <?php echo $ethernetClientActive; ?>"><?php echo $ethernetClientLabel; ?></span></a>
                  </div>
                </div>
                <?php echo renderClientConnections($wirelessClients, $ethernetClients); ?>
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
            <?php echo $buttons ?>
          </form>
        </div>
      </div>
      <div class="card-footer"><?php echo _("Information provided by raspap.sysinfo"); ?></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->

