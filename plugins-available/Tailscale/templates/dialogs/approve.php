    <h5><?php echo _("Allow exit node"); ?></h5>
    <div class="mt-3">
      <?php echo sprintf(_("The device <code>%s</code> is pending approval as an exit node."), htmlspecialchars($this->hostname)); ?>
    </div>
    <div class="mt-3">To allow this device as an exit node, choose <strong>Open Tailscale Machines</strong>.</div>
    <button type="button" class="btn btn-primary mt-3" onclick="window.open('https://login.tailscale.com/admin/machines', '_blank')">
        <i class="fas fa-up-right-from-square mx-1"></i> <?php echo _("Open Tailscale Machines"); ?>
    </button>

    <div class="mt-3">
        <ol>
          <li><?php echo sprintf(_("Locate the <code>%s</code> <strong>Exit Node</strong> badge in the machines list."), htmlspecialchars($this->hostname)); ?></li>
          <li><?php echo sprintf(_("From the %s icon menu of the exit node, open the %s panel."), '<i class="fa-solid fa-ellipsis mx-1"></i>', '<strong>' . _('Edit route settings') . '</strong>'); ?></li>
          <li><?php echo _("Enable the <strong>Use as exit node</strong> option."); ?></li>
        </ol>
    </div>

    <div class="mt-3">
      <?php echo _("Choose <strong>Next</strong> when you've completed these steps."); ?>
    </div>

