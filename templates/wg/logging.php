<!-- wireguard logging tab -->
<div class="tab-pane fade" id="wglogging">
  <div class="row">
    <div class="col-md-12">
      <h4 class="mt-3"><?php echo _("Logging"); ?></h4>
        <p><?php echo _("Enable this option to display an updated <code>wg-quick</code> debug log.") ?></p>
        <div class="custom-control custom-switch">
          <input class="custom-control-input" id="wgLogEnable" type="checkbox" name="wgLogEnable" value="1" <?php echo $optLogEnable ? ' checked="checked"' : "" ?> aria-describedby="wgLogEnable">
          <label class="custom-control-label" for="wgLogEnable"><?php echo _("Logfile output") ?></label>
        </div>
        <?php
          exec('sudo chmod o+r /tmp/wireguard.log');
          $log = file_get_contents('/tmp/wireguard.log');
          echo '<textarea class="logoutput my-3">'.htmlspecialchars($log, ENT_QUOTES).'</textarea>';
        ?>
    </div>
  </div><!-- /.row -->
</div><!-- /.tab-pane | logging tab -->

