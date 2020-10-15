<!-- wireguard logging tab -->
<div class="tab-pane fade" id="wglogging">
  <div class="row">
    <div class="col-md-12">
      <h4 class="mt-3"><?php echo _("Logging"); ?></h4>
          <div class="custom-control custom-switch">
            <input class="custom-control-input" id="wg_log" type="checkbox" name="wg_log" value="1" <?php echo $wg_log ? ' checked="checked"' : "" ?> aria-describedby="wg_log">
            <label class="custom-control-label" for="wg_log"><?php echo _("Display WireGuard status") ?></label>
          </div>
          <p><small><?php echo _("Enable this option to display an updated WireGuard status.") ?></small></p>
          <?php
              exec('sudo chmod o+r /tmp/wireguard.log');
              $log = file_get_contents('/tmp/wireguard.log');
              echo '<textarea class="logoutput my-3">'.htmlspecialchars($log, ENT_QUOTES).'</textarea>';
          ?>
    </div>
  </div><!-- /.row -->
</div><!-- /.tab-pane | logging tab -->

