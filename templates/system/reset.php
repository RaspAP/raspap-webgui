<!-- reset tab -->
<div role="tabpanel" class="tab-pane" id="reset">
  <h4 class="mt-3"><?php echo _("Restore settings") ;?></h4>
    <?php if (!RASPI_MONITOR_ENABLED) : ?>
    <?php echo CSRFTokenFieldTag() ?>
      <div class="row">
        <div class="form-group col-lg-8 col-md-8">
          <label for="cbxhwmode">
            <?php echo sprintf(_("To reset RaspAP to its <a href=\"%s\">initial configuration</a>, click or tap the button below."), "https://docs.raspap.com/defaults/"); ;?>
          </label>
          <?php getTooltip('Restores all access point (AP) service settings to their default values. This applies to hostapd, dhcpcd and dnsmasq.', 'tiphwmode', true); ?>
          <div class="small"> 
          <?php echo _("Custom files for optional components such as Ad Blocking, WireGuard or OpenVPN will remain on the system."); ?>
          </div>
        </div>
      </div>
      <button type="button" class="btn btn-danger" name="system-reset" data-toggle="modal" data-target="#system-confirm-reset" /><?php echo _("Perform reset"); ?></button>
    <?php endif ?>
</div>


