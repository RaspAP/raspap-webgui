<div class="row">
  <div class="col-lg-12">
    <div class="card shadow">
      <div class="card-header page-card-header">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <i class="fas fa-tachometer-alt fa-fw me-2"></i>
            <?php echo _("Dashboard"); ?>
          </div>
          <div>
            <button type="button" onClick="window.location.reload();" class="btn btn-primary btn-sm">
              <i class="fas fa-sync-alt"></i> <?php echo _("Refresh") ?>
            </button>
          </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->

      <div class="card-body">
        <?php $status->showMessages(); ?>

        <div class="dashboard-container row">
          <div class="connections-left col-lg-4">
            <div class="connection-item">
              <a href="/network_conf" title="<?php echo _("Networking"); ?>" class="<?php echo $ethernetActive; ?>"><span><?php echo _("Ethernet"); ?></span></a>
              <a href="/network_conf" title="<?php echo _("Networking"); ?>" class="<?php echo $ethernetActive; ?>"><i class="fas fa-ethernet fa-2xl"></i></a>
            </div>
            <div class="connection-item">
              <a href="/wpa_conf" title="<?php echo _("WiFi Client"); ?>" class="<?php echo $wirelessActive; ?>"><span><?php echo _("Repeater"); ?></span>
              <a href="/wpa_conf" title="<?php echo _("WiFi Client"); ?>" class="<?php echo $wirelessActive; ?>"><i class="fas fa-wifi fa-2xl"></i></a>
            </div>
            <div class="connection-item">
              <a href="/network_conf" title="<?php echo _("Tethering"); ?>" class="<?php echo $tetheringActive; ?>"><span><?php echo _("Tethering"); ?></span></a>
              <a href="/network_conf" title="<?php echo _("Tethering"); ?>" class="<?php echo $tetheringActive; ?>"><i class="fas fa-mobile-alt fa-2xl"></i></a>
            </div>
            <div class="connection-item">
              <a href="/network_conf" title="<?php echo _("Cellular"); ?>" class="<?php echo $cellularActive; ?>"><span><?php echo _("Cellular"); ?></span></a>
              <a href="/network_conf" title="<?php echo _("Cellular"); ?>" class="<?php echo $cellularActive; ?>"><i class="fas fa-broadcast-tower fa-2xl"></i></a>
            </div>
            <img src="app/img/dashed.svg" class="dashed-lines" alt="">
            <img src="<?php echo htmlspecialchars(renderConnection($connectionType)); ?>" class="solid-lines" alt="Network connection">
          </div>
          <div class="center-device col-12 col-lg-4">
            <div class="center-device-top">
              <a href="/system_info" title="<?php echo _("System"); ?>"><img class="device-illustration" src="app/img/devices/<?php echo $deviceImage; ?>" alt="<?php echo htmlspecialchars($revision, ENT_QUOTES); ?>"></a>
              <div class="device-label"><a href="/system_info" title="<?php echo _("System"); ?>"><?php echo htmlspecialchars($revision, ENT_QUOTES); ?></a></div>
              
              <div class="ip-info-toggle mt-3 mb-2" role="tablist">
                <button id="public-info-tab" class="" data-bs-toggle="tab" data-bs-target="#public-info" type="button" role="tab" aria-controls="public-info" aria-selected="false"><?php echo _("Public"); ?></button>
                <button id="local-info-tab" class="active" data-bs-toggle="tab" data-bs-target="#local-info" type="button" role="tab" aria-controls="local-info" aria-selected="true"><?php echo _("Local"); ?></button>
              </div>

              <div class="tab-content">
                <div id="local-info" class="tab-pane fade show active" role="tabpanel" aria-labelledby="local-info-tab">
                  <div class="mt-1 small"><?php echo _("IP Address"); ?>: <a href="/dhcpd_conf"><?php echo htmlspecialchars($ipv4Address, ENT_QUOTES); ?></a></div>
                  <div class="small"><?php echo _("Netmask"); ?>: <a href="/dhcpd_conf"><?php echo htmlspecialchars($ipv4Netmask, ENT_QUOTES); ?></a></div>
                  <div class="small"><?php echo _("MAC Address"); ?>: <a href="/dhcpd_conf"><?php echo htmlspecialchars($macAddress, ENT_QUOTES); ?></a></div>
                  <div class="small"><?php echo _("SSID"); ?>: <a href="/hostapd_conf"><?php echo htmlspecialchars($ssid, ENT_QUOTES); ?></a></div>
                </div>
                <div id="public-info" class="tab-pane fade" role="tabpanel" aria-labelledby="public-info-tab">
                  <div class="mt-1 small"><?php echo _("IP Address"); ?>: <a href="/dhcpd_conf"><?php echo htmlspecialchars($publicIpv4Address, ENT_QUOTES); ?></a></div>
                  <div class="small"><?php echo _("Netmask"); ?>: <a href="/dhcpd_conf"><?php echo htmlspecialchars($publicIpv4Netmask, ENT_QUOTES); ?></a></div>
                  <div class="small"><?php echo _("MAC Address"); ?>: <a href="/dhcpd_conf"><?php echo htmlspecialchars($publicMacAddress, ENT_QUOTES); ?></a></div>
                  <div class="small"><?php echo _("SSID"); ?>: <a href="/wpa_conf"><?php echo htmlspecialchars($publicSsid, ENT_QUOTES); ?></a></div>
                </div>
              </div>
            </div>

            <div class="bottom">
              <div class="device-status">
                <a href="/hostapd_conf">
                  <div class="status-item <?php echo $hostapdStatus; ?>">
                    <div>
                      <i class="fas fa-bullseye fa-2x"></i>
                    </div>
                    <span><?php echo _('AP'); ?></span>
                  </div>
                </a>
                <a href="/hostapd_conf#advanced">
                  <div class="status-item <?php echo $bridgedStatus; ?>">
                    <div>
                      <i class="fas fa-bridge fa-2x"></i>
                    </div>
                    <span><?php echo _('Bridged'); ?></span>
                  </div>
                </a>
                <a href="/adblock_conf">
                  <div class="status-item <?php echo $adblockStatus; ?>">
                    <div>
                      <i class="far fa-hand-paper fa-2x"></i>
                    </div>
                    <span><?php echo _('Adblock'); ?></span>
                  </div>
                </a>
                <a href="<?php echo $vpnManaged; ?>">
                  <div class="status-item <?php echo $vpnStatus; ?>">
                    <div>
                      <i class="fas fa-shield-alt fa-2x"></i>
                    </div>
                    <span><?php echo _('VPN'); ?></span>
                  </div>
                </a>
                <?php echo $firewallManaged; ?>
                  <div class="status-item <?php echo $firewallStatus; ?>">
                    <div class="fa-stack">
                      <i class="fas fa-fire-flame-curved fa-stack-2x"></i>
                      <?php echo $firewallUnavailable; ?>
                    </div>
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
                <a href="/dhcpd_conf#client-list">
                  <i class="fas fa-laptop <?php echo $totalClientsActive; ?>"></i>
                  <span class="client-count"><?php echo $totalClients; ?></span>
                </a>
              </div>
            </div>
          </div>

          <div class="connections-right col-lg-4">
            <div class="d-flex flex-column justify-content-around h-100">
              <div class="connection-item connection-right">
                <a href="/dhcpd_conf#client-list" class="<?php echo $wirelessClientActive; ?>">
                  <span class="fa-stack">
                    <i class="fas fa-laptop fa-stack-1x fa-2xl"></i>
                    <i class="fas fa-wifi fa-stack-1x fa-xs"></i>
                  </span>
                </a>
                <a href="/dhcpd_conf#client-list"><span class="<?php echo $wirelessClientActive; ?>"><?php echo $wirelessClientLabel; ?></span></a>
              </div>
              <div class="connection-item connection-right">
                <a href="/dhcpd_conf#client-list" class="<?php echo $ethernetClientActive; ?>">
                  <span class="fa-stack">
                    <i class="fas fa-laptop fa-stack-1x fa-2xl"></i>
                    <i class="fas fa-ethernet fa-stack-1x fa-xs"></i>
                  </span>
                </a>
                <a href="/dhcpd_conf#client-list"><span class="<?php echo $ethernetClientActive; ?>"><?php echo $ethernetClientLabel; ?></span></a>
              </div>
            </div>
            <?php echo renderClientConnections($wirelessClients, $ethernetClients); ?>
            <img src="app/img/right-dashed.svg" class="dashed-lines dashed-lines-right" alt="">
          </div>
        </div>
      </div><!-- /.card-body -->

      <div class="card-footer"><?php echo _("Information provided by raspap.sysinfo"); ?></div>

    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->

