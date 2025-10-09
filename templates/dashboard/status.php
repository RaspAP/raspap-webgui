<div class="tab-pane active" id="status">
  <h4 class="card-title mt-3">
    <?php echo _("Current status"); ?>
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
        <a href="/system_info"><img class="device-illustration" src="app/img/devices/<?php echo $deviceImage; ?>" alt="<?php echo htmlspecialchars($revision, ENT_QUOTES); ?>"></a>
        <div class="device-label"><a href="/system_info"><?php echo htmlspecialchars($revision, ENT_QUOTES); ?></a></div>
        <div class="mt-1 small"><?php echo _("IP Address"); ?>: <a href="/dhcpd_conf"><?php echo htmlspecialchars($ipv4Address, ENT_QUOTES); ?></a></div>
        <div class="small"><?php echo _("Netmask"); ?>: <a href="/dhcpd_conf"><?php echo htmlspecialchars($ipv4Netmask, ENT_QUOTES); ?></a></div>
        <div class="small"><?php echo _("MAC Address"); ?>: <a href="/dhcpd_conf"><?php echo htmlspecialchars($macAddress, ENT_QUOTES); ?></a></div>
        <div class="small"><?php echo _("SSID"); ?>: <a href="/hostapd_conf"><?php echo htmlspecialchars($ssid, ENT_QUOTES); ?></a></div>
      </div>

      <div class="bottom">
        <div class="device-status">
          <a href="/hostapd_conf">
            <div class="status-item <?php echo $hostapdStatus; ?>">
              <i class="fas fa-bullseye fa-2xl"></i>
              <span><?php echo _('AP'); ?></span>
            </div>
          </a>
          <a href="/hostapd_conf?tab=advanced">
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
          <?php echo $firewallManaged; ?>
            <div class="status-item <?php echo $firewallStatus; ?>">
              <span class="fa-stack fa-2xl" style="line-height: 0!important;height: 100%!important;">
                <i class="fas fa-fire-flame-curved fa-stack-1x"></i>
                <?php echo $firewallUnavailable; ?>
              </span>
              <span><?php echo _('Firewall'); ?></span>
            </div>
          </a>
        </div>

        <div class="wifi-bands">
          <a href="/hostapd_conf"><span class="band <?php echo $freq5active; ?>"><?php echo _("5G"); ?></span></a>
          <a href="/hostapd_conf"><span class="band <?php echo $freq24active; ?>"><?php echo _("2.4G"); ?></span></a>
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
</div><!-- /.tab-pane | status tab -->

