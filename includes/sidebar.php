      <ul class="navbar-nav sidebar sidebar-light d-none d-md-block accordion <?php echo (isset($_SESSION["toggleState"])) ? $_SESSION["toggleState"] : null ; ?>" id="accordionSidebar">
        <!-- Sidebar - Brand -->
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="wlan0_info">
          <div class="sidebar-brand-text ml-1"><?php echo RASPI_BRAND_TEXT; ?></div>
        </a>
        <!-- Divider -->
        <hr class="sidebar-divider my-0">
        <div class="row">
          <div class="col-xs ml-3 sidebar-brand-icon">
            <img src="app/img/raspAP-logo.php" class="navbar-logo" width="64" height="64">
          </div>
          <div class="col-xs ml-2">
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
        <li class="nav-item">
          <a class="nav-link" href="wlan0_info"><i class="fas fa-tachometer-alt fa-fw mr-2"></i><span class="nav-label"><?php echo _("Dashboard"); ?></span></a>
        </li>
        <?php if (RASPI_HOTSPOT_ENABLED) : ?>
        <li class="nav-item">
          <a class="nav-link" href="hostapd_conf"><i class="far fa-dot-circle fa-fw mr-2"></i><span class="nav-label"><?php echo _("Hotspot"); ?></a>
        </li>
        <?php endif; ?>
        <?php if (RASPI_DHCP_ENABLED && !$_SESSION["bridgedEnabled"]) : ?>
        <li class="nav-item">
          <a class="nav-link" href="dhcpd_conf"><i class="fas fa-exchange-alt fa-fw mr-2"></i><span class="nav-label"><?php echo _("DHCP Server"); ?></a>
        </li>
        <?php endif; ?>
        <?php if (RASPI_ADBLOCK_ENABLED && !$_SESSION["bridgedEnabled"]) : ?>
        <li class="nav-item">
           <a class="nav-link" href="adblock_conf"><i class="far fa-hand-paper fa-fw mr-2"></i><span class="nav-label"><?php echo _("Ad Blocking"); ?></a>
        </li>
        <?php endif; ?>
        <?php if (RASPI_NETWORK_ENABLED) : ?>
        <li class="nav-item">
           <a class="nav-link" href="network_conf"><i class="fas fa-network-wired fa-fw mr-2"></i><span class="nav-label"><?php echo _("Networking"); ?></a>
        </li>
        <?php endif; ?>
        <?php if (RASPI_WIFICLIENT_ENABLED && !$_SESSION["bridgedEnabled"]) : ?>
        <li class="nav-item">
          <a class="nav-link" href="wpa_conf"><i class="fas fa-wifi fa-fw mr-2"></i><span class="nav-label"><?php echo _("WiFi client"); ?></span></a>
        </li>
        <?php endif; ?>
        <?php if (RASPI_OPENVPN_ENABLED) : ?>
        <li class="nav-item">
          <a class="nav-link" href="openvpn_conf"><i class="fas fa-key fa-fw mr-2"></i><span class="nav-label"><?php echo _("OpenVPN"); ?></a>
        </li>
        <?php endif; ?>
        <?php if (RASPI_WIREGUARD_ENABLED) : ?>
        <li class="nav-item">
          <a class="nav-link" href="wg_conf"><i class="ra-wireguard mr-2"></i><span class="nav-label"><?php echo _("WireGuard"); ?></a>
        </li>
        <?php endif; ?>
        <?php if (RASPI_VPN_PROVIDER_ENABLED) : ?>
        <li class="nav-item">
          <a class="nav-link" href="provider_conf"><i class="fas fa-shield-alt fa-fw mr-2"></i><span class="nav-label"><?php echo _(getProviderValue($_SESSION["providerID"], "name")); ?></a>
        </li>
        <?php endif; ?>
        <?php if (RASPI_TORPROXY_ENABLED) : ?>
        <li class="nav-item">
           <a class="nav-link" href="torproxy_conf"><i class="fas fa-eye-slash fa-fw mr-2"></i><span class="nav-label"><?php echo _("TOR proxy"); ?></a>
        </li>
        <?php endif; ?>
        <?php if (RASPI_CONFAUTH_ENABLED) : ?>
        <li class="nav-item">
          <a class="nav-link" href="auth_conf"><i class="fas fa-user-lock fa-fw mr-2"></i><span class="nav-label"><?php echo _("Authentication"); ?></a>
        </li>
        <?php endif; ?>
        <?php if (RASPI_VNSTAT_ENABLED) : ?>
        <li class="nav-item">
          <a class="nav-link" href="data_use"><i class="fas fa-chart-bar fa-fw mr-2"></i><span class="nav-label"><?php echo _("Data usage"); ?></a>
        </li>
        <?php endif; ?>
        <?php if (RASPI_RESTAPI_ENABLED) : ?>
        <li class="nav-item">
          <a class="nav-link" href="restapi_conf"><i class="fas fa-puzzle-piece mr-2"></i><span class="nav-label"><?php echo _("RestAPI"); ?></a>
        </li>
        <?php endif; ?>
        <?php if (RASPI_SYSTEM_ENABLED) : ?>
        <li class="nav-item">
          <a class="nav-link" href="system_info"><i class="fas fa-cube fa-fw mr-2"></i><span class="nav-label"><?php echo _("System"); ?></a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
          <a class="nav-link" href="about"><i class="fas fa-info-circle fa-fw mr-2"></i><span class="nav-label"><?php echo _("About RaspAP"); ?></a>
        </li>
        <!-- Divider -->
        <hr class="sidebar-divider d-none d-md-block">

        <!-- Sidebar Toggler (Sidebar) -->
        <div class="text-center d-none d-md-block">
          <button class="rounded-circle border-0" id="sidebarToggle"></button>
        </div>
    </ul>
