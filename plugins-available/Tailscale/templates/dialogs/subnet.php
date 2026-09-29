  <h5><?php echo _("Approve subnet route"); ?></h5>
    <div class="mt-3" required>
      <?php echo sprintf(_("The device <code>%s</code> is advertising a subnet route. For security reasons, you must opt in to enable this."), htmlspecialchars($this->hostname)); ?>
    </div>
    <div class="mt-3">To enable subnet routes for this device, choose <strong>Open Tailscale Machines</strong>.</div>
    <button type="button" class="btn btn-primary mt-3" onclick="window.open('https://login.tailscale.com/admin/machines', '_blank')">
        <i class="fas fa-up-right-from-square mx-1"></i> <?php echo _("Open Tailscale Machines"); ?>
    </button>
    <input type="hidden" name="configOpt" value="subnet">
    <div class="mt-3">
      <ol>
        <li><?php echo sprintf(_("Locate the <code>%s</code> <strong>Subnets</strong> badge in the machines list."), htmlspecialchars($this->hostname)); ?></li>
        <li><?php echo sprintf(_("From the %s icon menu of the device, open the %s panel."), '<i class="fa-solid fa-ellipsis mx-1"></i>', '<strong>' . _('Edit route settings') . '</strong>'); ?></li>
        <li><?php echo _("Check the IP range box that corresponds to the subnet route you want to advertise, then select <strong>Save</strong>."); ?></li>
      </ol>
    </div>

    <div class="mt-3">
      <?php echo _("Choose <strong>Next</strong> when you've completed these steps."); ?>
    </div>
