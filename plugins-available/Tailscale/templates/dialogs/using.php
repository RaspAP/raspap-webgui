    <h5><?php echo _("Using exit node"); ?></h5>
    <input type="hidden" name="editNodeAddress" value="<?php echo $this->exitNodeAddress; ?>"> 
    <div class="my-3">
      <?php echo sprintf(
          _("The device <code>%s</code> is configured to use exit node <code>%s</code>. It has the Tailscale MagicDNS address <code>%s</code>."),
        htmlspecialchars($this->hostname),
        htmlspecialchars($this->exitNodeAddress),
        htmlspecialchars($magicHostname)
      ); ?>
    </div>

