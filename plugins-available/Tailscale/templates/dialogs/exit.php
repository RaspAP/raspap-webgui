    <h5><?php echo _("Configure new exit node"); ?></h5>
    <div class="mt-3">
      <?php echo sprintf(_("For security reasons, you must opt in to enable exit node functionality. The first step is to advertise <code>%s</code> as an exit node in your tailnet. In the next step, you'll allow this device to be an exit node."), htmlspecialchars($this->hostname)); ?>
    </div>

    <div class="mt-3">
      <input type="hidden" name="ipv4address" value="<?php echo $address; ?>">
      <div class="form-check form-switch">
        <input class="form-check-input" id="chxexitnode" name="exitnode" type="checkbox" value="1" checked />
        <label class="form-check-label" for="chxexitnode"><?php printf(_("Advertise <code>%s</code> as an exit node"), htmlspecialchars($this->hostname)); ?></label>
        <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto"
           title="<?php echo _("This effectively configures Tailscale  as a VPN to mask your real location, access region-restricted content, or enhance privacy when connecting from untrusted networks."); ?>">
        </i>
        <p id="exitnode-description">
          <small>
            <?php echo _("This option lets Tailscale know your device is ready to route traffic."); ?>
          </small>
        </p>
      </div>

      <div class="form-check form-switch">
        <input class="form-check-input" id="chxoptimize" name="optimize" type="checkbox" value="1" checked />
        <label class="form-check-label" for="chxoptimize"><?php echo _("Enable optimized UDP throughput"); ?></label>
        <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto"
           title="<?php echo _("Recommended for Tailscale exit nodes with Linux 6.2 or later kernels, this uses UDP generic receive offload (GRO) forwarding to reduce CPU overhead."); ?>">
        </i>
        <p id="optimize-description">
          <small>
            <?php echo _("This option enables transport layer offloads for better performance."); ?>
          </small>
        </p>
      </div>
    </div>

    <div class="mt-3">
      <?php echo _("Choose <strong>Next</strong> to continue."); ?>
    </div>