<?php if (!empty($switchberrySupported)): ?>
<div class="row mt-3" data-switchberry-dashboard>
  <div class="col-lg-12">
    <div class="card shadow switchberry-dashboard-card">
      <div class="card-header page-card-header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
          <div>
            <i class="fas fa-clock fa-fw me-2"></i>
            <?php echo _("Switchberry timing"); ?>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span id="switchberry-dashboard-updated" class="small opacity-75"><?php echo _("Loading live timing state…"); ?></span>
            <button type="button" id="switchberry-dashboard-refresh" class="btn btn-primary btn-sm" title="<?php echo _("Refresh timing state"); ?>">
              <i class="fas fa-sync-alt"></i>
            </button>
          </div>
        </div>
      </div>

      <div class="card-body">
        <div id="switchberry-dashboard-error" class="alert alert-warning d-none" role="alert"></div>

        <div class="row g-3">
          <div class="col-sm-6 col-xl-3">
            <a class="switchberry-timing-tile tone-primary" data-switchberry-tile="ptp" href="/switchberry#switchberry-ptp">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="switchberry-timing-icon"><i class="fas fa-network-wired"></i></span>
                <span id="switchberry-dashboard-ptp-badge" class="badge text-bg-secondary">—</span>
              </div>
              <div class="small text-uppercase text-muted fw-semibold mb-1"><?php echo _("PTP clock"); ?></div>
              <div id="switchberry-dashboard-ptp-title" class="h5 mb-1"><?php echo _("Loading…"); ?></div>
              <div id="switchberry-dashboard-ptp-detail" class="small mb-1">—</div>
              <div id="switchberry-dashboard-ptp-note" class="small text-muted text-truncate">—</div>
            </a>
          </div>

          <div class="col-sm-6 col-xl-3">
            <a class="switchberry-timing-tile tone-secondary" data-switchberry-tile="clockmatrix" href="/switchberry#switchberry-clockmatrix">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="switchberry-timing-icon"><i class="fas fa-wave-square"></i></span>
                <span id="switchberry-dashboard-clockmatrix-badge" class="badge text-bg-secondary">—</span>
              </div>
              <div class="small text-uppercase text-muted fw-semibold mb-1"><?php echo _("ClockMatrix"); ?></div>
              <div id="switchberry-dashboard-clockmatrix-title" class="h5 mb-1"><?php echo _("Loading…"); ?></div>
              <div id="switchberry-dashboard-clockmatrix-detail" class="small mb-1">—</div>
              <div id="switchberry-dashboard-clockmatrix-note" class="small text-muted text-truncate">—</div>
            </a>
          </div>

          <div class="col-sm-6 col-xl-3">
            <a class="switchberry-timing-tile tone-secondary" data-switchberry-tile="gnss" href="/switchberry#switchberry-gnss">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="switchberry-timing-icon"><i class="fas fa-satellite-dish"></i></span>
                <span id="switchberry-dashboard-gnss-badge" class="badge text-bg-secondary">—</span>
              </div>
              <div class="small text-uppercase text-muted fw-semibold mb-1"><?php echo _("GNSS"); ?></div>
              <div id="switchberry-dashboard-gnss-title" class="h5 mb-1"><?php echo _("Loading…"); ?></div>
              <div id="switchberry-dashboard-gnss-detail" class="small mb-1">—</div>
              <div id="switchberry-dashboard-gnss-note" class="small text-muted text-truncate">—</div>
            </a>
          </div>

          <div class="col-sm-6 col-xl-3">
            <a class="switchberry-timing-tile tone-secondary" data-switchberry-tile="timing" href="/switchberry#switchberry-overview">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="switchberry-timing-icon"><i class="fas fa-stopwatch"></i></span>
                <span id="switchberry-dashboard-timing-badge" class="badge text-bg-secondary">—</span>
              </div>
              <div class="small text-uppercase text-muted fw-semibold mb-1"><?php echo _("Timing path"); ?></div>
              <div id="switchberry-dashboard-timing-title" class="h5 mb-1"><?php echo _("Loading…"); ?></div>
              <div id="switchberry-dashboard-timing-detail" class="small mb-1">—</div>
              <div id="switchberry-dashboard-timing-note" class="small text-muted text-truncate">—</div>
            </a>
          </div>
        </div>

        <div class="switchberry-reference-strip mt-3">
          <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2">
            <div class="small fw-semibold text-nowrap"><i class="fas fa-random me-2"></i><?php echo _("Active references"); ?></div>
            <div id="switchberry-dashboard-references" class="d-flex flex-wrap gap-2">
              <span class="badge rounded-pill text-bg-light border text-dark"><?php echo _("Loading…"); ?></span>
            </div>
            <a class="btn btn-outline-primary btn-sm ms-lg-auto text-nowrap" href="/switchberry">
              <?php echo _("Open timing console"); ?> <i class="fas fa-arrow-right ms-1"></i>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
