<!-- logfile output tab -->
<div class="tab-pane fade" id="logging">
  <h4><?php echo _("Logging") ?></h4>
  <p><?php echo _("Enable these options to log DHCP server activity.") ?></p>

  <div class="custom-control custom-switch">
    <input class="custom-control-input" id="log-dhcp" type="checkbox" name="log-dhcp" value="1" <?php echo $conf['log-dhcp'] ? ' checked="checked"' : "" ?> aria-describedby="log-dhcp-requests">
    <label class="custom-control-label" for="log-dhcp"><?php echo _("Log DHCP requests") ?></label>
  </div>
  <div class="custom-control custom-switch">
    <input class="custom-control-input" id="log-queries" type="checkbox" name="log-queries" value="1" <?php echo $conf['log-queries'] ? ' checked="checked"' : "" ?> aria-describedby="log-dhcp-queries">
    <label class="custom-control-label" for="log-queries"><?php echo _("Log DNS queries") ?></label>
  </div>

  <?php
  exec('sudo chmod o+r /tmp/dnsmasq.log');
  $log = file_get_contents('/tmp/dnsmasq.log');
  echo '<textarea class="logoutput my-3">'.htmlspecialchars($log, ENT_QUOTES).'</textarea>';
  ?>
</div><!-- /.tab-pane -->
