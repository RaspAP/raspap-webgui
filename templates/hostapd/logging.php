<!-- logfile output tab -->
<div class="tab-pane fade" id="logoutput">
  <h4 class="mt-3"><?php echo _("Logging"); ?></h4>
  <p><?php echo _("Enable this option to log <code>hostapd</code> activity.") ?></p>

  <div class="custom-control custom-switch">
    <?php $checked = $arrHostapdConf['LogEnable'] == 1 ? 'checked="checked"' : '' ?>
    <input class="custom-control-input" id="chxlogenable" name="logEnable" type="checkbox" value="1" <?php echo $checked ?> />
    <label class="custom-control-label" for="chxlogenable"><?php echo _("Logfile output"); ?></label>
  </div>

  <div class="row">
    <div class="form-group col-md-8 mt-2">
      <?php
      if ($arrHostapdConf['LogEnable'] == 1) {
          exec('sudo /bin/chmod o+r /tmp/hostapd.log');
          $log = file_get_contents('/tmp/hostapd.log');
          echo '<textarea class="logoutput">'.htmlspecialchars($log, ENT_QUOTES).'</textarea>';
      } else {
          echo '<textarea class="logoutput my-3"></textarea>';
      }
      ?>
     </div>
  </div>
</div><!-- /.tab-pane | logging tab -->
