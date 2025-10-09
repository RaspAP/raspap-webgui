<!-- logfile output tab -->
<div class="tab-pane fade" id="logging">
  <h4><?php echo _("Logging") ?></h4>
  <p><?php echo _("Enable these options to log <code>dhcpcd</code> and <code>dnsmasq</code> activity.") ?></p>

  <div class="form-check form-switch">
    <input class="form-check-input" id="log-dhcp" type="checkbox" name="log-dhcp" value="1" <?php echo !empty($conf['log-dhcp']) ? ' checked="checked"' : "" ?> aria-describedby="log-dhcp-requests">
    <label class="form-check-label" for="log-dhcp"><?php echo _("Log DHCP requests") ?></label>
  </div>
  <div class="form-check form-switch">
    <input class="form-check-input" id="log-queries" type="checkbox" name="log-queries" value="1" <?php echo !empty($conf['log-queries']) ? ' checked="checked"' : "" ?> aria-describedby="log-dhcp-queries">
    <label class="form-check-label align-middle" for="log-queries"><?php echo _("Log DNS queries") ?></label>
    <input type="button" class="btn btn-outline btn-warning btn-sm align-top ms-4" id="js-cleardnsmasq-log" value="<?php echo _("Clear log"); ?>" />
  </div>

  <div class="row">
    <div class="mb-3 col-md-8 mt-2">
      <?php
      if (($conf['log-dhcp'] ?? 0) == 1 || ($conf['log-queries'] ?? 0) == 1) {
          echo '<textarea class="logoutput text-secondary" id="dnsmasq-log">'.htmlspecialchars($logdata, ENT_QUOTES).'</textarea>';
      } else {
          echo '<textarea class="logoutput my-3"></textarea>';
      }
      ?>
    </div>
  </div>
</div><!-- /.tab-pane -->
