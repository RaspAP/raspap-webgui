<!-- reset tab -->
<div role="tabpanel" class="tab-pane" id="tools">
  <h4 class="mt-3"><?php echo _("System tools") ;?></h4>
    <?php if (!RASPI_MONITOR_ENABLED) : ?>
    <?php echo CSRFTokenFieldTag() ?>

      <div class="row">
        <div class="form-group col-lg-8 col-md-8">
          <label for="debug">
            <?php echo sprintf(_("To generate a system <a href=\"%s\" target=\"_blank\">debug log</a>, click or tap the button below."), "https://docs.raspap.com/ap-basics/#debug-log"); ;?>
          </label>
          <div class="small"> 
          <?php echo _("Debug log information contains the RaspAP version, current state and configuration of AP related services, installed system packages, Linux kernel version and networking details. No passwords or other sensitive data are included."); ?>
          </div>
        </div>
      </div>
      <button type="button" class="btn btn-warning mb-3" name="debug-log" data-toggle="modal" data-target="#debugModal" />
        <i class="fas fa-ambulance ml-1 mr-2"></i><?php echo _("Generate debug log"); ?>
      </button>

      <div class="row">
        <div class="form-group col-lg-8 col-md-8">
          <label for="reset">
            <?php echo sprintf(_("To reset RaspAP to its <a href=\"%s\" target=\"_blank\">initial configuration</a>, click or tap the button below."), "https://docs.raspap.com/defaults/"); ;?>
          </label>
          <?php getTooltip('Restores all access point (AP) service settings to their default values. This applies to hostapd, dhcpcd and dnsmasq.', 'tiphwmode', true); ?>
          <div class="small"> 
          <?php echo _("Custom files for optional components such as Ad Blocking, WireGuard or OpenVPN will remain on the system."); ?>
          </div>
        </div>
      </div>
      <button type="button" class="btn btn-danger" name="system-reset" data-toggle="modal" data-target="#system-confirm-reset" />
        <i class="fas fa-history ml-1 mr-2"></i><?php echo _("Perform reset"); ?>
      </button>

    <?php endif ?>
</div>


