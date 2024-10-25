        <div class="row g-0">
            <div class="col-4 ms-2 sidebar-brand-icon">
                <img src="app/img/raspAP-logo.php" class="navbar-logo" width="60" height="60">
            </div>
          <div class="col ml-2">
            <div class="ml-1 sb-status">Status</div>
            <div class="info-item-xs"><span class="icon">
              <i class="fas fa-circle <?php echo ($hostapd_led); ?>"></i></span> <?php echo _("Hotspot").' '. _($hostapd_status); ?>
            </div>
            <div class="info-item-xs"><span class="icon">
              <i class="fas fa-circle <?php echo ($memused_led); ?>"></i></span> <?php echo _("Mem Use").': '. htmlspecialchars(strval($memused), ENT_QUOTES); ?>%
            </div>
            <div class="info-item-xs"><span class="icon">
              <i class="fas fa-circle <?php echo ($cputemp_led); ?>"></i></span> <?php echo _("CPU").': '. htmlspecialchars($cputemp, ENT_QUOTES); ?>°C
            </div>
          </div>
        </div>
        <div class="sb-nav-link-icon px-2">
          <a class="nav-link" href="wlan0_info"><i class="sb-nav-link-icon fas fa-tachometer-alt fa-fw mr-2"></i><span class="nav-label"><?php echo _("Dashboard"); ?></span></a>
        </div>
          <?php if (RASPI_HOTSPOT_ENABLED) : ?>
        <div class="sb-nav-link-icon px-2">
          <a class="nav-link" href="hostapd_conf"><i class="sb-nav-link-icon far fa-dot-circle fa-fw mr-2"></i><span class="nav-label"><?php echo _("Hotspot"); ?></a>
        </div>
          <?php endif; ?>
          <?php if (RASPI_DHCP_ENABLED && !$_SESSION["bridgedEnabled"]) : ?>
        <div class="sb-nav-link-icon px-2">
          <a class="nav-link" href="dhcpd_conf"><i class="sb-nav-link-icon fas fa-exchange-alt fa-fw mr-2"></i><span class="nav-label"><?php echo _("DHCP Server"); ?></a>
        </div>
          <?php endif; ?>
          <?php if (RASPI_ADBLOCK_ENABLED && !$_SESSION["bridgedEnabled"]) : ?>
        <div class="sb-nav-link-icon px-2">
           <a class="nav-link" href="adblock_conf"><i class="sb-nav-link-icon far fa-hand-paper fa-fw mr-2"></i><span class="nav-label"><?php echo _("Ad Blocking"); ?></a>
        </div>
          <?php endif; ?>
          <?php if (RASPI_NETWORK_ENABLED) : ?>
        <div class="sb-nav-link-icon px-2">
           <a class="nav-link" href="network_conf"><i class="sb-nav-link-icon fas fa-network-wired fa-fw mr-2"></i><span class="nav-label"><?php echo _("Networking"); ?></a>
        </div> 
          <?php endif; ?>
          <?php if (RASPI_WIFICLIENT_ENABLED && !$_SESSION["bridgedEnabled"]) : ?>
        <div class="sb-nav-link-icon px-2">
          <a class="nav-link" href="wpa_conf"><i class="sb-nav-link-icon fas fa-wifi fa-fw mr-2"></i><span class="nav-label"><?php echo _("WiFi client"); ?></span></a>
        </div>
          <?php endif; ?>
          <?php if (RASPI_OPENVPN_ENABLED) : ?>
        <div class="sb-nav-link-icon px-2">
          <a class="nav-link" href="openvpn_conf"><i class="sb-nav-link-icon fas fa-key fa-fw mr-2"></i><span class="nav-label"><?php echo _("OpenVPN"); ?></a>
        </div>
          <?php endif; ?>
          <?php if (RASPI_WIREGUARD_ENABLED) : ?>
        <div class="sb-nav-link-icon px-2">
          <a class="nav-link" href="wg_conf"><i class="sb-nav-link-icon ra-wireguard mr-2"></i><span class="nav-label"><?php echo _("WireGuard"); ?></a>
        </div>
          <?php endif; ?>
          <?php if (RASPI_VPN_PROVIDER_ENABLED) : ?>
        <div class="sb-nav-link-icon px-2">
          <a class="nav-link" href="provider_conf"><i class="sb-nav-link-icon fas fa-shield-alt fa-fw mr-2"></i><span class="nav-label"><?php echo _(getProviderValue($_SESSION["providerID"], "name")); ?></a>
        </div>
          <?php endif; ?>
          <?php if (RASPI_TORPROXY_ENABLED) : ?>
        <div class="sb-nav-link-icon px-2">
           <a class="nav-link" href="torproxy_conf"><i class="sb-nav-link-icon fas fa-eye-slash fa-fw mr-2"></i><span class="nav-label"><?php echo _("TOR proxy"); ?></a>
        </div>
          <?php endif; ?>
          <?php if (RASPI_CONFAUTH_ENABLED) : ?>
        <div class="sb-nav-link-icon px-2">
        <a class="nav-link" href="auth_conf"><i class="sb-nav-link-icon fas fa-user-lock fa-fw mr-2"></i><span class="nav-label"><?php echo _("Authentication"); ?></a>
        </div>
          <?php endif; ?>
          <?php if (RASPI_VNSTAT_ENABLED) : ?>
        <div class="sb-nav-link-icon px-2">
          <a class="nav-link" href="data_use"><i class="sb-nav-link-icon fas fa-chart-bar fa-fw mr-2"></i><span class="nav-label"><?php echo _("Data usage"); ?></a>
        </div>
        <?php endif; ?>
        <?php if (RASPI_RESTAPI_ENABLED) : ?>
        <div class="sb-nav-link-icon px-2">
          <a class="nav-link" href="restapi_conf"><i class="sb-nav-link-icon fas fa-puzzle-piece mr-2"></i><span class="nav-label"><?php echo _("RestAPI"); ?></a>
        </div>
        <?php endif; ?>
        <?php if (RASPI_SYSTEM_ENABLED) : ?>
          <div class="sb-nav-link-icon px-2">
          <a class="nav-link" href="system_info"><i class="sb-nav-link-icon fas fa-cube fa-fw mr-2"></i><span class="nav-label"><?php echo _("System"); ?></a>
          </div>
        <?php endif; ?>
        <div class="sb-nav-link-icon px-2">
          <a class="nav-link" href="about"><i class="sb-nav-link-icon fas fa-info-circle fa-fw mr-2"></i><span class="nav-label"><?php echo _("About RaspAP"); ?></a>
        </div>

