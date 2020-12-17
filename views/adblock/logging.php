<!-- logging tab -->
<div class="tab-pane fade" id="adblocklogfileoutput">
  <h4 class="mt-3"><?php echo _("Logging"); ?></h4>
    <div class="row">
      <div class="form-group col-md-8">
        <?php
        $log = '';
        exec('sudo chmod o+r /tmp/dnsmasq.log');
        $handle = fopen("/tmp/dnsmasq.log", "r");
        if ($handle) {
          while (($line = fgets($handle)) !== false) {
            if (preg_match('/(0.0.0.0)/', $line)){
              $log.=$line;
            }
          }
        } else {
          $log = "Unable to open log file";
        }
        fclose($handle);
        echo '<textarea class="logoutput">'.htmlspecialchars($log, ENT_QUOTES).'</textarea>';
        ?>
    </div>
  </div>
</div><!-- /.tab-pane -->

