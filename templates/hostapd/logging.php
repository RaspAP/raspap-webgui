<!-- logfile output tab -->
<div class="tab-pane fade" id="logoutput">
  <h4 class="mt-3"><?php echo _("Logging"); ?></h4>
  <div class="row">
    <div class="form-group col-md-8">
      <?php
      if ($arrHostapdConf['LogEnable'] == 1) {
          exec('sudo /bin/chmod o+r /tmp/hostapd.log');
          $log = file_get_contents('/tmp/hostapd.log');
          echo '<br /><textarea class="logoutput">'.htmlspecialchars($log, ENT_QUOTES).'</textarea>';
      } else {
          echo "<br />Logfile output not enabled";
      }
      ?>
     </div>
  </div>
</div><!-- /.tab-pane | logging tab -->
