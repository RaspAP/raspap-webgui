    <h5><?php echo _("Login to Tailscale"); ?></h5>
    <div class="mt-3"><?php echo sprintf(_('To connect device %s to your tailnet, choose %s.'), '<code>' . htmlspecialchars(trim($this->hostname)) . '</code>', '<strong>' . _('Open Tailscale Login') . '</strong>'); ?></div>
    <button type="button" class="btn btn-primary mt-3" onclick="window.open('<?php echo htmlspecialchars($loginUrl, ENT_QUOTES); ?>', '_blank')">
        <i class="fas fa-up-right-from-square mx-1"></i> <?php echo _("Open Tailscale Login"); ?>
    </button>
    <div class="mt-3">
      <?php echo _("After logging in, choose <strong>Next</strong> to continue."); ?>
    </div>

